<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\ChosenInlineResults\DefaultChosenInlineResultHandler;
use Longman\TelegramBot\Entities\Update;

class ChosenInlineResultHandler
{
    /**
     * Handle chosen inline result updates.
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        $handler = new DefaultChosenInlineResultHandler();
        $handler->handle($update);
    }
}
