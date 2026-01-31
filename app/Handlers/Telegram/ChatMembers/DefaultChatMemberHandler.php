<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\ChatMembers;

use App\Helpers\Push;
use JsonException;
use Longman\TelegramBot\Entities\Update;

class DefaultChatMemberHandler extends AbstractChatMemberHandler
{
    public function handle(Update $update): void
    {
        $chatMember = $update->getChatMember();
        if ($chatMember === null) {
            return;
        }

        $chatId = $chatMember->getChat()->getId();
        $actor = $chatMember->getFrom();
        $newMember = $chatMember->getNewChatMember();
        $oldMember = $chatMember->getOldChatMember();
        $userId = $newMember->getUser()->getId();
        $status = $newMember->getStatus();
        $oldStatus = $oldMember?->getStatus();

        $state = $status === 'kicked' ? 'declined' : 'approved';

        $stmt = $this->db->prepare(
            'INSERT INTO chat_members (chat_id, user_id, role, state) ' .
            'VALUES (:chat_id, :user_id, :role, :state) ' .
            'ON DUPLICATE KEY UPDATE role = VALUES(role), state = VALUES(state)'
        );
        $stmt->execute([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'role' => $status,
            'state' => $state,
        ]);

        try {
            $rights = json_encode([
                'old' => $oldMember ? $oldMember->getRawData() : null,
                'new' => $newMember->getRawData(),
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $rights = null;
        }

        $updateStmt = $this->db->prepare(
            'INSERT INTO chat_member_updates '
            . '(chat_id, target_user_id, actor_user_id, old_status, new_status, rights, until_date, changed_at) '
            . 'VALUES (:chat_id, :target_user_id, :actor_user_id, :old_status, :new_status, :rights, :until_date, NOW())'
        );
        $untilDate = method_exists($newMember, 'getUntilDate') && $newMember->getUntilDate() !== null
            ? date('Y-m-d H:i:s', (int) $newMember->getUntilDate())
            : null;
        $updateStmt->execute([
            'chat_id' => $chatId,
            'target_user_id' => $userId,
            'actor_user_id' => $actor?->getId(),
            'old_status' => $oldStatus,
            'new_status' => $status,
            'rights' => $rights,
            'until_date' => $untilDate,
        ]);

        if ($actor !== null) {
            // Example: notify administrator about status change
            Push::text(
                (int) $actor->getId(),
                sprintf(
                    'Статус пользователя %d в чате %d изменен с %s на %s.',
                    $userId,
                    $chatId,
                    $oldStatus ?? 'unknown',
                    $status
                )
            );
        }
    }
}
