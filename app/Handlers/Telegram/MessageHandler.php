<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\Commands\AboutCommandHandler;
use App\Handlers\Telegram\Commands\ResetCommandHandler;
use App\Handlers\Telegram\Commands\StartCommandHandler;
use App\Handlers\Telegram\Messages\TextMessageHandler;
use App\Handlers\Telegram\Messages\WriteAccessAllowedMessageHandler;
use Exception;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Entities\WriteAccessAllowed;

class MessageHandler
{
    /**
     * Метод для обработки сообщений
     *
     * @param Update $update
     *
     * @throws Exception
     */
    public function handle(Update $update): void
    {
        $message = $update->getMessage();
        /** @var WriteAccessAllowed|null $writeAccess */
        $writeAccess = $message->getWriteAccessAllowed();
        $text = $message->getText() ?? '';

        if ($writeAccess instanceof WriteAccessAllowed) {
            $handler = new WriteAccessAllowedMessageHandler();
        } else {
            $text = str_starts_with($text, '/start') ? '/start' : $text;

            $handler = match ($text) {
                '/start' => new StartCommandHandler(),
                '/reset' => new ResetCommandHandler(),
                '/about', 'ℹ️ О проекте' => new AboutCommandHandler(),
                default => new TextMessageHandler(),
            };
        }

        // Вызов хендлера
        $handler->handle($update);
    }
}
