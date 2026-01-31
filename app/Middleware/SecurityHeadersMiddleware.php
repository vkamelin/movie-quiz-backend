<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * Middleware для установки заголовков безопасности и CORS.
 */
class SecurityHeadersMiddleware
{
    private array $cors;
    private array $csp;
    private ?string $xFrameOptions;
    private array $headers;
    private ResponseFactory $responseFactory;

    /**
     * @param array $options Настройки заголовков
     * @param ResponseFactory|null $responseFactory Фабрика ответов
     */
    public function __construct(array $options = [], ?ResponseFactory $responseFactory = null)
    {
        $this->cors = $options['cors'] ?? [];
        $this->csp = $options['csp'] ?? [];
        $this->xFrameOptions = $options['x_frame_options'] ?? 'DENY';
        $this->headers = $options['headers'] ?? [];
        $this->responseFactory = $responseFactory ?? new ResponseFactory();
    }

    /**
     * Добавляет заголовки безопасности к ответу.
     *
     * @param Request $request HTTP-запрос
     * @param RequestHandler $handler Следующий обработчик
     * @return Response Ответ с заголовками
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $isPreflight = strtoupper($request->getMethod()) === 'OPTIONS';
        if ($isPreflight) {
            $response = $this->responseFactory->createResponse(204);
        } else {
            $response = $handler->handle($request);
        }

        if ($this->cors !== []) {
            $origin = $request->getHeaderLine('Origin');
            $allowedOrigins = $this->cors['origins'] ?? [];
            if ($origin !== '' && (empty($allowedOrigins) || in_array($origin, $allowedOrigins, true))) {
                $response = $response->withHeader('Access-Control-Allow-Origin', $origin)
                    ->withHeader('Access-Control-Allow-Credentials', 'true');
            }
            $methods = $this->cors['methods'] ?? 'GET,POST,PUT,PATCH,DELETE,OPTIONS';
            $headers = $this->cors['headers'] ?? 'Content-Type, Authorization';
            $response = $response
                ->withHeader('Access-Control-Allow-Methods', $methods)
                ->withHeader('Access-Control-Allow-Headers', $headers);
        }

        if ($this->csp !== null) {
            $policy = $this->buildCsp(
                $this->csp['script'] ?? null,
                $this->csp['style'] ?? null,
                $this->csp['img'] ?? null,
                $this->csp['connect'] ?? null,
                $this->csp['font'] ?? null,
            );
            $response = $response->withHeader('Content-Security-Policy', $policy);
        }

        if ($this->xFrameOptions !== null) {
            $response = $response->withHeader('X-Frame-Options', $this->xFrameOptions);
        }

        foreach ($this->headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    private function buildCsp(?string $script, ?string $style, ?string $img, ?string $connect, ?string $font): string
    {
        $scriptSrc = $this->buildSources($script ?? ($_ENV['CSP_SCRIPT_SRC'] ?? ''));
        $styleSrc = $this->buildSources($style ?? ($_ENV['CSP_STYLE_SRC'] ?? ''), true);
        $imgSrc = $this->buildSources($img ?? ($_ENV['CSP_IMG_SRC'] ?? ''));
        $connectSrc = $this->buildSources($connect ?? ($_ENV['CSP_CONNECT_SRC'] ?? ''));
        $fontSrc = $this->buildSources($font ?? ($_ENV['CSP_FONT_SRC'] ?? ''));

        return sprintf(
            "default-src 'self'; connect-src %s; img-src %s data:; script-src %s; style-src %s; font-src %s; frame-ancestors 'none'",
            $connectSrc,
            $imgSrc,
            $scriptSrc,
            $styleSrc,
            $fontSrc,
        );
    }

    private function buildSources(string $domains, bool $allowUnsafeInline = false): string
    {
        $sources = "'self'";
        if ($allowUnsafeInline) {
            $sources .= " 'unsafe-inline'";
        }
        $additional = array_filter(array_map('trim', explode(',', $domains)));
        if ($additional !== []) {
            $sources .= ' ' . implode(' ', $additional);
        }

        return $sources;
    }
}
