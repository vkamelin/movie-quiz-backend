<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\ChannelPosts;

use App\Helpers\Logger;
use JsonException;
use Longman\TelegramBot\Entities\Update;

class DefaultChannelPostHandler extends AbstractChannelPostHandler
{
    public function handle(Update $update): void
    {
        $message = $update->getChannelPost() ?? $update->getEditedChannelPost();
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
        $forwardFromChat = $raw['forward_from_chat'] ?? null;
        $linkPreview = $raw['link_preview_options'] ?? null;
        $isAutomaticForward = (bool)($raw['is_automatic_forward'] ?? false);

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO telegram_channel_posts (chat_id, message_id, `date`, author_signature, text, caption, entities, media, is_automatic_forward, forward_from_chat, link_preview_options) '
                . 'VALUES (:chat_id, :message_id, :date, :author_signature, :text, :caption, :entities, :media, :is_automatic_forward, :forward_from_chat, :link_preview_options)'
            );

            $stmt->execute([
                'chat_id' => $message->getChat()->getId(),
                'message_id' => $message->getMessageId(),
                'date' => date('Y-m-d H:i:s', $message->getDate()),
                'author_signature' => $message->getAuthorSignature(),
                'text' => $text,
                'caption' => $caption,
                'entities' => $entities ? json_encode($entities, JSON_THROW_ON_ERROR) : null,
                'media' => $media ? json_encode($media, JSON_THROW_ON_ERROR) : null,
                'is_automatic_forward' => $isAutomaticForward ? 1 : 0,
                'forward_from_chat' => $forwardFromChat ? json_encode($forwardFromChat, JSON_THROW_ON_ERROR) : null,
                'link_preview_options' => $linkPreview ? json_encode($linkPreview, JSON_THROW_ON_ERROR) : null,
            ]);
        } catch (JsonException $e) {
            Logger::error('Failed to save channel post', ['exception' => $e]);
            return;
        }

        $content = $text ?? $caption;
        if ($content !== null) {
            $this->analyseContent($content);
        }
    }

    private function analyseContent(string $content): void
    {
        if (stripos($content, 'spam') !== false) {
            Logger::warning('Channel post contains potential spam');
        }
    }
}
