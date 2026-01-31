<?php

declare(strict_types=1);

if (!function_exists('url')) {
    /**
     * Добавляет базовый путь к переданному URI.
     * Если передан null — возвращает только базовый путь.
     */
    function url(?string $path): string
    {
        $base = rtrim($_ENV['BASE_PATH'] ?? '', '/');
        $normalized = $path === null ? '' : ltrim($path, '/');
        return $base . '/' . $normalized;
    }
}
