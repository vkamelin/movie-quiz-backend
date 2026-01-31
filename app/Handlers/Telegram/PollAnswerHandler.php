<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\PollAnswers\DefaultPollAnswerHandler;
use Longman\TelegramBot\Entities\Update;

class PollAnswerHandler
{
    /**
     * Handle poll answer updates.
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        $handler = new DefaultPollAnswerHandler();
        $handler->handle($update);
    }
}
