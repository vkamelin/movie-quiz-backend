<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\Polls\DefaultPollHandler;
use Longman\TelegramBot\Entities\Update;

class PollHandler
{
    /**
     * Handle poll updates.
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        $handler = new DefaultPollHandler();
        $handler->handle($update);
    }
}
