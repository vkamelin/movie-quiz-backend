<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\Messages;

use App\Helpers\FlowState;
use Longman\TelegramBot\Entities\Update;
use PDO;

/**
 * Обработчик текстового сообщения
 *
 * @property PDO $db
 */
class TextMessageHandler extends AbstractMessageHandler
{
    public function handle(Update $update): void
    {
        $message = $update->getMessage();
        $chatId = $message->getChat()->getId();
        $messageText = trim($message->getText() ?? '');

        FlowState::update((string) $chatId, ['last_message' => $messageText]);
    }
}
