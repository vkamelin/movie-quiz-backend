<?php

namespace App\Controllers\Api;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface as Res;

/**
 * Базовый контроллер для API миниаппа для VK
 */
class VkController
{

    public function __construct()
    {
    }

    /**
     * Валидация подписи VK
     *
     * @param array $params Параметры пользователя VK
     * @return bool
     */
    function validateVkSign(array $params): bool
    {
        $secret = $_ENV['VK_APP_SECRET'];

        if (empty($secret)) {
            return false;
        }

        $sign = $params['sign'] ?? null;
        if (!$sign) {
            return false;
        }

        unset($params['sign']);

        // Отфильтровываем только vk_* параметры
        $filtered = [];
        foreach ($params as $key => $value) {
            if (substr($key, 0, 3) === 'vk_') {
                $filtered[$key] = $value;
            }
        }

        ksort($filtered);
        $str = '';
        foreach ($filtered as $key => $value) {
            $str .= $key . '=' . $value;
        }
        $str .= $secret;

        return hash_hmac('sha256', $str, $secret) === $sign;
    }

    protected function success(Res $response, array $data): MessageInterface|Res
    {
        $response->getBody()->write(json_encode(['success' => true, 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    protected function error(Res $response, string $message, int $code = 400): MessageInterface|Res
    {
        $response->getBody()->write(json_encode(['error' => true, 'message' => $message]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($code);
    }
}