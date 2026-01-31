<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\ChatJoinRequests;

use App\Helpers\Logger;
use JsonException;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;

class DefaultChatJoinRequestHandler extends AbstractChatJoinRequestHandler
{
    public function handle(Update $update): void
    {
        $chatJoinRequest = $update->getChatJoinRequest();
        if ($chatJoinRequest === null) {
            return;
        }

        $chatId = $chatJoinRequest->getChat()->getId();
        $userId = $chatJoinRequest->getFrom()->getId();
        $bio = $chatJoinRequest->getBio();
        $invite = $chatJoinRequest->getInviteLink();
        $requestedAt = date('Y-m-d H:i:s', $chatJoinRequest->getDate());

        $stmtCheck = $this->db->prepare('SELECT state FROM chat_members WHERE chat_id = :chat_id AND user_id = :user_id');
        $stmtCheck->execute(['chat_id' => $chatId, 'user_id' => $userId]);
        $existingState = $stmtCheck->fetchColumn();
        if ($existingState === 'declined') {
            Request::declineChatJoinRequest(['chat_id' => $chatId, 'user_id' => $userId]);
            try {
                $stmt = $this->db->prepare(
                    'INSERT INTO chat_join_requests (chat_id, user_id, bio, invite_link, requested_at, status, decided_at) '
                    . 'VALUES (:chat_id, :user_id, :bio, :invite_link, :requested_at, "declined", CURRENT_TIMESTAMP) '
                    . 'ON DUPLICATE KEY UPDATE bio = VALUES(bio), invite_link = VALUES(invite_link), requested_at = VALUES(requested_at), status = "declined", decided_at = CURRENT_TIMESTAMP, decided_by = NULL'
                );
                $stmt->execute([
                    'chat_id' => $chatId,
                    'user_id' => $userId,
                    'bio' => $bio,
                    'invite_link' => $invite ? json_encode($invite->getRawData(), JSON_THROW_ON_ERROR) : null,
                    'requested_at' => $requestedAt,
                ]);
            } catch (JsonException $e) {
                Logger::error('Failed to save chat join request', ['exception' => $e]);
            }
            return;
        }

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO chat_join_requests (chat_id, user_id, bio, invite_link, requested_at) '
                . 'VALUES (:chat_id, :user_id, :bio, :invite_link, :requested_at) '
                . 'ON DUPLICATE KEY UPDATE bio = VALUES(bio), invite_link = VALUES(invite_link), requested_at = VALUES(requested_at), status = "pending", decided_at = NULL, decided_by = NULL'
            );

            $stmt->execute([
                'chat_id' => $chatId,
                'user_id' => $userId,
                'bio' => $bio,
                'invite_link' => $invite ? json_encode($invite->getRawData(), JSON_THROW_ON_ERROR) : null,
                'requested_at' => $requestedAt,
            ]);
        } catch (JsonException $e) {
            Logger::error('Failed to save chat join request', ['exception' => $e]);
            return;
        }

        // Пример одобрения заявки
        // Request::approveChatJoinRequest(['chat_id' => $chatId, 'user_id' => $userId]);
        // Пример отклонения заявки
        // Request::declineChatJoinRequest(['chat_id' => $chatId, 'user_id' => $userId]);
    }
}
