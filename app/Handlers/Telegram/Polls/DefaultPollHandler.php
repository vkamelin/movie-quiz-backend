<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\Polls;

use App\Domain\PollsTable;
use JsonException;
use Longman\TelegramBot\Entities\Update;

class DefaultPollHandler extends AbstractPollHandler
{
    public function handle(Update $update): void
    {
        $poll = $update->getPoll();

        $repo = new PollsTable($this->db);
        $options = $poll->getOptions();
        try {
            $optionsJson = json_encode($options, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            $optionsJson = '[]';
        }

        $repo->save([
            'poll_id' => $poll->getId(),
            'question' => $poll->getQuestion(),
            'is_anonymous' => $poll->getIsAnonymous(),
            'allows_multiple_answers' => $poll->getAllowsMultipleAnswers(),
            'options' => $optionsJson,
            'open_period' => $poll->getOpenPeriod(),
            'close_date' => $poll->getCloseDate() ? date('c', $poll->getCloseDate()) : null,
            'total_voter_count' => $poll->getTotalVoterCount(),
            'created_at' => date('c'),
        ]);
    }
}
