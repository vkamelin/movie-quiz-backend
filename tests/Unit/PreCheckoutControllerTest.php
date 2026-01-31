<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\Dashboard\PreCheckoutController;
use PDO;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class PreCheckoutControllerTest extends TestCase
{
    private PDO $db;
    private PreCheckoutController $controller;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('CREATE TABLE tg_pre_checkout (pre_checkout_query_id TEXT PRIMARY KEY, from_user_id INTEGER, currency TEXT, total_amount INTEGER, invoice_payload TEXT, shipping_option_id TEXT, order_info TEXT, received_at TEXT)');
        $this->db->exec("INSERT INTO tg_pre_checkout (pre_checkout_query_id, from_user_id, currency, total_amount, invoice_payload, shipping_option_id, order_info, received_at) VALUES ('abc', 1, 'USD', 1000, 'payload', 'ship1', '{}', '2024-01-01 00:00:00')");
        $this->controller = new PreCheckoutController($this->db);
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
        $this->assertSame('abc', $payload['data'][0]['pre_checkout_query_id']);
        $this->assertSame(1000, (int)$payload['data'][0]['total_amount']);
    }
}
