<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\ChatMembers;

use App\Helpers\Database;
use Longman\TelegramBot\Entities\Update;
use PDO;

abstract class AbstractChatMemberHandler
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Метод для обработки обновления chat_member
     *
     * @param Update $update
     */
    abstract public function handle(Update $update): void;
}
