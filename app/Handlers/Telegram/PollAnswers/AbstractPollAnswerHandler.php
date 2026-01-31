<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\PollAnswers;

use App\Helpers\Database;
use Longman\TelegramBot\Entities\Update;
use PDO;

abstract class AbstractPollAnswerHandler
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Handle incoming poll answer update.
     *
     * @param Update $update
     */
    abstract public function handle(Update $update): void;
}
