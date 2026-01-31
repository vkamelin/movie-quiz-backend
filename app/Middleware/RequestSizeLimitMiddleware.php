<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Middleware для ограничения размера тела запроса.
 */
class RequestSizeLimitMiddleware implements MiddlewareInterface
{
    private int $maxBytes;
    /** @var array<string,int> */
    private array $overrides;

    /**
     * @param int $maxBytes Максимальный размер тела запроса в байтах
     */
    public function __construct(int $maxBytes, array $overrides = [])
    {
        $this->maxBytes = $maxBytes;
        $this->overrides = $overrides;
    }

    /**
     * Проверяет размер запроса и отклоняет слишком большие тела.
     *
     * @param Request $request HTTP-запрос
     * @param RequestHandlerInterface $handler Следующий обработчик
     * @return ResponseInterface Ответ после проверки
     */
    public function process(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $limit = $this->resolveLimit($request);

        $length = $request->getServerParams()['CONTENT_LENGTH'] ?? $request->getHeaderLine('Content-Length');
        if ($length !== null && $length !== '' && (int)$length > $limit) {
            $response = new Response(413);
            $payload = json_encode(['error' => 'Payload Too Large'], JSON_UNESCAPED_UNICODE);
            if (!is_string($payload)) {
                $payload = '{"error":"Payload Too Large"}';
            }
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }

        $bodySize = $request->getBody()->getSize();
        if ($bodySize !== null && $bodySize > $limit) {
            $response = new Response(413);
            $payload = json_encode(['error' => 'Payload Too Large'], JSON_UNESCAPED_UNICODE);
            if (!is_string($payload)) {
                $payload = '{"error":"Payload Too Large"}';
            }
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }

    private function resolveLimit(Request $request): int
    {
        $path = $request->getUri()->getPath();
        // Exact match or prefix-based overrides
        foreach ($this->overrides as $prefix => $bytes) {
            if ($prefix === $path || str_starts_with($path, $prefix)) {
                return (int)$bytes;
            }
        }
        return $this->maxBytes;
    }
}
