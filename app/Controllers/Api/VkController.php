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

        // Обязательные параметры
        $payload = $params['payload'] ?? null; // это request_id
        $sign    = $params['sign'] ?? null;
        $ts      = $params['ts'] ?? null;

        if (!$payload || !$sign || !$ts) {
            return false;
        }

        // Получаем app_id из конфига (или .env)
        $appId = $_ENV['VK_APP_ID']; // ← должен быть задан!
        if (!$appId) {
            return false;
        }

        // Извлекаем user_id из payload (если payload = "user_id=123")
        // Альтернатива: передавать user_id отдельно, но проще парсить
        parse_str($payload, $payloadData);
        $userId = $payloadData['user_id'] ?? null;
        if (!$userId) {
            return false;
        }

        // Формируем хеш-параметры как в документации
        $hashParams = [
            'app_id' => (int)$appId,
            'user_id' => (int)$userId,
            'request_id' => $payload, // ← именно так!
            'ts' => (int)$ts,
        ];

        ksort($hashParams);
        $queryString = http_build_query($hashParams);

        // Генерируем подпись: HMAC-SHA256 + base64 + безопасный URL-код
        $computedSign = hash_hmac('sha256', $queryString, $secret, true);
        $computedSign = rtrim(strtr(base64_encode($computedSign), '+/', '-_'), '=');

        return hash_equals($computedSign, $sign);
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