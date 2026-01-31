<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\PreCheckoutQueries\DefaultPreCheckoutQueryHandler;
use Longman\TelegramBot\Entities\Update;

class PreCheckoutQueryHandler
{
    /**
     * Handle pre-checkout query updates.
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        $handler = new DefaultPreCheckoutQueryHandler();
        $handler->handle($update);
    }
}
