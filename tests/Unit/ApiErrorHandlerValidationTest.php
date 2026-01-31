<?php

declare(strict_types=1);

use App\Exceptions\ValidationException;
use App\Handlers\ApiErrorHandler;
use PHPUnit\Framework\TestCase;

final class ApiErrorHandlerValidationTest extends TestCase
{
    public function testValidationExceptionReturns422ProblemJson(): void
    {
        $handler = new ApiErrorHandler();
        $e = new ValidationException(['email' => 'required'], 'Invalid payload');
        $res = $handler->handle($e);
        $this->assertSame(422, $res->getStatusCode());
        $this->assertSame('application/problem+json', $res->getHeaderLine('Content-Type'));
        $body = json_decode((string)$res->getBody(), true);
        $this->assertSame(422, $body['status'] ?? null);
        $this->assertSame('Unprocessable Entity', $body['title'] ?? null);
        $this->assertIsArray($body['errors'] ?? null);
        $this->assertSame('required', $body['errors']['email'] ?? null);
    }
}
