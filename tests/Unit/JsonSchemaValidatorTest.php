<?php

declare(strict_types=1);

use App\Helpers\JsonSchemaValidator;
use PHPUnit\Framework\TestCase;

final class JsonSchemaValidatorTest extends TestCase
{
    public function testLoginSchemaValid(): void
    {
        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['email','password'],
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email', 'minLength' => 3, 'maxLength' => 255],
                'password' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 200],
            ],
        ];
        $res = JsonSchemaValidator::validate(['email' => 'user@example.com', 'password' => 'x'], $schema);
        $this->assertTrue($res['ok'] ?? false);
    }

    public function testLoginSchemaMissingField(): void
    {
        $schema = [
            'type' => 'object',
            'required' => ['email','password'],
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email'],
                'password' => ['type' => 'string', 'minLength' => 1],
            ],
        ];
        $res = JsonSchemaValidator::validate(['email' => 'user@example.com'], $schema);
        $this->assertFalse($res['ok'] ?? true);
        $this->assertArrayHasKey('password', $res['errors'] ?? []);
        $this->assertSame('required', $res['errors']['password'] ?? null);
    }

    public function testAdditionalPropertiesFalse(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => ['a' => ['type' => 'string']],
            'additionalProperties' => false,
        ];
        $res = JsonSchemaValidator::validate(['a' => 'ok', 'b' => 'x'], $schema);
        $this->assertFalse(($res['ok'] ?? true));
        $this->assertArrayHasKey('b', $res['errors'] ?? []);
        $this->assertSame('additionalProperties', $res['errors']['b'] ?? null);
    }
}
