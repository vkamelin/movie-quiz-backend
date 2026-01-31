<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Console\Commands\PushSendCommand;
use App\Console\Kernel;
use App\Helpers\Database;
use App\Helpers\RedisHelper;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class PushSendCommandTest extends TestCase
{
    private PDO $db;
    private PushSendCommand $command;

    protected function setUp(): void
    {
        $_ENV['APP_ENV'] = 'test';

        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('CREATE TABLE telegram_users (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, username TEXT, is_user_banned INTEGER DEFAULT 0, is_bot_banned INTEGER DEFAULT 0)');
        $this->db->exec('CREATE TABLE telegram_messages (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, method TEXT, type TEXT, data TEXT, priority INTEGER)');
        $this->db->exec('CREATE TABLE telegram_user_groups (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $this->db->exec('CREATE TABLE telegram_user_group_user (group_id INTEGER, user_id INTEGER)');

        $this->db->exec("INSERT INTO telegram_users (id, user_id, username, is_user_banned, is_bot_banned) VALUES (1, 100, 'alice', 0, 0), (2, 101, 'bob', 0, 0), (3, 102, 'carol', 1, 0)");
        $this->db->exec("INSERT INTO telegram_user_groups (id, name) VALUES (1, 'group1')");
        $this->db->exec('INSERT INTO telegram_user_group_user (group_id, user_id) VALUES (1, 1), (1, 2)');

        $dbRef = new ReflectionClass(Database::class);
        $prop = $dbRef->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, $this->db);

        $redisStub = new class () {
            public array $data = [];
            public function set($key, $value): bool
            {
                $this->data[$key] = $value;
                return true;
            }
            public function rPush($key, $value): bool
            {
                $this->data[$key][] = $value;
                return true;
            }
            public function del($key): int
            {
                unset($this->data[$key]);
                return 1;
            }
        };
        $redisRef = new ReflectionClass(RedisHelper::class);
        $propRedis = $redisRef->getProperty('instance');
        $propRedis->setAccessible(true);
        $propRedis->setValue(null, $redisStub);

        $this->command = new PushSendCommand();
    }

    public function testSendAllUsers(): void
    {
        $kernel = new Kernel();
        $this->command->handle(['Hello', '--all'], $kernel);
        $rows = $this->db->query('SELECT user_id FROM telegram_messages ORDER BY user_id')->fetchAll(PDO::FETCH_COLUMN);
        $this->assertSame([100, 101], array_map('intval', $rows));
    }

    public function testSendByUserId(): void
    {
        $kernel = new Kernel();
        $this->command->handle(['Hi', '--user=100'], $kernel);
        $row = $this->db->query('SELECT user_id FROM telegram_messages')->fetchColumn();
        $this->assertSame(100, (int)$row);
    }

    public function testSendByUsername(): void
    {
        $kernel = new Kernel();
        $this->command->handle(['Yo', '--username=bob'], $kernel);
        $row = $this->db->query('SELECT user_id FROM telegram_messages')->fetchColumn();
        $this->assertSame(101, (int)$row);
    }

    public function testSendByGroup(): void
    {
        $kernel = new Kernel();
        $this->command->handle(['Group message', '--group=group1'], $kernel);
        $rows = $this->db->query('SELECT user_id FROM telegram_messages ORDER BY user_id')->fetchAll(PDO::FETCH_COLUMN);
        $this->assertSame([100, 101], array_map('intval', $rows));
    }
}
