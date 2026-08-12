<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class RecipeImageUploader
{
    private const MAX_BYTES = 5242880;

    /**
     * @param array<string, mixed> $file
     */
    public function upload(array $file): ?string
    {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK || !isset($file['tmp_name'], $file['size'])) {
            throw new RuntimeException('Bilden kunde inte laddas upp.');
        }

        if ((int) $file['size'] < 1 || (int) $file['size'] > self::MAX_BYTES) {
            throw new RuntimeException('Bilden får vara högst 5 MB.');
        }

        $imageInfo = @getimagesize((string) $file['tmp_name']);
        if ($imageInfo === false) {
            throw new RuntimeException('Den uppladdade filen är inte en giltig bild.');
        }

        $extensions = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
        ];
        $imageType = $imageInfo[2];

        if (!isset($extensions[$imageType])) {
            throw new RuntimeException('Endast JPG, PNG och WebP-bilder är tillåtna.');
        }

        $directory = BASE_PATH . '/public/uploads/recipes';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Bildmappen kunde inte skapas.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$imageType];
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            throw new RuntimeException('Bilden kunde inte sparas.');
        }

        return 'uploads/recipes/' . $filename;
    }

    public function delete(?string $relativePath): void
    {
        if ($relativePath === null || strpos($relativePath, 'uploads/recipes/') !== 0) {
            return;
        }

        $path = BASE_PATH . '/public/' . $relativePath;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
