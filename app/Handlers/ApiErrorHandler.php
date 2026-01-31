<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Exceptions\ValidationException;
use App\Helpers\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpException;
use Slim\Psr7\Factory\ResponseFactory;
use Throwable;

/**
 * Обработчик ошибок для API.
 */
final class ApiErrorHandler
{
    private ResponseFactory $responseFactory;

    /**
     * @param ResponseFactory|null $responseFactory Фабрика ответов
     */
    public function __construct(?ResponseFactory $responseFactory = null)
    {
        $this->responseFactory = $responseFactory ?? new ResponseFactory();
    }

    /**
     * Формирует ответ JSON для исключения.
     *
     * @param Throwable $e Перехваченное исключение
     * @return Response Ответ в формате application/problem+json
     */
    public function handle(Throwable $e): Response
    {
        if ($e instanceof ValidationException) {
            $payload = json_encode([
                'type' => 'https://example.com/validation-error',
                'title' => 'Unprocessable Entity',
                'status' => 422,
                'detail' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], JSON_UNESCAPED_UNICODE);
            if (!is_string($payload)) {
                $payload = '{"type":"https://example.com/validation-error","title":"Unprocessable Entity","status":422,"detail":"Validation failed","errors":[]}' ;
            }
            $response = $this->responseFactory->createResponse(422);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/problem+json');
        }

        if ($e instanceof HttpException) {
            $status = $e->getCode();
            $title = method_exists($e, 'getTitle') ? $e->getTitle() : 'HTTP Error';
            $payload = json_encode([
                'type' => 'about:blank',
                'title' => $title,
                'status' => $status,
                'detail' => $e->getMessage(),
                'errors' => [],
            ], JSON_UNESCAPED_UNICODE);
            if (!is_string($payload)) {
                $payload = sprintf('{"type":"about:blank","title":"%s","status":%d,"detail":"%s","errors":[]}', $title, $status, $e->getMessage());
            }
            $response = $this->responseFactory->createResponse($status);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/problem+json');
        }

        Logger::error($e->getMessage(), ['exception' => $e]);
        $payload = json_encode([
            'type' => 'about:blank',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'Internal Server Error',
            'errors' => [],
        ], JSON_UNESCAPED_UNICODE);
        if (!is_string($payload)) {
            $payload = '{"type":"about:blank","title":"Internal Server Error","status":500,"detail":"Internal Server Error","errors":[]}' ;
        }
        $response = $this->responseFactory->createResponse(500);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/problem+json');
    }
}
