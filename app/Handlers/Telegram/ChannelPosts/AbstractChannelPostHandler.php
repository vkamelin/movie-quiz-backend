<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\ChannelPosts;

use App\Helpers\Database;
use Longman\TelegramBot\Entities\Update;
use PDO;

abstract class AbstractChannelPostHandler
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Метод для обработки сообщения канала
     *
     * @param Update $update
     */
    abstract public function handle(Update $update): void;
}
