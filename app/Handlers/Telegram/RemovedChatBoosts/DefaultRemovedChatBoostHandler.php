<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\RemovedChatBoosts;

use App\Helpers\Logger;
use App\Helpers\Push;
use App\Telegram\UpdateHelper;
use JsonException;
use Longman\TelegramBot\Entities\Update;

class DefaultRemovedChatBoostHandler extends AbstractRemovedChatBoostHandler
{
    public function handle(Update $update): void
    {
        $removedChatBoost = UpdateHelper::getRemovedChatBoost($update);
        if ($removedChatBoost === null) {
            return;
        }

        $chat = $removedChatBoost['chat'] ?? [];
        $boost = $removedChatBoost['boost'] ?? [];
        $reason = $removedChatBoost['reason'] ?? ($boost['reason'] ?? null);

        $chatId = isset($chat['id']) ? (int)$chat['id'] : null;
        $boostId = $boost['id'] ?? null;
        $removedAt = isset($boost['remove_date'])
            ? date('Y-m-d H:i:s', (int)$boost['remove_date'])
            : date('Y-m-d H:i:s');

        if ($chatId === null || $boostId === null) {
            return;
        }

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO chat_boosts_removed (id, chat_id, reason, removed_at, payload) '
                . 'VALUES (:id, :chat_id, :reason, :removed_at, :payload) '
                . 'ON DUPLICATE KEY UPDATE reason = VALUES(reason), removed_at = VALUES(removed_at), payload = VALUES(payload)'
            );
            $stmt->execute([
                'id' => $boostId,
                'chat_id' => $chatId,
                'reason' => $reason ? json_encode($reason, JSON_THROW_ON_ERROR) : null,
                'removed_at' => $removedAt,
                'payload' => json_encode($removedChatBoost, JSON_THROW_ON_ERROR),
            ]);
        } catch (JsonException $e) {
            Logger::error('Failed to save removed chat boost', ['exception' => $e]);
            return;
        }

        // Example: notify chat owner about removed boost
        $chatOwnerId = $chatId; // Replace with actual chat owner ID retrieval
        Push::text($chatOwnerId, 'Один из бустов вашего чата был снят 😔');
    }
}
