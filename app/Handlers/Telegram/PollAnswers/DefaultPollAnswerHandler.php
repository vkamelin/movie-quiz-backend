<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\PollAnswers;

use App\Helpers\Push;
use JsonException;
use Longman\TelegramBot\Entities\Update;

class DefaultPollAnswerHandler extends AbstractPollAnswerHandler
{
    public function handle(Update $update): void
    {
        $pollAnswer = $update->getPollAnswer();
        if ($pollAnswer === null) {
            return;
        }

        $optionIds = $pollAnswer->getOptionIds();
        try {
            $optionIdsJson = json_encode($optionIds, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $optionIdsJson = '[]';
        }

        $stmt = $this->db->prepare(
            'INSERT INTO poll_answers (poll_id, user_id, option_ids, answered_at) '
            . 'VALUES (:poll_id, :user_id, :option_ids, :answered_at) '
            . 'ON DUPLICATE KEY UPDATE option_ids = VALUES(option_ids), answered_at = VALUES(answered_at)'
        );
        $stmt->execute([
            ':poll_id' => $pollAnswer->getPollId(),
            ':user_id' => $pollAnswer->getUser()->getId(),
            ':option_ids' => $optionIdsJson,
            ':answered_at' => date('Y-m-d H:i:s'),
        ]);

        // Example: count votes for each option
        $selectStmt = $this->db->prepare(
            'SELECT option_ids FROM poll_answers WHERE poll_id = :poll_id'
        );
        $selectStmt->execute([':poll_id' => $pollAnswer->getPollId()]);
        $rows = $selectStmt->fetchAll(\PDO::FETCH_COLUMN);

        $counts = [];
        foreach ($rows as $row) {
            try {
                $ids = json_decode($row, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $ids = [];
            }
            foreach ($ids as $id) {
                $counts[$id] = ($counts[$id] ?? 0) + 1;
            }
        }

        $text = "Текущие голоса:\n";
        foreach ($counts as $id => $count) {
            $text .= sprintf("Опция %d: %d\n", $id, $count);
        }

        Push::text((int) $pollAnswer->getUser()->getId(), $text);
    }
}
