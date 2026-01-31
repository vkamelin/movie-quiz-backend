<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\ShippingQueries;

use App\Helpers\Database;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
use PDO;

abstract class AbstractShippingQueryHandler
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Handle incoming ShippingQuery update.
     *
     * @param Update $update
     */
    abstract public function handle(Update $update): void;

    /**
     * Send answer to shipping query.
     *
     * @param string $shippingQueryId
     * @param bool $ok
     * @param array $shippingOptions
     * @param string $errorMessage
     */
    protected function answerShippingQuery(string $shippingQueryId, bool $ok, array $shippingOptions = [], string $errorMessage = ''): void
    {
        $data = [
            'shipping_query_id' => $shippingQueryId,
            'ok' => $ok,
        ];

        if ($ok) {
            $data['shipping_options'] = $shippingOptions;
        } elseif ($errorMessage !== '') {
            $data['error_message'] = $errorMessage;
        }

        Request::answerShippingQuery($data);
    }
}
