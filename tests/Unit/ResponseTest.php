<?php

declare(strict_types=1);

use App\Helpers\Response;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response as Psr7Response;

final class ResponseTest extends TestCase
{
    public function testJson(): void
    {
        $res = Response::json(new Psr7Response(), 200, ['foo' => 'bar']);
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/json', $res->getHeaderLine('Content-Type'));
        $this->assertSame(['foo' => 'bar'], json_decode((string)$res->getBody(), true));
    }

    public function testProblem(): void
    {
        $res = Response::problem(new Psr7Response(), 400, 'Error', ['detail' => 'oops']);
        $this->assertSame(400, $res->getStatusCode());
        $this->assertSame('application/problem+json', $res->getHeaderLine('Content-Type'));
        $this->assertSame(
            [
                'type' => 'about:blank',
                'title' => 'Error',
                'status' => 400,
                'detail' => 'oops',
            ],
            json_decode((string)$res->getBody(), true)
        );
    }
}
