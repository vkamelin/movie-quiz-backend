<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\Messages;

use App\Handlers\Telegram\Commands\StartCommandHandler;
use App\Helpers\Logger;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Entities\WriteAccessAllowed;
use Throwable;

class WriteAccessAllowedMessageHandler extends AbstractMessageHandler
{
    public function handle(Update $update): void
    {
        $message = $update->getMessage();

        try {
            $writeAccessAllowed = $message->getWriteAccessAllowed();
            if ($writeAccessAllowed instanceof WriteAccessAllowed) {
                $webAppName = $writeAccessAllowed->getWebAppName();
                if (!empty($webAppName)) {
                    $handler = new StartCommandHandler();
                    $handler->handle($update);
                }
            }
        } catch (Throwable $e) {
            Logger::error('Ошибка при обработке сообщения: ' . $e->getMessage());
        }
    }
}
