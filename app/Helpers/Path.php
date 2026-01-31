<?php

declare(strict_types=1);

namespace App\Helpers;

final class Path
{
    private function __construct()
    {
    }

    public static function base(string $path = ''): string
    {
        $baseDir = __DIR__ . '/../../';
        return $baseDir . ($path !== '' ? '/' . ltrim($path, '/\\') : '');
    }
}
