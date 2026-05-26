<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// Autoloader (mirrors public/index.php)
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $base   = BASE_PATH . '/app/';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file     = $base . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Returns a live PDO connection via the same singleton the app uses.
 * We bypass the private static by using Reflection so tests and app
 * share the same connection (and thus the same transaction).
 */
function getTestDb(): PDO
{
    $config = require BASE_PATH . '/config/database.php';
    $dsn    = sprintf(
        '%s:host=%s;port=%s;dbname=%s;charset=%s',
        $config['driver'],
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Inject into Model's private singleton so all models use same connection
        $ref  = new ReflectionProperty(\App\Core\Model::class, 'connection');
        $ref->setAccessible(true);
        $ref->setValue(null, $pdo);
    }

    return $pdo;
}

/**
 * Seed minimal test fixtures; returns ids as associative array.
 * Wrapped in a transaction — call rollbackFixtures() in teardown.
 *
 * @return array{listener_id:int, author_id:int, track_id:int, album_id:int}
 */
function seedFixtures(): array
{
    $db = getTestDb();

    // Use a unique suffix so parallel runs don't collide
    $suffix = uniqid('_t', true);

    // listener
    $db->prepare('INSERT INTO users (name, email, password, role) VALUES (:n,:e,:p,:r)')
       ->execute(['n' => "Listener$suffix", 'e' => "listener$suffix@test.loc",
                  'p' => password_hash('secret', PASSWORD_BCRYPT), 'r' => 'listener']);
    $listenerId = (int) $db->lastInsertId();

    // author
    $db->prepare('INSERT INTO users (name, email, password, role) VALUES (:n,:e,:p,:r)')
       ->execute(['n' => "Author$suffix", 'e' => "author$suffix@test.loc",
                  'p' => password_hash('secret', PASSWORD_BCRYPT), 'r' => 'author']);
    $authorId = (int) $db->lastInsertId();

    // album
    $db->prepare('INSERT INTO albums (author_id, title) VALUES (:a,:t)')
       ->execute(['a' => $authorId, 't' => "Album$suffix"]);
    $albumId = (int) $db->lastInsertId();

    // track (file_path required; cover optional)
    $db->prepare('INSERT INTO tracks (author_id, album_id, title, file_path) VALUES (:a,:al,:t,:f)')
       ->execute(['a' => $authorId, 'al' => $albumId, 't' => "Track$suffix", 'f' => "fake/$suffix.mp3"]);
    $trackId = (int) $db->lastInsertId();

    return [
        'listener_id' => $listenerId,
        'author_id'   => $authorId,
        'track_id'    => $trackId,
        'album_id'    => $albumId,
        'suffix'      => $suffix,
    ];
}

/**
 * Remove all rows created by seedFixtures() for given ids.
 */
function cleanFixtures(array $ids): void
{
    $db = getTestDb();
    $db->prepare('DELETE FROM likes         WHERE user_id  IN (:l,:a) OR track_id = :t')->execute(['l' => $ids['listener_id'], 'a' => $ids['author_id'], 't' => $ids['track_id']]);
    $db->prepare('DELETE FROM subscriptions WHERE subscriber_id IN (:l,:a) OR author_id IN (:l2,:a2)')->execute(['l' => $ids['listener_id'], 'a' => $ids['author_id'], 'l2' => $ids['listener_id'], 'a2' => $ids['author_id']]);
    $db->prepare('DELETE FROM playlist_tracks WHERE track_id = :t')->execute(['t' => $ids['track_id']]);
    $db->prepare('DELETE FROM playlists      WHERE user_id  IN (:l,:a)')->execute(['l' => $ids['listener_id'], 'a' => $ids['author_id']]);
    $db->prepare('DELETE FROM listens        WHERE track_id = :t')->execute(['t' => $ids['track_id']]);
    $db->prepare('DELETE FROM tracks         WHERE id = :id')->execute(['id' => $ids['track_id']]);
    $db->prepare('DELETE FROM albums         WHERE id = :id')->execute(['id' => $ids['album_id']]);
    $db->prepare('DELETE FROM users          WHERE id IN (:l,:a)')->execute(['l' => $ids['listener_id'], 'a' => $ids['author_id']]);
}
