<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Middleware для назначения идентификатора запросу.
 */
class RequestIdMiddleware
{
    private string $header;

    /**
     * @param string $header Название заголовка с идентификатором запроса
     */
    public function __construct(string $header = 'X-Request-Id')
    {
        $this->header = $header;
    }

    /**
     * Добавляет идентификатор запроса и устанавливает его в логере.
     *
     * @param Request $request HTTP-запрос
     * @param RequestHandler $handler Следующий обработчик
     * @return Response Ответ с заголовком Request-Id
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $requestId = $request->getHeaderLine($this->header);
        if ($requestId === '') {
            // создаем uuid
            $requestId = uniqid('', true);
        }

        $request = $request->withAttribute('request_id', $requestId);
        Logger::setRequestId($requestId);

        $response = $handler->handle($request);

        Logger::setRequestId(null);

        return $response->withHeader($this->header, $requestId);
    }
}
