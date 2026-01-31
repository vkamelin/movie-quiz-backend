<?php

declare(strict_types=1);

use App\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response as Psr7Response;

final class RateLimitMiddlewareTest extends TestCase
{
    public function testUsesTelegramUserIdForBucket(): void
    {
        $middleware = new RateLimitMiddleware(['bucket' => 'user', 'limit' => 1]);

        $req1 = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withAttribute('telegramUser', ['id' => 1])
            ->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $handler = new class () implements RequestHandlerInterface {
            public function handle(Req $request): Res
            {
                return new Psr7Response();
            }
        };

        $res1 = $middleware->process($req1, $handler);
        $this->assertSame(200, $res1->getStatusCode());

        $req2 = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withAttribute('telegramUser', ['id' => 1])
            ->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $res2 = $middleware->process($req2, $handler);
        $this->assertSame(429, $res2->getStatusCode());

        $req3 = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withAttribute('telegramUser', ['id' => 2])
            ->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $res3 = $middleware->process($req3, $handler);
        $this->assertSame(200, $res3->getStatusCode());
    }

    public function testFallsBackToIpWhenNoUser(): void
    {
        $middleware = new RateLimitMiddleware(['bucket' => 'user', 'limit' => 1]);

        $handler = new class () implements RequestHandlerInterface {
            public function handle(Req $request): Res
            {
                return new Psr7Response();
            }
        };

        $req1 = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withServerParams(['REMOTE_ADDR' => '9.9.9.9']);
        $res1 = $middleware->process($req1, $handler);
        $this->assertSame(200, $res1->getStatusCode());

        $req2 = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withServerParams(['REMOTE_ADDR' => '9.9.9.9']);
        $res2 = $middleware->process($req2, $handler);
        $this->assertSame(429, $res2->getStatusCode());
    }
}
