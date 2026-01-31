<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\MessageReactionCounts\DefaultMessageReactionCountHandler;
use Longman\TelegramBot\Entities\Update;

class MessageReactionCountHandler
{
    /**
     * Метод для обработки обновления количества реакций на сообщение
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        $handler = new DefaultMessageReactionCountHandler();

        // Вызов хендлера
        $handler->handle($update);
    }
}
