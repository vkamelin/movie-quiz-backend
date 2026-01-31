<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\PreCheckoutQueries;

use App\Helpers\Database;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
use PDO;

abstract class AbstractPreCheckoutQueryHandler
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Handle incoming PreCheckoutQuery update.
     *
     * @param Update $update
     */
    abstract public function handle(Update $update): void;

    /**
     * Send answer to pre-checkout query.
     *
     * @param string $preCheckoutQueryId
     * @param bool $ok
     * @param string $errorMessage
     */
    protected function answerPreCheckoutQuery(string $preCheckoutQueryId, bool $ok, string $errorMessage = ''): void
    {
        $data = [
            'pre_checkout_query_id' => $preCheckoutQueryId,
            'ok' => $ok,
        ];

        if (!$ok && $errorMessage !== '') {
            $data['error_message'] = $errorMessage;
        }

        Request::answerPreCheckoutQuery($data);
    }
}
