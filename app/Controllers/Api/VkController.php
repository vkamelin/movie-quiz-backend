<?php

namespace App\Controllers\Api;

use App\Helpers\Logger;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

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
     * @param Req $request
     * @return bool
     */
    function validateVkSign(Req $request): bool
    {
        $params = $request->getQueryParams();

        // Обязательные параметры
        if (!isset($params['payload'], $params['sign'], $params['ts'])) {
            Logger::error('Missing required parameters', $params);
            return false;
        }

        $secret = $_ENV['VK_APP_SECRET'];
        if (empty($secret)) {
            return false;
        }

        if (!$params['payload'] || !$params['sign'] || !$params['ts']) {
            return false;
        }

        // Получаем app_id из конфига (или .env)
        $appId = $_ENV['VK_APP_ID']; // ← должен быть задан!
        if (!$appId) {
            return false;
        }

        // Формируем хеш-параметры
        $hashParams = [
            'app_id' => (int)$appId,
            'user_id' => (int)$userId,
            'request_id' => $params['payload'],
            'ts' => (int)$params['ts'],
        ];

        ksort($hashParams);
        $queryString = http_build_query($hashParams);

        // Генерируем подпись: HMAC-SHA256 + base64 + безопасный URL-код
        $computedSign = hash_hmac('sha256', $queryString, $secret, true);
        $computedSign = rtrim(strtr(base64_encode($computedSign), '+/', '-_'), '=');

        return hash_equals($computedSign, $params['sign']);
    }

    protected function getPayload(Req $request): array
    {
        $params = $request->getParsedBody();
        if (!$params) {
            return (array)parse_url($params, PHP_URL_QUERY);
        }

        return [];
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