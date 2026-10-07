<?php
declare(strict_types=1);

namespace App\Domain;

/** Subida de imágenes: valida, reduce a 1920px y convierte a WebP si GD lo permite. */
final class Images
{
    /** @return string|null nombre de archivo guardado en public/uploads, o null si no es válido */
    public static function store(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }
        if ($file['size'] > 8 * 1024 * 1024) {
            return null;
        }
        $info = @getimagesize($file['tmp_name']);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }
        $dir = ROOT . '/public/uploads';
        $base = bin2hex(random_bytes(8));
        if (function_exists('imagewebp')) {
            $img = match ($info[2]) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($file['tmp_name']),
                IMAGETYPE_PNG => @imagecreatefrompng($file['tmp_name']),
                default => @imagecreatefromwebp($file['tmp_name']),
            };
            if ($img) {
                $w = imagesx($img);
                if ($w > 1920) {
                    $img = imagescale($img, 1920);
                }
                imagepalettetotruecolor($img);
                imagesavealpha($img, true);
                $name = $base . '.webp';
                $ok = imagewebp($img, $dir . '/' . $name, 82);
                imagedestroy($img);
                return $ok ? $name : null;
            }
        }
        $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2]];
        $name = $base . '.' . $ext;
        return move_uploaded_file($file['tmp_name'], $dir . '/' . $name) ? $name : null;
    }
}
