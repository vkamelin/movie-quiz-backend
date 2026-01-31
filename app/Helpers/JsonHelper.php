<?php

declare(strict_types=1);

namespace App\Helpers;

final class JsonHelper
{
    private function __construct()
    {
    }

    /**
     * @throws \JsonException
     */
    public static function encodePrompt(string $text): string
    {
        return json_encode(
            $text,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }
}
