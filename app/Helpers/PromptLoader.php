<?php

/**
 * Copyright (c) 2025. Vitaliy Kamelin <v.kamelin@gmail.com>
 */

declare(strict_types=1);

namespace App\Helpers;

use JsonException;
use RuntimeException;

class PromptLoader
{
    public function load(string $filename): string
    {
        if (!is_file($filename)) {
            Logger::error('Prompt file missing', ['file' => $filename]);
            throw new RuntimeException("Prompt file not found: {$filename}");
        }

        $contents = file_get_contents($filename);
        if ($contents === false) {
            Logger::error('Prompt file read failed', ['file' => $filename]);
            throw new RuntimeException("Prompt file could not be read: {$filename}");
        }

        try {
            return JsonHelper::encodePrompt($contents);
        } catch (JsonException $e) {
            Logger::error('Prompt JSON encoding failed', [
                'file' => $filename,
                'message' => $e->getMessage(),
            ]);
            throw new RuntimeException($e->getMessage(), $e);
        }
    }
}
