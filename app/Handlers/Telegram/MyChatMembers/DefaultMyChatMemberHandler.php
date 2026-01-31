<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\MyChatMembers;

use App\Domain\MyChatMemberUpdatesTable;
use JsonException;
use Longman\TelegramBot\Entities\Update;

class DefaultMyChatMemberHandler extends AbstractMyChatMemberHandler
{
    public function handle(Update $update): void
    {
        $myChatMember = $update->getMyChatMember();

        $repo = new MyChatMemberUpdatesTable($this->db);
        $newMember = $myChatMember->getNewChatMember();
        $oldMember = $myChatMember->getOldChatMember();

        try {
            $rights = json_encode($newMember->getRawData(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            $rights = null;
        }

        $repo->save([
            'chat_id' => $myChatMember->getChat()->getId(),
            'actor_user_id' => $myChatMember->getFrom()->getId(),
            'old_status' => $oldMember?->getStatus(),
            'new_status' => $newMember->getStatus(),
            'new_rights' => $rights,
            'until_date' => $newMember->getUntilDate() ? date('c', $newMember->getUntilDate()) : null,
            'changed_at' => date('c'),
        ]);
    }
}
