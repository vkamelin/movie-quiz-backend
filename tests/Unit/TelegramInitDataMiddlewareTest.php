<?php

declare(strict_types=1);

use App\Middleware\TelegramInitDataMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response as Psr7Response;

final class TelegramInitDataMiddlewareTest extends TestCase
{
    private string $botToken = 'TEST_TOKEN';

    private function buildInitData(array $user, ?int $authDate = null): string
    {
        $data = [
            'auth_date' => (string)($authDate ?? 123),
            'user' => json_encode($user, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
        ksort($data);
        $check = [];
        foreach ($data as $k => $v) {
            $check[] = $k . '=' . $v;
        }
        $checkString = implode("\n", $check);
        $secret = hash_hmac('sha256', $this->botToken, 'WebAppData', true);
        $hash = hash_hmac('sha256', $checkString, $secret);
        $data['hash'] = $hash;
        return http_build_query($data);
    }

    public function testValidInitDataPasses(): void
    {
        $middleware = new TelegramInitDataMiddleware($this->botToken);
        $init = $this->buildInitData(['id' => 1, 'username' => 'test']);
        $req = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('X-Telegram-Init-Data', $init);
        $handler = new class () implements RequestHandlerInterface {
            public ?Req $captured = null;
            public function handle(Req $request): Res
            {
                $this->captured = $request;
                return new Psr7Response();
            }
        };
        $res = $middleware->process($req, $handler);
        $this->assertSame(200, $res->getStatusCode());
        $this->assertInstanceOf(Req::class, $handler->captured);
        $this->assertSame(
            ['id' => 1, 'username' => 'test'],
            $handler->captured->getAttribute('telegramUser'),
        );
        $this->assertSame($init, $handler->captured->getAttribute('rawInitData'));
    }

    public function testInvalidInitDataFails(): void
    {
        $middleware = new TelegramInitDataMiddleware($this->botToken);
        $init = $this->buildInitData(['id' => 1]);
        $init .= 'broken';
        $req = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('X-Telegram-Init-Data', $init);
        $handler = new class () implements RequestHandlerInterface {
            public function handle(Req $request): Res
            {
                return new Psr7Response();
            }
        };
        $res = $middleware->process($req, $handler);
        $this->assertSame(403, $res->getStatusCode());
        $this->assertStringContainsString('Invalid init data', (string)$res->getBody());
    }

    public function testExpiredInitDataFails(): void
    {
        $middleware = new TelegramInitDataMiddleware($this->botToken);
        $expired = time() - 86400 - 1;
        $init = $this->buildInitData(['id' => 1], $expired);
        $req = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('X-Telegram-Init-Data', $init);
        $handler = new class () implements RequestHandlerInterface {
            public function handle(Req $request): Res
            {
                return new Psr7Response();
            }
        };
        $res = $middleware->process($req, $handler);
        $this->assertSame(403, $res->getStatusCode());
        $this->assertStringContainsString('Invalid init data', (string)$res->getBody());
    }
}
