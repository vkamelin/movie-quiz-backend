<?php

declare(strict_types=1);

namespace App\Handlers\Telegram;

use App\Handlers\Telegram\CallbackQueries\CheckSubscriptionHandler;
use App\Handlers\Telegram\CallbackQueries\DefaultCallbackQueryHandler;
use Longman\TelegramBot\Entities\Update;

class CallbackQueryHandler
{
    /**
     * Метод для обработки CallbackQuery
     *
     * @param Update $update
     */
    public function handle(Update $update): void
    {
        $callbackQuery = $update->getCallbackQuery();
        $callbackData = $callbackQuery->getData(); // Получаем данные, переданные в CallbackQuery

        // Определение, какой хендлер вызывать
        $handler = match (true) {
            $callbackData === 'checkSubscription' => new CheckSubscriptionHandler(),
            default => new DefaultCallbackQueryHandler(),
        };

        // Вызов хендлера
        $handler->handle($update);
    }
}
