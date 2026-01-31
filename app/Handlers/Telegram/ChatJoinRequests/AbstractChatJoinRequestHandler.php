<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\ChatJoinRequests;

use App\Helpers\Database;
use Longman\TelegramBot\Entities\Update;
use PDO;

abstract class AbstractChatJoinRequestHandler
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Метод для обработки обновления chat_join_request
     *
     * @param Update $update
     */
    abstract public function handle(Update $update): void;
}
