<?php

declare(strict_types=1);

use App\Helpers\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testValidEmailAndPassword(): void
    {
        $res = Validator::validate([
            'email' => ' user@example.com ',
            'password' => 'secret',
        ], [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|length:1,200',
        ]);

        $this->assertTrue($res['ok'] ?? false);
        $this->assertSame('user@example.com', $res['data']['email'] ?? null);
        $this->assertSame('secret', $res['data']['password'] ?? null);
    }

    public function testMissingEmailReturnsError(): void
    {
        $res = Validator::validate(['password' => 'x'], [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        $this->assertFalse($res['ok'] ?? true);
        $this->assertArrayHasKey('email', $res['errors'] ?? []);
        $this->assertSame('required', $res['errors']['email']);
    }

    public function testEnumRule(): void
    {
        $res = Validator::validate(['role' => 'user'], [
            'role' => 'required|string|enum:admin,manager',
        ]);
        $this->assertFalse($res['ok'] ?? true);
        $this->assertSame('enum:admin,manager', $res['errors']['role'] ?? null);
    }

    public function testIntMinMax(): void
    {
        $res = Validator::validate(['age' => '17'], [
            'age' => 'required|int|min:18|max:120',
        ]);
        $this->assertFalse($res['ok'] ?? true);
        $this->assertSame('min:18', $res['errors']['age'] ?? null);

        $res2 = Validator::validate(['age' => 25], [
            'age' => 'required|int|min:18|max:120',
        ]);
        $this->assertTrue($res2['ok'] ?? false);
        $this->assertSame(25, $res2['data']['age'] ?? null);
    }

    public function testLengthRule(): void
    {
        $res = Validator::validate(['name' => 'abcdef'], [
            'name' => 'string|length:1,5',
        ]);
        $this->assertFalse($res['ok'] ?? true);
        $this->assertSame('length:1,5', $res['errors']['name'] ?? null);
    }
}
