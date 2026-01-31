<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\ChatBoosts\DefaultChatBoostHandler;
use Longman\TelegramBot\Entities\Update;

class ChatBoostHandler
{
    /**
     * Метод для обработки обновления chat_boost
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        $handler = new DefaultChatBoostHandler();
        $handler->handle($update);
    }
}
