<?php

declare(strict_types=1);

namespace App\Helpers;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Класс для работы с базой данных
 *
 * @package App\Classes
 */
/**
 * Класс-обёртка для подключения к базе данных (PDO) с ретраями.
 *
 * Отвечает за хранение singleton-инстанса PDO, проверку живости соединения
 * и корректную инициализацию параметров подключения.
 */
class Database
{
    /**
     * @var PDO|null
     *   — NULL пока не было успешного подключения
     *   — PDO при удачном подключении
     */
    private static ?PDO $instance = null;

    /** Максимальное число попыток подключения */
    private const int MAX_TRIES = 3;
    /** Секунд ждать между повторами */
    private const int RETRY_DELAY = 1;

    private static int $lastPing = 0;

    /**
     * Возвращает живой PDO-инстанс или бросает RuntimeException.
     *
     * @return PDO
     * @throws RuntimeException
     */
    /**
     * Возвращает singleton PDO. Переподключается, если соединение умерло.
     *
     * @return PDO Подготовленный инстанс PDO
     * @throws RuntimeException При невозможности подключиться
     */
    public static function getInstance(): PDO
    {
        // Если ещё нет инстанса или предыдущее соединение «мертвое» — обновляем
        if (
            self::$instance === null
            || !self::isConnectionAlive(self::$instance)
        ) {
            $config = self::loadConfig();
            self::$instance = self::connectWithRetry($config);
        }

        return self::$instance;
    }

    /**
     * Проверяет «живость» PDO через SELECT 1.
     *
     * @param PDO $pdo Инстанс PDO
     *
     * @return bool true если соединение живо
     */
    /**
     * Пингует соединение лёгким запросом SELECT 1 (не чаще раза в минуту).
     *
     * @param PDO $pdo Активное соединение
     * @return bool true если соединение живо, иначе false
     */
    private static function isConnectionAlive(PDO $pdo): bool
    {
        // пингуем не чаще чем раз в 60 секунд
        if (time() - self::$lastPing < 60) {
            return true;
        }
        self::$lastPing = time();
        try {
            return ((int)$pdo->query('SELECT 1')->fetchColumn() === 1);
        } catch (PDOException $e) {
            Logger::warning("Ping не прошёл: {$e->getMessage()}", ['exception' => $e]);
            return false;
        }
    }

    /**
     * Пробует подключиться до MAX_TRIES раз, кидает исключение при неудаче.
     *
     * @param array{dsn:string, user:string, password:string, options:array} $config
     * @return PDO
     * @throws RuntimeException
     */
    /**
     * Подключается к БД с несколькими попытками (MAX_TRIES) и задержкой между ними.
     *
     * @param array{dsn:string,user:string,password:string,options:array} $config Параметры подключения
     * @return PDO Активное соединение
     * @throws RuntimeException Если исчерпаны попытки
     */
    private static function connectWithRetry(array $config): PDO
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_TRIES; $attempt++) {
            try {
                $pdo = new PDO(
                    $config['dsn'],
                    $config['user'],
                    $config['password'],
                    $config['options']
                );
                // Небольшой «пинг» сразу после подключения
                if (!self::isConnectionAlive($pdo)) {
                    throw new PDOException('Ping после подключения не прошёл');
                }
                return $pdo;
            } catch (PDOException $e) {
                $lastException = $e;
                Logger::warning(
                    "Попытка #{$attempt} подключения не удалась: {$e->getMessage()}",
                    ['exception' => $e]
                );
                // Ждём перед следующим повтором, если он будет
                if ($attempt < self::MAX_TRIES) {
                    sleep(self::RETRY_DELAY);
                }
            }
        }

        // Все попытки исчерпаны — фатальная ошибка
        Logger::error(
            'Не удалось подключиться к БД после ' . self::MAX_TRIES . ' попыток',
            ['exception' => $lastException]
        );
        throw new RuntimeException('Невозможно подключиться к базе данных. ' . $lastException->getMessage());
    }

    /**
     * Загружает параметры подключения из окружения.
     *
     * @return array{dsn:string, user:string, password:string, options:array}
     */
    /**
     * Формирует конфигурацию подключения из переменных окружения.
     *
     * @return array{dsn:string,user:string,password:string,options:array} Параметры подключения
     */
    private static function loadConfig(): array
    {
        $connectionType = $_ENV['DB_CONNECTION_TYPE'] ?? 'tcp';
        $dbName = $_ENV['DB_NAME'] ?? '';
        $user = $_ENV['DB_USER'] ?? '';
        $password = $_ENV['DB_PASS'] ?? '';

        if ($connectionType === 'socket') {
            $socket = $_ENV['DB_SOCKET'] ?? '';
            $dsn = "mysql:unix_socket={$socket};dbname={$dbName};charset=utf8mb4";
        } else {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $host,
                $port,
                $dbName
            );
        }

        $options = [
            // Ошибки в виде исключений
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // fetch_assoc по умолчанию
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // нативные prepare
            PDO::ATTR_EMULATE_PREPARES => false,
            // кодировка
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',

            // ПЕРЕИСПОЛЬЗОВАНИЕ соединений
            PDO::ATTR_PERSISTENT => true,
            // Таймаут на подключение (секунды)
            PDO::ATTR_TIMEOUT => 5,
        ];

        return [
            'dsn' => $dsn,
            'user' => $user,
            'password' => $password,
            'options' => $options,
        ];
    }
}
