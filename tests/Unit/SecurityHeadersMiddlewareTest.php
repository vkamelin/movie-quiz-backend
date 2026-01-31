<?php

declare(strict_types=1);

use App\Middleware\SecurityHeadersMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response as Psr7Response;

final class SecurityHeadersMiddlewareTest extends TestCase
{
    public function testAddsHeaders(): void
    {
        $middleware = new SecurityHeadersMiddleware([
            'cors' => [
                'origins' => ['https://example.com'],
                'methods' => 'GET',
                'headers' => 'X-Test',
            ],
            'csp' => [
                'script' => 'https://scripts.example',
                'style' => 'https://styles.example',
                'img' => 'https://img.example',
                'connect' => 'https://api.example',
                'font' => 'https://fonts.example',
            ],
            'x_frame_options' => 'DENY',
            'headers' => ['X-Test-Header' => 'foo'],
        ]);

        $req = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Origin', 'https://example.com');
        $handler = new class () implements RequestHandlerInterface {
            public function handle(Req $request): Res
            {
                return new Psr7Response();
            }
        };
        $res = $middleware($req, $handler);
        $this->assertSame('https://example.com', $res->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('GET', $res->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertSame('X-Test', $res->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertSame('DENY', $res->getHeaderLine('X-Frame-Options'));
        $this->assertSame('foo', $res->getHeaderLine('X-Test-Header'));
        $this->assertCount(1, $res->getHeader('Content-Security-Policy'));
        $csp = $res->getHeaderLine('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' https://scripts.example", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline' https://styles.example", $csp);
        $this->assertStringContainsString("img-src 'self' https://img.example data:", $csp);
        $this->assertStringContainsString("connect-src 'self' https://api.example", $csp);
        $this->assertStringContainsString("font-src 'self' https://fonts.example", $csp);
    }

    public function testPreflightReturns204(): void
    {
        $middleware = new SecurityHeadersMiddleware([
            'cors' => [
                'origins' => ['https://example.com'],
                'methods' => 'GET',
                'headers' => 'X-Test',
            ],
        ]);

        $req = (new ServerRequestFactory())->createServerRequest('OPTIONS', '/')
            ->withHeader('Origin', 'https://example.com');
        $handler = new class () implements RequestHandlerInterface {
            public function handle(Req $request): Res
            {
                return new Psr7Response(500);
            }
        };
        $res = $middleware($req, $handler);
        $this->assertSame(204, $res->getStatusCode());
        $this->assertSame('https://example.com', $res->getHeaderLine('Access-Control-Allow-Origin'));
    }
}
