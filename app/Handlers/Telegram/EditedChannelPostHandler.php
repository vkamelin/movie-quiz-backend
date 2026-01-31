<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\EditedChannelPosts\DefaultEditedChannelPostHandler;
use Longman\TelegramBot\Entities\Update;

class EditedChannelPostHandler
{
    /**
     * Метод для обработки отредактированного сообщения канала
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        // Пока что используется только обработчик по умолчанию
        $handler = new DefaultEditedChannelPostHandler();

        // Вызов хендлера
        $handler->handle($update);
    }
}
