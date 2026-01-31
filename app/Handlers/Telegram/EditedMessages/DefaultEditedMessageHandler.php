<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\EditedMessages;

use JsonException;
use Longman\TelegramBot\Entities\Update;
use PDO;

class DefaultEditedMessageHandler extends AbstractEditedMessageHandler
{
    public function handle(Update $update): void
    {
        $message = $update->getEditedMessage();
        $messageId = $message->getMessageId();
        $raw = $message->getRawData();

        $newText = $message->getText() ?? '';

        $stmt = $this->db->prepare('SELECT text, entities FROM telegram_message_history WHERE message_id = :message_id');
        $stmt->execute([':message_id' => $messageId]);
        $previous = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $oldText = $previous['text'] ?? '';
        try {
            $oldEntities = isset($previous['entities'])
                ? json_decode($previous['entities'], true, 512, JSON_THROW_ON_ERROR)
                : [];
        } catch (JsonException) {
            $oldEntities = [];
        }

        $newEntities = $raw['entities'] ?? [];

        try {
            $entitiesDiff = array_udiff(
                $newEntities,
                $oldEntities,
                static fn (array $a, array $b): int => strcmp(
                    json_encode($a, JSON_THROW_ON_ERROR),
                    json_encode($b, JSON_THROW_ON_ERROR)
                )
            );
            $entitiesDiffJson = $entitiesDiff ? json_encode($entitiesDiff, JSON_THROW_ON_ERROR) : null;
            $newEntitiesJson = $newEntities ? json_encode($newEntities, JSON_THROW_ON_ERROR) : null;
        } catch (JsonException) {
            $entitiesDiffJson = null;
            $newEntitiesJson = null;
        }

        $editedAt = $message->getEditDate();
        $editedAt = $editedAt ? date('Y-m-d H:i:s', $editedAt) : date('Y-m-d H:i:s');

        $updateStmt = $this->db->prepare(
            'UPDATE telegram_message_history
                SET old_text = :old_text,
                    new_text = :new_text,
                    entities_diff = :entities_diff,
                    entities = :new_entities,
                    edited_at = :edited_at
              WHERE message_id = :message_id'
        );

        $updateStmt->execute([
            ':old_text' => $oldText,
            ':new_text' => $newText,
            ':entities_diff' => $entitiesDiffJson,
            ':new_entities' => $newEntitiesJson,
            ':edited_at' => $editedAt,
            ':message_id' => $messageId,
        ]);
    }
}
