<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\MyChatMembers\DefaultMyChatMemberHandler;
use Longman\TelegramBot\Entities\Update;

class MyChatMemberHandler
{
    /**
     * Метод для обработки обновления my_chat_member
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        $handler = new DefaultMyChatMemberHandler();
        $handler->handle($update);
    }
}
