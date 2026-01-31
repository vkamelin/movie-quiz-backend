<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\MessageReactionCounts;

use App\Helpers\Logger;
use App\Telegram\UpdateHelper;
use JsonException;
use Longman\TelegramBot\Entities\Update;

class DefaultMessageReactionCountHandler extends AbstractMessageReactionCountHandler
{
    public function handle(Update $update): void
    {
        $reaction = UpdateHelper::getMessageReactionCount($update);
        if ($reaction === null) {
            return;
        }

        $chatId = isset($reaction['chat']['id']) ? (int)$reaction['chat']['id'] : null;
        $messageId = isset($reaction['message_id']) ? (int)$reaction['message_id'] : null;
        $reactions = $reaction['reactions'] ?? [];

        if ($chatId === null || $messageId === null) {
            return;
        }

        $agg = [];
        foreach ($reactions as $r) {
            $emoji = $r['type']['emoji'] ?? ($r['type']['custom_emoji_id'] ?? null);
            $count = $r['count'] ?? null;
            if ($emoji !== null && $count !== null) {
                $agg[(string)$emoji] = (int)$count;
            }
        }

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO message_reactions_agg (chat_id, message_id, agg, updated_at) '
                . 'VALUES (:chat_id, :message_id, :agg, NOW()) '
                . 'ON DUPLICATE KEY UPDATE agg = VALUES(agg), updated_at = VALUES(updated_at)'
            );
            $stmt->execute([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'agg' => json_encode($agg, JSON_THROW_ON_ERROR),
            ]);
        } catch (JsonException $e) {
            Logger::error('Failed to save aggregated message reactions', ['exception' => $e]);
            return;
        }

        // Example: simple analysis of reactions — determine the leading emoji
        if (!empty($agg)) {
            arsort($agg);
            $leaderEmoji = array_key_first($agg);
            $leaderCount = $agg[$leaderEmoji];
            Logger::info('Top reaction emoji', ['emoji' => $leaderEmoji, 'count' => $leaderCount]);
        }
    }
}
