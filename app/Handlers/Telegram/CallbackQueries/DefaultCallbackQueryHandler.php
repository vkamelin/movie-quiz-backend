<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\CallbackQueries;

use App\Helpers\Logger;
use App\Helpers\Push;
use Longman\TelegramBot\Entities\Update;

class DefaultCallbackQueryHandler extends AbstractCallbackQueryHandler
{
    public function handle(Update $update): void
    {
        $callbackQuery = $update->getCallbackQuery();
        $userId = $callbackQuery->getFrom()->getId();
        $callbackQueryId = (int) $callbackQuery->getId();
        $messageId = $callbackQuery->getMessage()->getMessageId();
        $callbackData = $callbackQuery->getData();

        Logger::info('Received callback query', [
            'user_id' => $userId,
            'message_id' => $messageId,
            'callback_data' => $callbackData,
        ]);

        // Simply acknowledge the callback query to avoid "loading" animation
        $this->answerCallbackQuery($userId, $callbackQueryId);

        Push::text($userId, 'Ваш запрос обработан.');
    }
}
