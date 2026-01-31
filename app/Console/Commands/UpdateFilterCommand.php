<?php

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Kernel;
use App\Helpers\RedisHelper;
use RedisException;

/**
 * Команда управления фильтрами обновлений в Redis.
 */
class UpdateFilterCommand extends Command
{
    public string $signature = 'filter:update';
    public string $description = 'Manage update filter lists in Redis';

    /**
     * Добавляет или удаляет значение в выбранном списке фильтров.
     *
     * @param array<int,string> $arguments Параметры команды (подсказка --help)
     * @param Kernel $kernel Ядро (не используется)
     * @return int Код выхода
     */
    public function handle(array $arguments, Kernel $kernel): int
    {
        if (in_array('--help', $arguments, true)) {
            echo 'Manage Telegram update filter lists stored in Redis.' . PHP_EOL;
            echo PHP_EOL;
            echo 'Examples:' . PHP_EOL;
            echo '  php run.php filter:update' . PHP_EOL;
            echo '  php run.php filter:update --help' . PHP_EOL;
            return 0;
        }

        $lists = [
            'allow_updates',
            'deny_updates',
            'allow_chats',
            'deny_chats',
            'allow_commands',
            'deny_commands',
        ];

        $list = trim(readline('List name (' . implode('/', $lists) . '): '));
        if (!in_array($list, $lists, true)) {
            echo 'Invalid list name.' . PHP_EOL;
            return 1;
        }

        $action = strtolower(trim(readline('Operation (add/remove): ')));
        if (!in_array($action, ['add', 'remove'], true)) {
            echo 'Invalid operation.' . PHP_EOL;
            return 1;
        }

        $value = trim(readline('Value: '));
        if ($value === '') {
            echo 'Value is required.' . PHP_EOL;
            return 1;
        }

        $confirm = strtolower(trim(readline("Confirm {$action} of '{$value}' in '{$list}'? [y/N]: ")));
        if ($confirm !== 'y') {
            echo 'Aborted.' . PHP_EOL;
            return 0;
        }

        try {
            $redis = RedisHelper::getInstance();
        } catch (RedisException $e) {
            echo 'Redis connection failed: ' . $e->getMessage() . PHP_EOL;
            return 1;
        }

        $prefix = getenv('TG_FILTERS_REDIS_PREFIX') ?: 'tg:filters';
        $key = sprintf('%s:%s', $prefix, $list);

        if ($action === 'add') {
            $redis->sAdd($key, $value);
            echo "Added '{$value}' to '{$list}'." . PHP_EOL;
        } else {
            $redis->sRem($key, $value);
            echo "Removed '{$value}' from '{$list}'." . PHP_EOL;
        }

        return 0;
    }
}
