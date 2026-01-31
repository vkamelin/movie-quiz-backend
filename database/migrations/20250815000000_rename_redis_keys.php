<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use App\Helpers\RedisHelper;
use App\Config;

final class RenameRedisKeys extends AbstractMigration
{
    public function up(): void
    {
        $redis = RedisHelper::getInstance();
        $config = Config::getInstance();
        $prefix = $config->get('APP_NAME') . ':' . $config->get('PROJECT_ENV') . ':';
        foreach ($redis->keys('gpt:*') as $key) {
            $redis->rename($key, $prefix . $key);
        }
        foreach ($redis->keys('telegram:*') as $key) {
            $redis->rename($key, $prefix . $key);
        }
        foreach ($redis->keys('rate:*') as $key) {
            $redis->rename($key, $prefix . $key);
        }
    }
}
