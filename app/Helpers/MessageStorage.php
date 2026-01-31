<?php

declare(strict_types=1);

namespace App\Helpers;

use Exception;
use RuntimeException;

final class MessageStorage
{
    private function __construct()
    {
    }

    public static function save(string $filename, string $message): void
    {
        try {
            $filepath = Path::base("app/Messages/{$filename}.html");

            if (!is_dir(Path::base('app/Messages')) && !mkdir(
                $concurrentDirectory = Path::base('app/Messages'),
                0755,
                true
            ) && !is_dir($concurrentDirectory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }

            $file = fopen($filepath, 'wb');

            if ($file) {
                fwrite($file, $message);
                fclose($file);
            } else {
                throw new RuntimeException("Не удалось открыть файл {$filename} для записи.");
            }
        } catch (Exception $e) {
            Logger::error($e->getMessage());
        }
    }

    public static function read(string $filename): ?string
    {
        try {
            $filepath = Path::base("app/Messages/{$filename}.html");

            if (!file_exists($filepath)) {
                throw new RuntimeException("Файл {$filename}.html не найден.");
            }

            return file_get_contents($filepath);
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            return null;
        }
    }
}
