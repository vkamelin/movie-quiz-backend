<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\EditedChannelPosts;

use App\Helpers\Logger;
use JsonException;
use Longman\TelegramBot\Entities\Update;

class DefaultEditedChannelPostHandler extends AbstractEditedChannelPostHandler
{
    public function handle(Update $update): void
    {
        $message = $update->getEditedChannelPost();
        if ($message === null) {
            return;
        }

        $raw = $message->getRawData();

        $text = $message->getText();
        $caption = $message->getCaption();

        $media = [];
        foreach (['photo', 'video', 'animation', 'audio', 'voice', 'document', 'video_note', 'sticker'] as $key) {
            if (isset($raw[$key])) {
                $media[$key] = $raw[$key];
            }
        }
        $media = $media === [] ? null : $media;

        $entities = $raw['entities'] ?? null;

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO message_edits (chat_id, message_id, editor_user_id, edit_date, new_text, new_caption, entities, media, is_channel) '
                . 'VALUES (:chat_id, :message_id, :editor_user_id, :edit_date, :new_text, :new_caption, :entities, :media, 1)'
            );

            $stmt->execute([
                'chat_id' => $message->getChat()->getId(),
                'message_id' => $message->getMessageId(),
                'editor_user_id' => $message->getFrom()?->getId(),
                'edit_date' => date('Y-m-d H:i:s', $message->getEditDate() ?? time()),
                'new_text' => $text,
                'new_caption' => $caption,
                'entities' => $entities ? json_encode($entities, JSON_THROW_ON_ERROR) : null,
                'media' => $media ? json_encode($media, JSON_THROW_ON_ERROR) : null,
            ]);
        } catch (JsonException $e) {
            Logger::error('Failed to save edited channel post', ['exception' => $e]);
            return;
        }

        $this->notifyAuditLog(
            (int) $message->getChat()->getId(),
            (int) $message->getMessageId(),
            $text,
            $caption
        );
    }

    private function notifyAuditLog(int $chatId, int $messageId, ?string $text, ?string $caption): void
    {
        $content = $text ?? $caption;
        Logger::info('Channel post edited', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'content' => $content,
        ]);
    }
}
