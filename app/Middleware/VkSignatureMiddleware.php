<?php

/*
 * Copyright (c) 2026. Vitaliy Kamelin <v.kamelin@gmail.com>
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Response as ResponseHelper;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;

/**
 * Middleware для проверки подписи VK.
 */
final class VkSignatureMiddleware implements MiddlewareInterface
{
    /**
     * Проверяет подпись VK.
     *
     * @param Req $request HTTP-запрос
     * @param Handler $handler Следующий обработчик
     * @return Res Ответ после проверки
     */
    public function process(Req $request, Handler $handler): Res
    {
        $params = $request->getQueryParams();
        if (!isset($params['payload'], $params['sign'], $params['ts'])) {
            return ResponseHelper::problem(new Response(), 401, 'Missing required parameters for VK signature validation');
        }

        $secret = $_ENV['VK_APP_SECRET'];
        if (empty($secret)) {
            return ResponseHelper::problem(new Response(), 401, 'VK App Secret is not configured');
        }

        $payloadStr = $params['payload'];
        $receivedSign = $params['sign'];
        $ts = $params['ts'];

        if ($payloadStr === '' || $receivedSign === '' || $ts === '') {
            return ResponseHelper::problem(new Response(), 401, 'Empty required parameters for VK signature validation');
        }

        $appId = (int)$_ENV['VK_APP_ID'];
        if (!$appId) {
            return ResponseHelper::problem(new Response(), 401, 'VK App ID is not configured');
        }

        parse_str($payloadStr, $payloadArray);
        $userId = $payloadArray['user_id'] ?? null;
        if ($userId === null) {
            return ResponseHelper::problem(new Response(), 401, 'User ID not found in payload for VK signature validation');
        }

        $hashParams = [
            'app_id' => $appId,
            'user_id' => (int)$userId,
            'request_id' => $payloadStr,
            'ts' => (int)$ts,
        ];

        ksort($hashParams);
        $queryString = http_build_query($hashParams);

        $expectedSign = hash_hmac('sha256', $queryString, $secret, true);
        $expectedSign = rtrim(strtr(base64_encode($expectedSign), '+/', '-_'), '=');

        if (!hash_equals($expectedSign, $receivedSign)) {
            return ResponseHelper::problem(new Response(), 401, 'VK signature validation failed');
        }

        return $handler->handle($request);
    }
}