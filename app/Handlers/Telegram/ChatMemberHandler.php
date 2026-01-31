<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\ChatMembers\DefaultChatMemberHandler;
use Longman\TelegramBot\Entities\Update;

class ChatMemberHandler
{
    /**
     * Метод для обработки обновления chat_member
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        $handler = new DefaultChatMemberHandler();
        $handler->handle($update);
    }
}
