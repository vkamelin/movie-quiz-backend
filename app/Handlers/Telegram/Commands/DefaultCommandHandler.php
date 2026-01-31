<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\Commands;

use JsonException;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;

class DefaultCommandHandler extends AbstractCommandHandler
{
    /**
     * @param Update $update
     * @return void
     */
    public function handle(Update $update): void
    {
        $message = $update->getMessage();

        if ($message === null) {
            return;
        }

        $text = trim($message->getText() ?? '');
        if ($text === '' || !str_starts_with($text, '/')) {
            return;
        }

        $parts = explode(' ', $text);
        $command = ltrim(array_shift($parts), '/');
        $arguments = $parts;

        try {
            $data = json_encode([
                'command' => $command,
                'arguments' => $arguments,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            $data = '{}';
        }

        $stmt = $this->db->prepare(
            'INSERT INTO telegram_updates (update_id, user_id, message_id, type, data, sent_at) '
            . 'VALUES (:update_id, :user_id, :message_id, :type, :data, :sent_at)'
        );
        $stmt->execute([
            'update_id' => $update->getUpdateId(),
            'user_id' => $message->getFrom()->getId(),
            'message_id' => $message->getMessageId(),
            'type' => 'command',
            'data' => $data,
            'sent_at' => date('c', $message->getDate()),
        ]);

        Request::sendMessage([
            'chat_id' => $message->getChat()->getId(),
            'text' => 'Команда обработана',
        ]);
    }
}
