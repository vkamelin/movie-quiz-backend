<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\ChatJoinRequests\DefaultChatJoinRequestHandler;
use Longman\TelegramBot\Entities\Update;

class ChatJoinRequestHandler
{
    /**
     * Метод для обработки обновления chat_join_request
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        $handler = new DefaultChatJoinRequestHandler();
        $handler->handle($update);
    }
}
