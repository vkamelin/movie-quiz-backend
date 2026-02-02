<?php

namespace App\Controllers\Api;

use Firebase\JWT\JWT;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

class VkAuthController extends VkController
{
    public function auth(Req $request, Res $response): MessageInterface|Res
    {
        // Для валидации подписи передаём все параметры, как есть
        if (!$this->validateVkSign($request)) {
            return $this->error($response, 'Invalid signature', 401);
        }

        // Парсим data, чтобы получить user_id
        $data = $this->getPayload($request);
        $userId = (int)($data['user_id'] ?? 0);

        if (!$userId) {
            return $this->error($response, 'User ID not found in data', 400);
        }

        $payload = [
            'user_id' => $userId,
            'exp' => time() + 3600
        ];

        $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        return $this->success($response, ['token' => $token]);
    }
}