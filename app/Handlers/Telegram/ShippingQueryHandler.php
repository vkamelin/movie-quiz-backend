<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\ShippingQueries\DefaultShippingQueryHandler;
use Longman\TelegramBot\Entities\Update;

class ShippingQueryHandler
{
    /**
     * Handle shipping query updates.
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        $handler = new DefaultShippingQueryHandler();
        $handler->handle($update);
    }
}
