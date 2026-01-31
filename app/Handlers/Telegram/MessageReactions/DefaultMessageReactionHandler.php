<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\MessageReactions;

use App\Helpers\Logger;
use App\Telegram\UpdateHelper;
use JsonException;
use Longman\TelegramBot\Entities\Update;

class DefaultMessageReactionHandler extends AbstractMessageReactionHandler
{
    public function handle(Update $update): void
    {
        $reaction = UpdateHelper::getMessageReaction($update);
        if ($reaction === null) {
            return;
        }

        $chatId = isset($reaction['chat']['id']) ? (int)$reaction['chat']['id'] : null;
        $messageId = isset($reaction['message_id']) ? (int)$reaction['message_id'] : null;
        $userId = isset($reaction['user']['id']) ? (int)$reaction['user']['id'] : null;
        $newReaction = $reaction['new_reaction'] ?? [];

        if ($chatId === null || $messageId === null || $userId === null) {
            return;
        }

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO message_reactions (chat_id, message_id, user_id, reactions, updated_at) '
                . 'VALUES (:chat_id, :message_id, :user_id, :reactions, NOW()) '
                . 'ON DUPLICATE KEY UPDATE reactions = VALUES(reactions), updated_at = VALUES(updated_at)'
            );
            $stmt->execute([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'user_id' => $userId,
                'reactions' => json_encode($newReaction, JSON_THROW_ON_ERROR),
            ]);
        } catch (JsonException $e) {
            Logger::error('Failed to save message reaction', ['exception' => $e]);
            return;
        }

        // Example: update aggregated reaction counter for the first reaction
        if (!empty($newReaction)) {
            $emoji = $newReaction[0]['emoji'] ?? ($newReaction[0]['custom_emoji_id'] ?? null);
            if ($emoji !== null) {
                $stmt = $this->db->prepare(
                    'INSERT INTO message_reactions_agg (chat_id, message_id, agg, updated_at) '
                    . 'VALUES (:chat_id, :message_id, JSON_OBJECT(:emoji, 1), NOW()) '
                    . "ON DUPLICATE KEY UPDATE agg = JSON_SET(agg, CONCAT('$.', :emoji), COALESCE(JSON_EXTRACT(agg, CONCAT('$.', :emoji)), 0) + 1), updated_at = NOW()"
                );
                $stmt->execute([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'emoji' => $emoji,
                ]);
            }
        }
    }
}
