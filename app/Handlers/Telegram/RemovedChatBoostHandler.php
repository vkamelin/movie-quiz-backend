<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\RemovedChatBoosts\DefaultRemovedChatBoostHandler;
use Longman\TelegramBot\Entities\Update;

class RemovedChatBoostHandler
{
    /**
     * Method for handling removed_chat_boost update
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        $handler = new DefaultRemovedChatBoostHandler();
        $handler->handle($update);
    }
}
