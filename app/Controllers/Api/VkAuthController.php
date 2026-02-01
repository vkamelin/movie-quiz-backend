<?php

namespace App\Controllers\Api;

use Firebase\JWT\JWT;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

class VkAuthController extends VkController
{
    public function auth(Req $request, Res $response)
    {
        $params = $request->getQueryParams();

        if (!$this->validateVkSign($params)) {
            $response->getBody()->write(json_encode(['error' => 'Invalid signature']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $userId = (int)($params['vk_user_id'] ?? 0);

        if (!$userId) {
            $response->getBody()->write(json_encode(['error' => 'User ID not found']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $payload = [
            'user_id' => $userId,
            'exp' => time() + 3600 // 1 час
        ];

        $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        $response->getBody()->write(json_encode(['token' => $token]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}