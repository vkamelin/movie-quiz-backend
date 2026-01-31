<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\MessageReactions\DefaultMessageReactionHandler;
use Longman\TelegramBot\Entities\Update;

class MessageReactionHandler
{
    /**
     * Метод для обработки реакции на сообщение
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        $handler = new DefaultMessageReactionHandler();

        // Вызов хендлера
        $handler->handle($update);
    }
}
