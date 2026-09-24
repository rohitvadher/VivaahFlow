<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Database\Connection;

class ImageEngine
{
    private const MIME_MAP = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const MAX_IMAGE_DIMENSION = 8000;

    public static function validateUpload(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return 'The file could not be uploaded.';
        }
        if (($file['size'] ?? 0) === 0) {
            return 'The uploaded file is empty.';
        }
        $maxBytes = (int)app_config('security.upload_max_bytes', 3145728);
        if ($file['size'] > $maxBytes) {
            return 'The image must not exceed ' . round($maxBytes / 1048576, 1) . ' MB.';
        }
        $mime = self::detectMime($file['tmp_name'] ?? '');
        if (!isset(self::MIME_MAP[$mime])) {
            return 'Only JPG, PNG and WEBP images are allowed.';
        }
        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $allowed = app_config('security.allowed_image_extensions', ['jpg', 'jpeg', 'png', 'webp']);
        if (!in_array($extension, $allowed, true)) {
            return 'Only JPG, PNG and WEBP images are allowed.';
        }
        $size = @getimagesize($file['tmp_name']);
        if ($size === false) {
            return 'The uploaded file is not a valid image.';
        }
        if ($size[0] > self::MAX_IMAGE_DIMENSION || $size[1] > self::MAX_IMAGE_DIMENSION) {
            return 'The image dimensions are too large.';
        }
        return null;
    }

    private static function detectMime(string $path): string
    {
        if (!is_file($path)) {
            return '';
        }
        $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
        return is_string($mime) ? strtolower($mime) : '';
    }

    public static function extension(array $file): string
    {
        $mime = self::detectMime($file['tmp_name'] ?? '');
        $mapped = self::MIME_MAP[$mime] ?? null;
        if ($mapped !== null) {
            return $mapped;
        }
        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg'], true)) {
            return 'jpg';
        }
        if (in_array($extension, ['png', 'webp'], true)) {
            return $extension;
        }
        return 'jpg';
    }

    public static function pathFor(string $module, string $name): string
    {
        return 'uploads/' . trim($module, '/') . '/' . $name;
    }

    public static function absolutePath(string $relativePath): string
    {
        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');
        return rtrim(str_replace('\\', '/', ROOT_PATH), '/') . '/' . $normalized;
    }

    public static function uniqueName(string $label, string $module, string $extension): string
    {
        $slug = Str::slugify($label);
        $counter = 1;
        while (true) {
            $name = sprintf('%s-%02d.%s', $slug, $counter, $extension);
            $absolute = self::absolutePath(self::pathFor($module, $name));
            if (!is_file($absolute)) {
                return $name;
            }
            $counter++;
        }
    }

    public static function store(array $file, string $module, string $label): array
    {
        $module = trim(preg_replace('/[^a-z0-9_-]/i', '', $module) ?? '', '/');
        if ($module === '') {
            return ['path' => null, 'error' => 'Invalid upload destination.'];
        }
        $error = self::validateUpload($file);
        if ($error !== null) {
            return ['path' => null, 'error' => $error];
        }
        $directory = self::absolutePath('uploads/' . $module);
        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }
        if (!is_dir($directory)) {
            return ['path' => null, 'error' => 'Unable to create the upload directory.'];
        }
        $extension = self::extension($file);
        $name = self::uniqueName($label, $module, $extension);
        $destination = $directory . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['path' => null, 'error' => 'The file could not be saved.'];
        }
        return ['path' => self::pathFor($module, $name), 'error' => null];
    }

    public static function replace(?array $file, string $module, string $label, ?string $oldPath): array
    {
        if ($file === null) {
            return ['path' => $oldPath, 'error' => null];
        }
        $result = self::store($file, $module, $label);
        if ($result['error'] !== null) {
            return $result;
        }
        if ($oldPath !== null && $oldPath !== $result['path']) {
            self::deleteIfUnused($oldPath);
        }
        return $result;
    }

    public static function deleteIfUnused(string $relativePath): void
    {
        $path = ltrim($relativePath, '/');
        $queries = [
            "SELECT COUNT(*) FROM services WHERE image = :path",
            "SELECT COUNT(*) FROM service_images WHERE image_path = :path",
            "SELECT COUNT(*) FROM packages WHERE cover_image = :path",
            "SELECT COUNT(*) FROM settings WHERE setting_key = 'logo_path' AND setting_value = :path",
        ];
        foreach ($queries as $query) {
            $count = (int)Connection::fetchColumn($query, ['path' => $path]);
            if ($count > 0) {
                return;
            }
        }
        self::deleteIfExists($relativePath);
    }

    public static function deleteIfExists(string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }
        if (!self::isUploadsPath($relativePath)) {
            error_log('[UPLOAD] refused to delete outside uploads/: ' . $relativePath);
            return;
        }
        $absolute = self::absolutePath($relativePath);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    /**
     * Containment guard: only paths inside uploads/ may be deleted.
     * Rejects traversal (../), absolute paths and stream wrappers.
     */
    private static function isUploadsPath(string $relativePath): bool
    {
        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');
        if ($normalized === '' || str_starts_with($normalized, '/')) {
            return false;
        }
        if (str_contains($normalized, ':') || str_starts_with($normalized, 'php://') || str_starts_with($normalized, 'data:')) {
            return false;
        }
        $segments = explode('/', $normalized);
        if ($segments[0] !== 'uploads') {
            return false;
        }
        foreach ($segments as $segment) {
            if ($segment === '..' || $segment === '.') {
                return false;
            }
        }
        return true;
    }
}