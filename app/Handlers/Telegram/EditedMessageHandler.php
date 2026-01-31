<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\EditedMessages\DefaultEditedMessageHandler;
use Longman\TelegramBot\Entities\Update;

class EditedMessageHandler
{
    /**
     * Метод для обработки отредактированного сообщения
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        // Пока что используется только обработчик по умолчанию
        $handler = new DefaultEditedMessageHandler();

        // Вызов хендлера
        $handler->handle($update);
    }
}
