<?php

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

declare(strict_types=1);

namespace App\Helpers;

use Longman\TelegramBot\Request;
use PDO;

/**
 * Service for sending files to Telegram and storing file identifiers.
 */
final class FileService
{
    private PDO $db;
    private int $defaultChatId;

    public function __construct(?PDO $db = null, ?int $defaultChatId = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->defaultChatId = $defaultChatId ?? (int)($_ENV['TELEGRAM_DEFAULT_CHAT_ID'] ?? 0);
    }

    public function sendPhoto(string $path, ?int $chatId = null, array $params = []): ?string
    {
        return $this->send('sendPhoto', 'photo', $path, $chatId, $params);
    }

    public function sendDocument(string $path, ?int $chatId = null, array $params = []): ?string
    {
        return $this->send('sendDocument', 'document', $path, $chatId, $params);
    }

    public function sendAudio(string $path, ?int $chatId = null, array $params = []): ?string
    {
        return $this->send('sendAudio', 'audio', $path, $chatId, $params);
    }

    public function sendVideo(string $path, ?int $chatId = null, array $params = []): ?string
    {
        return $this->send('sendVideo', 'video', $path, $chatId, $params);
    }

    public function sendVoice(string $path, ?int $chatId = null, array $params = []): ?string
    {
        return $this->send('sendVoice', 'voice', $path, $chatId, $params);
    }

    /**
     * @param string      $method   Request method to call.
     * @param string      $paramKey Key in request payload representing the file.
     * @param string      $filePath Path to local file to send.
     * @param int|null    $chatId   Chat id or null to use default.
     * @param array       $params   Additional request parameters.
     *
     * @return string|null Extracted file id or null on failure.
     */
    private function send(string $method, string $paramKey, string $filePath, ?int $chatId, array $params): ?string
    {
        $chatId = $chatId ?? $this->defaultChatId;
        $payload = array_merge($params, [
            'chat_id' => $chatId,
            $paramKey => Request::encodeFile($filePath),
        ]);

        $response = Request::$method($payload);
        $ok = method_exists($response, 'isOk') ? $response->isOk() : ($response->ok ?? false);
        if (! $ok) {
            return null;
        }

        $result = method_exists($response, 'getResult') ? $response->getResult() : ($response->result ?? null);
        $fileId = $this->extractFileId($result, $paramKey);
        if ($fileId !== null) {
            $this->save($paramKey, $filePath, $fileId);
        }

        return $fileId;
    }

    /**
     * Extract file_id from Telegram API response result.
     */
    private function extractFileId(mixed $result, string $type): ?string
    {
        if ($result === null) {
            return null;
        }

        if ($type === 'photo') {
            $photos = null;
            if (is_object($result)) {
                if (method_exists($result, 'getPhoto')) {
                    $photos = $result->getPhoto();
                } elseif (isset($result->photo)) {
                    $photos = $result->photo;
                }
            } elseif (is_array($result) && isset($result['photo'])) {
                $photos = $result['photo'];
            }
            if (is_array($photos) && ! empty($photos)) {
                $photo = end($photos);
                if (is_object($photo)) {
                    return method_exists($photo, 'getFileId') ? $photo->getFileId() : ($photo->file_id ?? null);
                }
                if (is_array($photo)) {
                    return $photo['file_id'] ?? null;
                }
            }
            return null;
        }

        $item = null;
        if (is_object($result)) {
            $getter = 'get' . ucfirst($type);
            if (method_exists($result, $getter)) {
                $item = $result->$getter();
            } elseif (isset($result->$type)) {
                $item = $result->$type;
            }
        } elseif (is_array($result) && isset($result[$type])) {
            $item = $result[$type];
        }

        if ($item === null) {
            return null;
        }
        if (is_object($item)) {
            return method_exists($item, 'getFileId') ? $item->getFileId() : ($item->file_id ?? null);
        }
        if (is_array($item)) {
            return $item['file_id'] ?? null;
        }
        return null;
    }

    /**
     * Save file information to database.
     */
    private function save(string $type, string $path, string $fileId): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO `telegram_files` (type, original_name, mime_type, size, file_id)'
            . ' VALUES (:type, :original_name, :mime_type, :size, :file_id)'
        );
        $stmt->execute([
            'type' => $type,
            'original_name' => basename($path),
            'mime_type' => mime_content_type($path) ?: '',
            'size' => filesize($path) ?: 0,
            'file_id' => $fileId,
        ]);
    }
}
