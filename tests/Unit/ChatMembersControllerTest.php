<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\Dashboard\ChatMembersController;
use PDO;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class ChatMembersControllerTest extends TestCase
{
    private PDO $db;
    private ChatMembersController $controller;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('CREATE TABLE chat_members (chat_id INTEGER, user_id INTEGER, role TEXT, state TEXT, PRIMARY KEY(chat_id, user_id))');
        $this->db->exec('CREATE TABLE telegram_users (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, username TEXT, first_name TEXT, last_name TEXT)');
        $this->db->exec("INSERT INTO telegram_users (user_id, username) VALUES (1, 'john')");
        $this->db->exec("INSERT INTO chat_members (chat_id, user_id, role, state) VALUES (100, 1, 'member', 'approved')");
        $this->controller = new ChatMembersController($this->db);
    }

    public function testDataReturnsJson(): void
    {
        $factory = new ServerRequestFactory();
        $req = $factory->createServerRequest('POST', '/');
        $req = $req->withParsedBody(['draw' => 1, 'start' => 0, 'length' => 10]);
        $res = new Response();
        $res = $this->controller->data($req, $res);
        $payload = json_decode((string)$res->getBody(), true);
        $this->assertSame(1, $payload['recordsTotal']);
        $this->assertSame('john', $payload['data'][0]['username']);
        $this->assertSame('member', $payload['data'][0]['role']);
    }
}
