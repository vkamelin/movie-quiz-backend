<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Helpers\Logger;
use App\Helpers\RedisHelper;
use Longman\TelegramBot\Entities\Update;
use Redis;
use RedisException;
use Throwable;

/**
 * Filters incoming Telegram updates based on allow/deny lists.
 */
final class UpdateFilter
{
    /** @var array<int, string> */
    private array $allowUpdates = [];
    /** @var array<int, string> */
    private array $denyUpdates = [];
    /** @var array<int, string> */
    private array $allowChats = [];
    /** @var array<int, string> */
    private array $denyChats = [];
    /** @var array<int, string> */
    private array $allowCommands = [];
    /** @var array<int, string> */
    private array $denyCommands = [];

    private int $debounceSeconds;
    private ?Redis $redis;
    private string $redisPrefix;

    /** @var array<string, int> */
    private static array $lastLog = [];

    public function __construct(?Redis $redis = null, string $redisPrefix = 'tg:filters', int $debounceSeconds = 60)
    {
        $this->debounceSeconds = $debounceSeconds;
        $this->redis = $redis;
        $this->redisPrefix = rtrim($redisPrefix, ':');
        $this->loadFilters();
    }

    private function loadFilters(): void
    {
        $redis = $this->redis;

        if ($_ENV['TG_FILTERS_FROM_REDIS'] ?? '') {
            if ($redis === null) {
                try {
                    $redis = RedisHelper::getInstance();
                } catch (RedisException) {
                    $redis = null;
                }
            }

            if ($redis instanceof Redis) {
                try {
                    $prefix = $this->redisPrefix;
                    $this->allowUpdates = $this->normalizeList($redis->sMembers("{$prefix}:allow_updates"));
                    $this->denyUpdates = $this->normalizeList($redis->sMembers("{$prefix}:deny_updates"));
                    $this->allowChats = $this->normalizeList($redis->sMembers("{$prefix}:allow_chats"));
                    $this->denyChats = $this->normalizeList($redis->sMembers("{$prefix}:deny_chats"));
                    $this->allowCommands = $this->normalizeList($redis->sMembers("{$prefix}:allow_commands"));
                    $this->denyCommands = $this->normalizeList($redis->sMembers("{$prefix}:deny_commands"));
                    return;
                } catch (RedisException|Throwable $e) {
                    $this->debouncedLog('redis_unreachable');
                }
            }
        }

        $this->allowUpdates = $this->normalizeList($_ENV['TG_ALLOW_UPDATES'] ?? '');
        $this->denyUpdates = $this->normalizeList($_ENV['TG_DENY_UPDATES'] ?? '');
        $this->allowChats = $this->normalizeList($_ENV['TG_ALLOW_CHATS'] ?? '');
        $this->denyChats = $this->normalizeList($_ENV['TG_DENY_CHATS'] ?? '');
        $this->allowCommands = $this->normalizeList($_ENV['TG_ALLOW_COMMANDS'] ?? '');
        $this->denyCommands = $this->normalizeList($_ENV['TG_DENY_COMMANDS'] ?? '');
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private function normalizeList(mixed $value): array
    {
        if (is_array($value)) {
            $items = array_map('strval', $value);
        } elseif (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return [];
            }
            $items = array_map('trim', explode(',', $value));
        } else {
            return [];
        }

        $items = array_filter($items, static fn (string $v): bool => $v !== '');
        return array_values($items);
    }

    /**
     * Determine if an update should be processed.
     *
     * @param Update      $update
     * @param string|null $reason Optional skip reason output
     * @return bool
     */
    public function shouldProcess(Update $update, ?string &$reason = null): bool
    {
        [$type, $chatId, $command] = $this->extract($update);

        if (!empty($this->allowUpdates) && !in_array($type, $this->allowUpdates, true)) {
            $reason = 'update_not_allowed';
            $this->debouncedLog($reason);
            return false;
        }

        if (in_array($type, $this->denyUpdates, true) && !in_array($type, $this->allowUpdates, true)) {
            $reason = 'update_denied';
            $this->debouncedLog($reason);
            return false;
        }

        if ($chatId !== null) {
            $chat = (string)$chatId;
            if (!empty($this->allowChats) && !in_array($chat, $this->allowChats, true)) {
                $reason = 'chat_not_allowed';
                $this->debouncedLog($reason);
                return false;
            }

            if (in_array($chat, $this->denyChats, true) && !in_array($chat, $this->allowChats, true)) {
                $reason = 'chat_denied';
                $this->debouncedLog($reason);
                return false;
            }
        }

        if ($command !== null) {
            if (!empty($this->allowCommands) && !in_array($command, $this->allowCommands, true)) {
                $reason = 'command_not_allowed';
                $this->debouncedLog($reason);
                return false;
            }

            if (in_array($command, $this->denyCommands, true) && !in_array($command, $this->allowCommands, true)) {
                $reason = 'command_denied';
                $this->debouncedLog($reason);
                return false;
            }
        }

        return true;
    }

    /**
     * Extract update type, chat ID and command from update.
     *
     * @param Update $update
     * @return array{0:string,1:int|null,2:string|null}
     */
    private function extract(Update $update): array
    {
        $type = $update->getUpdateType();
        $chatId = null;
        $command = null;

        $message = $update->getMessage()
            ?? $update->getEditedMessage()
            ?? $update->getChannelPost()
            ?? $update->getEditedChannelPost()
            ?? $update->getCallbackQuery()?->getMessage();

        if ($message !== null) {
            $chatId = $message->getChat()?->getId();
            $text = $message->getText();
            if (is_string($text) && str_starts_with($text, '/')) {
                $command = ltrim(strtok($text, ' '), '/');
            }
        }

        return [$type, $chatId, $command];
    }

    private function debouncedLog(string $reason): void
    {
        $now = time();
        $last = self::$lastLog[$reason] ?? 0;
        if ($now - $last >= $this->debounceSeconds) {
            Logger::info('Update skipped', ['reason' => $reason]);
            self::$lastLog[$reason] = $now;
        }
    }
}
