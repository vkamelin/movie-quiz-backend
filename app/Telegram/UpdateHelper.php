<?php

declare(strict_types=1);

namespace App\Telegram;

use Longman\TelegramBot\Entities\Update;

/**
 * Helper methods for extracting raw update data for unsupported update types.
 */
final class UpdateHelper
{
    private function __construct()
    {
    }

    /**
     * Get message reaction update data.
     */
    public static function getMessageReaction(Update $update): ?array
    {
        return $update->getRawData()['message_reaction'] ?? null;
    }

    /**
     * Get message reaction count update data.
     */
    public static function getMessageReactionCount(Update $update): ?array
    {
        return $update->getRawData()['message_reaction_count'] ?? null;
    }

    /**
     * Get chat boost update data.
     */
    public static function getChatBoost(Update $update): ?array
    {
        return $update->getRawData()['chat_boost'] ?? null;
    }

    /**
     * Get removed chat boost update data.
     */
    public static function getRemovedChatBoost(Update $update): ?array
    {
        return $update->getRawData()['removed_chat_boost'] ?? null;
    }

    public static function getUserId(Update $update): ?int
    {
        $sources = [
            ['method' => 'getMessage', 'getFrom' => true],
            ['method' => 'getEditedMessage', 'getFrom' => true],
            ['method' => 'getChannelPost', 'getFrom' => true],
            ['method' => 'getEditedChannelPost', 'getFrom' => true],
            ['method' => 'getInlineQuery', 'getFrom' => true],
            ['method' => 'getChosenInlineResult', 'getFrom' => true],
            ['method' => 'getCallbackQuery', 'getFrom' => true],
            ['method' => 'getShippingQuery', 'getFrom' => true],
            ['method' => 'getPreCheckoutQuery', 'getFrom' => true],
            ['method' => 'getPollAnswer', 'getUser' => true],
            ['method' => 'getMyChatMember', 'getFrom' => true],
            ['method' => 'getChatMember', 'getFrom' => true],
            ['method' => 'getChatJoinRequest', 'getFrom' => true],
        ];

        foreach ($sources as $source) {
            $object = $update->{$source['method']}();
            if ($object) {
                if (($source['getFrom'] ?? false) && $object->getFrom()) {
                    return (int)$object->getFrom()->getId();
                }
                if (($source['getUser'] ?? false) && $object->getUser()) {
                    return (int)$object->getUser()->getId();
                }
            }
        }

        $reaction = self::getMessageReaction($update);
        if ($reaction !== null && isset($reaction['user']['id'])) {
            return (int)$reaction['user']['id'];
        }

        return null;
    }
}
