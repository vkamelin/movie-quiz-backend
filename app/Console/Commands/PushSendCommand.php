<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Kernel;
use App\Helpers\Database;
use App\Helpers\Push;
use PDO;

/**
 * Команда отправки push-сообщений пользователям Telegram.
 */
final class PushSendCommand extends Command
{
    public string $signature = 'push:send';
    public string $description = 'Отправить push-сообщение выбранным пользователям Telegram';

    /**
     * Формирует список получателей и ставит текстовые сообщения в очередь.
     *
     * Доступные опции: --all | --user=1,2 | --username=name1,name2 | --group=1,devs
     *
     * @param array<int,string> $arguments Текст сообщения и опции выбора аудитории
     * @param Kernel $kernel Ядро (не используется)
     * @return int Код выхода
     */
    public function handle(array $arguments, Kernel $kernel): int
    {
        $message = '';
        $options = [];

        foreach ($arguments as $arg) {
            if (str_starts_with($arg, '--')) {
                [$key, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, '');
                $options[$key] = $value;
            } elseif ($message === '') {
                $message = $arg;
            }
        }

        if ($message === '') {
            echo 'Message text is required.' . PHP_EOL;
            return 1;
        }

        $pdo = Database::getInstance();
        $chatIds = [];

        // Все пользователи
        if (array_key_exists('all', $options)) {
            // Получаем всех не заблокированных пользователей
            $stmt = $pdo->query('SELECT user_id FROM telegram_users WHERE is_user_banned = 0 AND is_bot_banned = 0');
            $chatIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } else {
            // Пользователи по ID
            if (isset($options['user']) && $options['user'] !== '') {
                $ids = array_filter(array_map('trim', explode(',', $options['user'])), static fn ($v) => $v !== '');
                if ($ids !== []) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $pdo->prepare("SELECT user_id FROM telegram_users WHERE user_id IN ($placeholders)");
                    $stmt->execute($ids);
                    $chatIds = array_merge($chatIds, $stmt->fetchAll(PDO::FETCH_COLUMN));
                }
            }

            // Пользователи по username
            if (isset($options['username']) && $options['username'] !== '') {
                $names = array_filter(array_map('trim', explode(',', $options['username'])), static fn ($v) => $v !== '');
                if ($names !== []) {
                    $placeholders = implode(',', array_fill(0, count($names), '?'));
                    $stmt = $pdo->prepare("SELECT user_id FROM telegram_users WHERE username IN ($placeholders)");
                    $stmt->execute($names);
                    $chatIds = array_merge($chatIds, $stmt->fetchAll(PDO::FETCH_COLUMN));
                }
            }

            // Группы пользователей
            if (isset($options['group']) && $options['group'] !== '') {
                $groups = array_filter(array_map('trim', explode(',', $options['group'])), static fn ($v) => $v !== '');
                if ($groups !== []) {
                    $numeric = array_filter($groups, static fn ($g) => ctype_digit($g));
                    $names = array_diff($groups, $numeric);
                    $conditions = [];
                    $params = [];
                    if ($numeric !== []) {
                        $conditions[] = 'g.id IN (' . implode(',', array_fill(0, count($numeric), '?')) . ')';
                        $params = array_merge($params, $numeric);
                    }
                    if ($names !== []) {
                        $conditions[] = 'g.name IN (' . implode(',', array_fill(0, count($names), '?')) . ')';
                        $params = array_merge($params, $names);
                    }
                    if ($conditions !== []) {
                        $sql = 'SELECT tu.user_id FROM telegram_user_groups g '
                            . 'JOIN telegram_user_group_user ugu ON g.id = ugu.group_id '
                            . 'JOIN telegram_users tu ON tu.id = ugu.user_id '
                            . 'WHERE ' . implode(' OR ', $conditions);
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        $chatIds = array_merge($chatIds, $stmt->fetchAll(PDO::FETCH_COLUMN));
                    }
                }
            }
        }

        $chatIds = array_values(array_unique(array_map('intval', $chatIds)));

        if ($chatIds === []) {
            echo 'No recipients found.' . PHP_EOL;
            return 1;
        }

        foreach ($chatIds as $id) {
            Push::text($id, $message);
        }

        echo 'Messages queued: ' . count($chatIds) . PHP_EOL;
        return 0;
    }
}
