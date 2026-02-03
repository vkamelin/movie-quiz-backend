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

    protected function getPayload(Req $request): array
    {
        $params = $request->getQueryParams();
        if (!$params || !isset($params['payload'])) {
            return [];
        }

        $parsed = [];
        parse_str($params['payload'], $parsed); // <-- Исправлено
        return $parsed;
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