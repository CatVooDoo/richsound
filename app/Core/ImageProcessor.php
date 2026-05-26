<?php

declare(strict_types=1);

namespace App\Core;

final class ImageProcessor
{
    /**
     * Resize image to fit within maxW×maxH (preserving aspect ratio) and save as JPEG.
     * Returns false on failure so the caller can fall back to move_uploaded_file().
     */
    public static function resizeAndSave(
        string $tmpPath,
        string $destPath,
        int    $maxW,
        int    $maxH,
        int    $quality = 85
    ): bool {
        if (!function_exists('imagecreatefromjpeg')) {
            return false;
        }

        $mime = @mime_content_type($tmpPath);

        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmpPath),
            'image/png'  => @imagecreatefrompng($tmpPath),
            'image/webp' => @imagecreatefromwebp($tmpPath),
            default      => false,
        };

        if ($src === false) {
            return false;
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        [$newW, $newH] = self::scaleDown($origW, $origH, $maxW, $maxH);

        $dst = imagescale($src, $newW, $newH, IMG_BICUBIC);
        imagedestroy($src);

        if ($dst === false) {
            return false;
        }

        $result = imagejpeg($dst, $destPath, $quality);
        imagedestroy($dst);

        return $result;
    }

    /** @return array{int, int} */
    private static function scaleDown(int $w, int $h, int $maxW, int $maxH): array
    {
        if ($w <= $maxW && $h <= $maxH) {
            return [$w, $h];
        }

        $ratio = min($maxW / $w, $maxH / $h);

        return [max(1, (int) round($w * $ratio)), max(1, (int) round($h * $ratio))];
    }
}
