<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\Dashboard\ShippingQueriesController;
use PDO;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class ShippingQueriesControllerTest extends TestCase
{
    private PDO $db;
    private ShippingQueriesController $controller;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('CREATE TABLE tg_shipping_queries (shipping_query_id TEXT PRIMARY KEY, from_user_id INTEGER, invoice_payload TEXT, shipping_address TEXT, received_at TEXT)');
        $this->db->exec("INSERT INTO tg_shipping_queries (shipping_query_id, from_user_id, invoice_payload, shipping_address, received_at) VALUES ('sq1', 1, 'payload', '{}', '2024-01-01 00:00:00')");
        $this->controller = new ShippingQueriesController($this->db);
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
        $this->assertSame('sq1', $payload['data'][0]['shipping_query_id']);
        $this->assertSame('payload', $payload['data'][0]['invoice_payload']);
    }
}
