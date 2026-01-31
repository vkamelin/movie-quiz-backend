<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\Commands;

use App\Helpers\Database;
use Longman\TelegramBot\Entities\Update;
use PDO;

abstract class AbstractCommandHandler
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Метод для обработки команды
     *
     * @param Update $update
     */
    abstract public function handle(Update $update): void;
}
