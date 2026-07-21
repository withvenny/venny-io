<?php

declare(strict_types=1);

namespace VennyIO\Support;

final class Files
{
    public static function sanitizeFilename(string $filename): string
    {
        $filename = trim($filename);
        $filename = basename($filename);
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?? 'asset';
        $filename = preg_replace('/-+/', '-', $filename) ?? 'asset';
        $filename = trim($filename, '.-_');

        if ($filename === '') {
            return 'asset';
        }

        return strtolower(substr($filename, 0, 180));
    }

    public static function extensionFromFilename(string $filename): ?string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return $extension !== '' ? substr($extension, 0, 20) : null;
    }

    public static function normalizeMimeType(?string $mimeType): string
    {
        $mimeType = strtolower(trim((string) $mimeType));
        if ($mimeType === '' || !str_contains($mimeType, '/')) {
            return 'application/octet-stream';
        }

        return substr($mimeType, 0, 255);
    }

    public static function parseJsonObject($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new \InvalidArgumentException('asset_attributes must be a valid JSON object');
        }

        return $decoded;
    }

    public static function positiveInt($value, string $field, ?int $max = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            throw new \InvalidArgumentException($field . ' must be numeric');
        }

        $int = (int) $value;
        if ($int < 0) {
            throw new \InvalidArgumentException($field . ' must be greater than or equal to zero');
        }

        if ($max !== null && $int > $max) {
            throw new \InvalidArgumentException($field . ' exceeds the maximum allowed upload size');
        }

        return $int;
    }

    public static function appSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        if ($slug === '') {
            throw new \InvalidArgumentException('app_slug could not be resolved');
        }

        return $slug;
    }
}
