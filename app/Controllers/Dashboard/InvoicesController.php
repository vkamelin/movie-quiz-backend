<?php

/**
 * Controller for sending invoices via dashboard.
 */

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Helpers\Flash;
use App\Helpers\Push;
use App\Helpers\View;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

final class InvoicesController
{
    public function create(Req $req, Res $res): Res
    {
        $defaultChatId = (int)($_ENV['DEFAULT_CHAT_ID'] ?? 0);
        $params = [
            'title' => 'Invoice',
            'invoice' => ['chat_id' => $defaultChatId],
            'errors' => [],
        ];

        return View::render($res, 'dashboard/invoices/create.php', $params, 'layouts/main.php');
    }

    public function store(Req $req, Res $res): Res
    {
        $data = (array)$req->getParsedBody();
        $chatId = (int)($data['chat_id'] ?? 0);
        if ($chatId <= 0) {
            $chatId = (int)($_ENV['DEFAULT_CHAT_ID'] ?? 0);
        }
        $title = trim((string)($data['title'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $payload = trim((string)($data['payload'] ?? ''));
        $providerToken = trim((string)($data['provider_token'] ?? ''));
        $currency = trim((string)($data['currency'] ?? ''));
        $pricesRaw = (string)($data['prices'] ?? '');

        $errors = [];
        if ($chatId <= 0) {
            $errors[] = 'chat_id is required';
        }
        if ($title === '') {
            $errors[] = 'title is required';
        }
        if ($description === '') {
            $errors[] = 'description is required';
        }
        if ($payload === '') {
            $errors[] = 'payload is required';
        }
        if ($providerToken === '') {
            $errors[] = 'provider_token is required';
        }
        if ($currency === '') {
            $errors[] = 'currency is required';
        }
        if ($pricesRaw === '') {
            $errors[] = 'prices is required';
        }

        $prices = [];
        if ($pricesRaw !== '') {
            $decoded = json_decode($pricesRaw, true);
            if (is_array($decoded)) {
                $prices = $decoded;
            } else {
                $errors[] = 'prices must be valid JSON';
            }
        }

        $invoiceData = [
            'title' => $title,
            'description' => $description,
            'payload' => $payload,
            'provider_token' => $providerToken,
            'currency' => $currency,
            'prices' => $prices,
        ];

        $optional = ['need_name', 'need_phone_number', 'need_email', 'need_shipping_address', 'is_flexible'];
        foreach ($optional as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $invoiceData[$field] = (bool)$data[$field];
            }
        }

        if (!empty($errors)) {
            $params = [
                'title' => 'Invoice',
                'errors' => $errors,
                'invoice' => array_merge($invoiceData, [
                    'chat_id' => $chatId,
                    'prices' => $pricesRaw,
                ]),
            ];
            return View::render($res, 'dashboard/invoices/create.php', $params, 'layouts/main.php');
        }

        $result = Push::invoice($chatId, $invoiceData);
        if ($result) {
            Flash::add('success', 'Invoice queued');
            return $res->withHeader('Location', '/dashboard/messages')->withStatus(302);
        }

        $params = [
            'title' => 'Invoice',
            'errors' => ['Failed to queue invoice'],
            'invoice' => array_merge($invoiceData, [
                'chat_id' => $chatId,
                'prices' => $pricesRaw,
            ]),
        ];
        return View::render($res, 'dashboard/invoices/create.php', $params, 'layouts/main.php');
    }
}
