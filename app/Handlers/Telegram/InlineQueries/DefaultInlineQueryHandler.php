<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\InlineQueries;

use JsonException;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;

class DefaultInlineQueryHandler extends AbstractInlineQueryHandler
{
    public function handle(Update $update): void
    {
        $inlineQuery = $update->getInlineQuery();

        $location = $inlineQuery->getLocation();

        $locationJson = null;
        if ($location !== null) {
            try {
                $locationJson = json_encode($location->getRawData(), JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $locationJson = null;
            }
        }

        $stmt = $this->db->prepare(
            'INSERT INTO inline_queries (inline_query_id, from_user_id, query, location, received_at)
                VALUES (:inline_query_id, :from_user_id, :query, :location, :received_at)'
        );

        $stmt->execute([
            ':inline_query_id' => $inlineQuery->getId(),
            ':from_user_id' => $inlineQuery->getFrom()->getId(),
            ':query' => $inlineQuery->getQuery(),
            ':location' => $locationJson,
            ':received_at' => date('Y-m-d H:i:s'),
        ]);

        $results = [
            [
                'type' => 'article',
                'id' => 'example-1',
                'title' => 'Example result',
                'input_message_content' => [
                    'message_text' => 'This is an example inline query result.',
                ],
            ],
        ];

        Request::answerInlineQuery([
            'inline_query_id' => $inlineQuery->getId(),
            'results' => $results,
            'cache_time' => 0,
            'is_personal' => true,
        ]);
    }
}
