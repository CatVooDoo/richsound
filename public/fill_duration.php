<?php

declare(strict_types=1);

/**
 * One-time utility: fill duration for tracks that have duration = NULL.
 * Open in browser once, then delete this file.
 *
 * Supports MP3 (CBR estimation + Xing/Info VBR header) and WAV (RIFF header).
 */

define('BASE_PATH', dirname(__DIR__));

$dbCfg = require BASE_PATH . '/config/database.php';

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $dbCfg['host'],
    $dbCfg['port'],
    $dbCfg['database'],
    $dbCfg['charset']
);

try {
    $pdo = new PDO($dsn, $dbCfg['username'], $dbCfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die('DB connection failed: ' . htmlspecialchars($e->getMessage()));
}

$tracks = $pdo->query(
    "SELECT id, title, file_path FROM tracks WHERE duration IS NULL OR duration = 0"
)->fetchAll(PDO::FETCH_ASSOC);

$update = $pdo->prepare("UPDATE tracks SET duration = :dur WHERE id = :id");

// ─── MP3 duration ────────────────────────────────────────────────────────────

function getMp3Duration(string $path): ?int
{
    $fp = @fopen($path, 'rb');
    if (!$fp) {
        return null;
    }

    $size = filesize($path);
    if ($size === false || $size < 10) {
        fclose($fp);
        return null;
    }

    // Skip ID3v2 tag
    $header = fread($fp, 10);
    $offset = 0;
    if (substr($header, 0, 3) === 'ID3') {
        $tagSize = ((ord($header[6]) & 0x7F) << 21)
                 | ((ord($header[7]) & 0x7F) << 14)
                 | ((ord($header[8]) & 0x7F) << 7)
                 |  (ord($header[9]) & 0x7F);
        $offset = 10 + $tagSize;
    }

    fseek($fp, $offset);
    $chunk = fread($fp, 8192);
    if ($chunk === false) {
        fclose($fp);
        return null;
    }

    // Find first sync word
    $frameStart = null;
    $frameBytes = '';
    $len = strlen($chunk);
    for ($i = 0; $i < $len - 3; $i++) {
        if (ord($chunk[$i]) === 0xFF && (ord($chunk[$i + 1]) & 0xE0) === 0xE0) {
            $frameStart = $offset + $i;
            $frameBytes = substr($chunk, $i, 4);
            break;
        }
    }

    if ($frameStart === null || strlen($frameBytes) < 4) {
        fclose($fp);
        return null;
    }

    $b1 = ord($frameBytes[1]);
    $b2 = ord($frameBytes[2]);
    $b3 = ord($frameBytes[3]);

    $version      = ($b1 >> 3) & 3; // 3=MPEG1 2=MPEG2 0=MPEG2.5
    $bitrateIdx   = ($b2 >> 4) & 0xF;
    $srIdx        = ($b2 >> 2) & 3;
    $channelMode  = ($b3 >> 6) & 3;  // 3=mono

    $sampleRates = [
        3 => [44100, 48000, 32000],
        2 => [22050, 24000, 16000],
        0 => [11025, 12000,  8000],
    ];
    $bitrates = [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 0];

    if (!isset($sampleRates[$version][$srIdx])) {
        fclose($fp);
        return null;
    }

    $sampleRate = $sampleRates[$version][$srIdx];
    $bitrate    = $bitrates[$bitrateIdx] * 1000;

    if ($sampleRate === 0 || $bitrate === 0) {
        fclose($fp);
        return null;
    }

    // Xing/Info VBR header offset (MPEG1 Layer3)
    $isMono     = $channelMode === 3;
    $xingOffset = ($version === 3) ? ($isMono ? 17 : 32) : ($isMono ? 9 : 17);

    fseek($fp, $frameStart + 4 + $xingOffset);
    $tag = fread($fp, 4);

    if ($tag === 'Xing' || $tag === 'Info') {
        $flags = unpack('N', fread($fp, 4))[1];
        if ($flags & 1) {
            $numFrames      = unpack('N', fread($fp, 4))[1];
            $samplesPerFrame = ($version === 3) ? 1152 : 576;
            $dur = (int) round($numFrames * $samplesPerFrame / $sampleRate);
            fclose($fp);
            return $dur > 0 ? $dur : null;
        }
    }

    // CBR fallback: estimate from file size and bitrate
    $audioSize = $size - $frameStart;
    $dur = (int) round($audioSize * 8 / $bitrate);
    fclose($fp);
    return $dur > 0 ? $dur : null;
}

// ─── WAV duration ─────────────────────────────────────────────────────────────

function getWavDuration(string $path): ?int
{
    $fp = @fopen($path, 'rb');
    if (!$fp) {
        return null;
    }

    $header = fread($fp, 44);
    fclose($fp);

    if (strlen($header) < 44) {
        return null;
    }
    if (substr($header, 0, 4) !== 'RIFF' || substr($header, 8, 4) !== 'WAVE') {
        return null;
    }

    $byteRate = unpack('V', substr($header, 28, 4))[1];
    $dataSize = unpack('V', substr($header, 40, 4))[1];

    if ($byteRate === 0) {
        return null;
    }

    return (int) round($dataSize / $byteRate);
}

// ─── Main ─────────────────────────────────────────────────────────────────────

function getAudioDuration(string $absPath): ?int
{
    $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
    return match ($ext) {
        'mp3'       => getMp3Duration($absPath),
        'wav'       => getWavDuration($absPath),
        default     => null,
    };
}

$h = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$results = [];
foreach ($tracks as $track) {
    $absPath = BASE_PATH . '/public' . $track['file_path'];
    if (!file_exists($absPath)) {
        $results[] = ['id' => $track['id'], 'title' => $track['title'], 'status' => 'skip', 'reason' => 'файл не найден'];
        continue;
    }

    $dur = getAudioDuration($absPath);
    if ($dur === null || $dur <= 0) {
        $results[] = ['id' => $track['id'], 'title' => $track['title'], 'status' => 'skip', 'reason' => 'не удалось определить длительность'];
        continue;
    }

    $update->execute([':dur' => $dur, ':id' => $track['id']]);
    $results[] = ['id' => $track['id'], 'title' => $track['title'], 'status' => 'ok', 'duration' => $dur];
}

$total   = count($results);
$updated = count(array_filter($results, fn($r) => $r['status'] === 'ok'));
$skipped = $total - $updated;

?><!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Fill duration</title>
<style>
  body { font-family: monospace; background: #0d0d14; color: #ccc; padding: 2rem; }
  h1 { color: #8b5cf6; }
  .summary { margin: 1rem 0; padding: 1rem; background: #1a1a2e; border-radius: 8px; }
  table { border-collapse: collapse; width: 100%; margin-top: 1.5rem; }
  th, td { padding: .5rem 1rem; text-align: left; border-bottom: 1px solid #222; }
  th { color: #8b5cf6; }
  .ok { color: #22d3ee; }
  .skip { color: #f87171; }
  .warn { margin-top: 2rem; color: #fbbf24; font-size: .85rem; }
</style>
</head>
<body>
<h1>Fill duration</h1>
<div class="summary">
    Всего треков без длительности: <b><?= $h($total) ?></b> &nbsp;·&nbsp;
    Обновлено: <b class="ok"><?= $h($updated) ?></b> &nbsp;·&nbsp;
    Пропущено: <b class="skip"><?= $h($skipped) ?></b>
</div>

<?php if ($results): ?>
<table>
    <tr><th>ID</th><th>Название</th><th>Результат</th></tr>
    <?php foreach ($results as $r): ?>
    <tr>
        <td><?= $h($r['id']) ?></td>
        <td><?= $h($r['title']) ?></td>
        <td class="<?= $r['status'] === 'ok' ? 'ok' : 'skip' ?>">
            <?php if ($r['status'] === 'ok'): ?>
                ✓ <?= $h(sprintf('%d:%02d', intdiv($r['duration'], 60), $r['duration'] % 60)) ?>
            <?php else: ?>
                — <?= $h($r['reason']) ?>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
<p>Нет треков с пустой длительностью.</p>
<?php endif; ?>

<p class="warn">⚠ Удали этот файл после использования: <code>src/public/fill_duration.php</code></p>
</body>
</html>
