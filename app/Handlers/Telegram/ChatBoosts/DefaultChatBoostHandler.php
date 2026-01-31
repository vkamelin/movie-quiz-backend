<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\ChatBoosts;

use App\Helpers\Logger;
use App\Helpers\Push;
use App\Telegram\UpdateHelper;
use JsonException;
use Longman\TelegramBot\Entities\Update;

class DefaultChatBoostHandler extends AbstractChatBoostHandler
{
    public function handle(Update $update): void
    {
        $chatBoost = UpdateHelper::getChatBoost($update);
        if ($chatBoost === null) {
            return;
        }

        $chat = $chatBoost['chat'] ?? [];
        $boost = $chatBoost['boost'] ?? [];

        $chatId = isset($chat['id']) ? (int)$chat['id'] : null;
        $boostId = $boost['id'] ?? null;
        $source = $boost['source'] ?? null;
        $startAt = isset($boost['add_date']) ? date('Y-m-d H:i:s', (int)$boost['add_date']) : null;
        $endAt = isset($boost['expiration_date']) ? date('Y-m-d H:i:s', (int)$boost['expiration_date']) : null;

        if ($chatId === null || $boostId === null) {
            return;
        }

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO chat_boosts (id, chat_id, source, start_at, end_at, payload, received_at) '
                . 'VALUES (:id, :chat_id, :source, :start_at, :end_at, :payload, NOW()) '
                . 'ON DUPLICATE KEY UPDATE source = VALUES(source), start_at = VALUES(start_at), '
                . 'end_at = VALUES(end_at), payload = VALUES(payload), received_at = VALUES(received_at)'
            );
            $stmt->execute([
                'id' => $boostId,
                'chat_id' => $chatId,
                'source' => $source ? json_encode($source, JSON_THROW_ON_ERROR) : null,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'payload' => json_encode($chatBoost, JSON_THROW_ON_ERROR),
            ]);
        } catch (JsonException $e) {
            Logger::error('Failed to save chat boost', ['exception' => $e]);
            return;
        }

        // Example of notifying chat about new boost
        Push::text($chatId, 'Чат получил буст! Спасибо за поддержку 🙌');
    }
}
