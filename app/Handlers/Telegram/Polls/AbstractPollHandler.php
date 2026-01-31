<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\Polls;

use App\Helpers\Database;
use Longman\TelegramBot\Entities\Update;
use PDO;

abstract class AbstractPollHandler
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Handle incoming poll update.
     *
     * @param Update $update
     */
    abstract public function handle(Update $update): void;
}
