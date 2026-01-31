<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\InlineQueries\DefaultInlineQueryHandler;
use Longman\TelegramBot\Entities\Update;

class InlineQueryHandler
{
    /**
     * Метод для обработки InlineQuery
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        $handler = new DefaultInlineQueryHandler();
        $handler->handle($update);
    }
}
