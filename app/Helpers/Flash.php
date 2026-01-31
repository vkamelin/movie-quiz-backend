<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Session based flash messages.
 */
final class Flash
{
    private const KEY = '_flash';

    /**
     * Add flash message.
     *
     * @param string $type    Message type (success, info, error, warning)
     * @param string $message Message text
     */
    public static function add(string $type, string $message): void
    {
        $_SESSION[self::KEY][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Retrieve and remove flash messages.
     *
     * @return array<array{type:string,message:string}>
     */
    public static function get(): array
    {
        $msgs = $_SESSION[self::KEY] ?? [];
        unset($_SESSION[self::KEY]);
        return $msgs;
    }
}
