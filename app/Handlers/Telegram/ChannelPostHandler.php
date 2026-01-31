<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\ChannelPosts\DefaultChannelPostHandler;
use Longman\TelegramBot\Entities\Update;

class ChannelPostHandler
{
    /**
     * Метод для обработки сообщения канала
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        // Пока что используется только обработчик по умолчанию
        $handler = new DefaultChannelPostHandler();

        // Вызов хендлера
        $handler->handle($update);
    }
}
