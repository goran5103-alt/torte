<?php
declare(strict_types=1);

namespace App\Services;

/** Čuvanje slika u public_html/uploads/{shopId}/... sa proverom da je fajl zaista slika. */
final class Uploads
{
    private const MAX_BYTES = 8 * 1024 * 1024;
    private const TYPES = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    public static function dir(int $shopId, string $sub): string
    {
        $dir = PUBLIC_DIR . '/uploads/' . $shopId . '/' . $sub;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Snima uploadovanu sliku iz $_FILES[$field].
     * @return string putanja relativno od uploads/ (npr. "3/gallery/ab12.jpg")
     * @throws \RuntimeException sa porukom za korisnika
     */
    public static function saveImage(array $file, int $shopId, string $sub, int $maxWidth = 1600): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Slika nije uploadovana.');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new \RuntimeException('Slika je prevelika (najviše 8 MB).');
        }
        $info = @getimagesize($file['tmp_name']);
        if (!$info || !isset(self::TYPES[$info[2]])) {
            throw new \RuntimeException('Dozvoljene su samo JPG, PNG i WEBP slike.');
        }
        $ext = self::TYPES[$info[2]];
        $name = random_token(10) . '.' . $ext;
        $target = self::dir($shopId, $sub) . '/' . $name;

        // Ponovo zapisujemo sliku kroz GD: uklanja metapodatke i smanjuje prevelike slike.
        if (!self::resample($file['tmp_name'], $target, $info, $maxWidth)) {
            if (!move_uploaded_file($file['tmp_name'], $target)) {
                throw new \RuntimeException('Snimanje slike nije uspelo.');
            }
        }
        return $shopId . '/' . $sub . '/' . $name;
    }

    /** Snima sliku iz bajtova (AI generisane slike). */
    public static function saveBytes(string $bytes, int $shopId, string $sub, string $ext = 'png'): string
    {
        $name = random_token(10) . '.' . $ext;
        file_put_contents(self::dir($shopId, $sub) . '/' . $name, $bytes);
        return $shopId . '/' . $sub . '/' . $name;
    }

    public static function delete(?string $path): void
    {
        if (!$path || str_contains($path, '..')) {
            return;
        }
        $file = PUBLIC_DIR . '/uploads/' . $path;
        if (is_file($file)) {
            unlink($file);
        }
    }

    public static function deleteShopFiles(int $shopId): void
    {
        $dir = PUBLIC_DIR . '/uploads/' . $shopId;
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($dir);
    }

    private static function resample(string $source, string $target, array $info, int $maxWidth): bool
    {
        if (!extension_loaded('gd')) {
            return false;
        }
        $img = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
            default => false,
        };
        if (!$img) {
            return false;
        }
        [$w, $h] = [$info[0], $info[1]];
        if ($w > $maxWidth) {
            $newH = (int)round($h * $maxWidth / $w);
            $resized = imagecreatetruecolor($maxWidth, $newH);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $maxWidth, $newH, $w, $h);
            imagedestroy($img);
            $img = $resized;
        }
        $ok = match ($info[2]) {
            IMAGETYPE_JPEG => imagejpeg($img, $target, 85),
            IMAGETYPE_PNG => imagepng($img, $target, 6),
            IMAGETYPE_WEBP => imagewebp($img, $target, 85),
        };
        imagedestroy($img);
        return $ok;
    }
}
