<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\ChosenInlineResults;

use App\Helpers\Database;
use JsonException;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;

class DefaultChosenInlineResultHandler extends AbstractChosenInlineResultHandler
{
    public function handle(Update $update): void
    {
        $chosen = $update->getChosenInlineResult();
        $location = $chosen->getLocation();

        $locationData = null;

        try {
            if ($location !== null) {
                $locationData = json_encode([
                    'latitude' => $location->getLatitude(),
                    'longitude' => $location->getLongitude(),
                ], JSON_THROW_ON_ERROR);
            }
        } catch (JsonException $e) {
            $locationData = null;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO `chosen_inline_results` (`query_id`, `result`, `location`) VALUES (:query_id, :result, :location)'
        );
        $stmt->execute([
            'query_id' => $chosen->getQuery(),
            'result' => $chosen->getResultId(),
            'location' => $locationData,
        ]);

        Request::sendMessage([
            'chat_id' => $chosen->getFrom()->getId(),
            'text' => 'Пример ответа пользователю: результат сохранён.',
        ]);
    }
}
