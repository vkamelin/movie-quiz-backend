<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;
use Throwable;

/**
 * Класс для отправки пушей в Телеграм-ботом
 *
 * @package   App\Classes
 * @author    Vitaliy Kamelin <v.kamelin@gmail.com>
 * @version   1.0
 * @copyright Copyright (c) 2024, Vitaliy Kamelin
 * @license   proprietary
 */
/**
 * Помощник для постановки задач на отправку сообщений в Telegram через очередь.
 *
 * Предоставляет методы для разных типов сообщений (текст, фото, видео и т.д.)
 * и единый внутренний механизм планирования/приоритезации отправки.
 */
class Push
{
    /**
     * Checks if a column exists in the given table.
     */
    private static function columnExists(\PDO $db, string $table, string $column): bool
    {
        try {
            $stmt = $db->prepare("SHOW COLUMNS FROM `{$table}` LIKE :col");
            $stmt->execute(['col' => $column]);
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
    }
    /**
     * Метод добавляет в очередь текстовое сообщение в Телеграм
     *
     * @param int    $chatId   Id чата
     * @param string $text     Текст сообщения
     * @param string $type     Тип сообщения. Возможные значения: 'push', 'message'
     * @param int    $priority Приоритет сообщения. 2 - самый низкий, 0 - самый высокий. По-умолчанию 2
     * @param array  $options  Дополнительные параметры из API https://core.telegram.org/bots/api#sendmessage
     *
     * @return bool Удалось ли добавить в очередь на отправку. True - удалось, False - не удалось
     */
    /**
     * Ставит в очередь отправку текстового сообщения.
     *
     * @param int $chatId Идентификатор чата
     * @param string $text Текст сообщения (HTML)
     * @param string $type Тип сообщения для аналитики
     * @param int $priority Приоритет в очереди (2 — высокий)
     * @param array $options Дополнительные опции Telegram API
     * @param string|null $sendAfter Отложенная отправка (ISO8601 или Y-m-d H:i:s)
     * @return bool Успешно ли поставлено в очередь
     */
    public static function text(
        int $chatId,
        string $text,
        string $type = 'message',
        int $priority = 2,
        array $options = [],
        ?string $sendAfter = null
    ): bool {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'html',
        ];

        $data = array_merge($data, $options);
        $data = array_filter($data, static fn ($value) => $value !== null);

        return self::push($chatId, 'sendMessage', $data, $type, $priority, $sendAfter);
    }

    /**
     * Метод добавляет в очередь изображение по ссылке в Телеграм
     *
     * @param int         $chatId    Id чата
     * @param array|string $photo    URL изображения, fileId или результат MediaBuilder::buildInputMedia()
     * @param string      $caption   Текст подписи
     * @param string      $type      Тип сообщения. Возможные значения: 'push', 'message'
     * @param int         $priority  Приоритет сообщения. 2 - самый низкий, 0 - самый высокий. По-умолчанию 2
     * @param array       $options   Дополнительные параметры из API https://core.telegram.org/bots/api#sendphoto
     * @param string|null $sendAfter Время после которого отправлять сообщение в Телеграм
     *
     * @return bool Удалось ли добавить в очередь на отправку. True - удалось, False - не удалось
     */
    /**
     * Ставит в очередь отправку фото.
     *
     * @param int $chatId Идентификатор чата
     * @param array|string $photo URL/файл/file_id или структура из MediaBuilder
     * @param string $caption Подпись (HTML)
     * @param string $type Тип сообщения для аналитики
     * @param int $priority Приоритет в очереди (2 — высокий)
     * @param array $options Дополнительные опции Telegram API
     * @param string|null $sendAfter Отложенная отправка
     * @return bool Успешно ли поставлено в очередь
     */
    public static function photo(
        int $chatId,
        array|string $photo,
        string $caption = '',
        string $type = 'photo',
        int $priority = 2,
        array $options = [],
        ?string $sendAfter = null
    ): bool {
        if (!isset($options['parse_mode'])) {
            $options['parse_mode'] = 'html';
        }
        $data = MediaBuilder::prepareMediaData($chatId, 'photo', $photo, $caption, $options);
        $data = array_filter($data, static fn ($value) => $value !== null);

        return self::push($chatId, 'sendPhoto', $data, $type, $priority, $sendAfter);
    }

    /**
     * Метод добавляет в очередь аудио в Телеграм
     *
     * @param int          $chatId   Id чата
     * @param array|string $audio    URL аудио, fileId или результат MediaBuilder::buildInputMedia().
     * @param string       $caption  Текст подписи
     * @param string $type     Тип сообщения. Возможные значения: 'push', 'message'
     * @param int    $priority Приоритет сообщения. 2 - самый низкий, 0 - самый высокий. По-умолчанию 2
     * @param array  $options  Дополнительные параметры из API https://core.telegram.org/bots/api#sendaudio
     *
     * @return bool Удалось ли добавить в очередь на отправку. True - удалось, False - не удалось
     */
    public static function audio(
        int $chatId,
        array|string $audio,
        string $caption = '',
        string $type = 'audio',
        int $priority = 2,
        array $options = [],
        ?string $sendAfter = null
    ): bool {
        if (!isset($options['parse_mode'])) {
            $options['parse_mode'] = 'html';
        }
        $data = MediaBuilder::prepareMediaData($chatId, 'audio', $audio, $caption, $options);
        $data = array_filter($data, static fn ($value) => $value !== null);

        return self::push($chatId, 'sendAudio', $data, $type, $priority, $sendAfter);
    }

    /**
     * Метод добавляет в очередь документа в Телеграм
     *
     * @param int          $chatId   Id чата
     * @param array|string $document URL документа, fileId или результат MediaBuilder::buildInputMedia()
     * @param string       $caption  Текст подписи
     * @param string $type     Тип сообщения. Возможные значения: 'push', 'message'
     * @param int    $priority Приоритет сообщения. 2 - самый низкий, 0 - самый высокий. По-умолчанию 2
     * @param array  $options  Дополнительные параметры из API https://core.telegram.org/bots/api#senddocument
     *
     * @return bool Удалось ли добавить в очередь на отправку. True - удалось, False - не удалось
     */
    public static function document(
        int $chatId,
        array|string $document,
        string $caption = '',
        string $type = 'document',
        int $priority = 2,
        array $options = [],
        ?string $sendAfter = null
    ): bool {
        $options = array_merge(['disable_content_type_detection' => true], $options);
        if (!isset($options['parse_mode'])) {
            $options['parse_mode'] = 'html';
        }

        $data = MediaBuilder::prepareMediaData($chatId, 'document', $document, $caption, $options);
        $data = array_filter($data, static fn ($value) => $value !== null);

        return self::push($chatId, 'sendDocument', $data, $type, $priority, $sendAfter);
    }

    /**
     * Метод добавляет в очередь видео в Телеграм
     *
     * @param int          $chatId   Id чата
     * @param array|string $video    URL видео, fileId или результат MediaBuilder::buildInputMedia()
     * @param string       $caption  Текст подписи
     * @param string $type     Тип сообщения. Возможные значения: 'push', 'message'
     * @param int    $priority Приоритет сообщения. 2 - самый низкий, 0 - самый высокий. По-умолчанию 2
     * @param array  $options  Дополнительные параметры из API https://core.telegram.org/bots/api#sendvideo
     *
     * @return bool Удалось ли добавить в очередь на отправку. True - удалось, False - не удалось
     */
    public static function video(
        int $chatId,
        array|string $video,
        string $caption = '',
        string $type = 'video',
        int $priority = 2,
        array $options = [],
        ?string $sendAfter = null
    ): bool {
        if (!isset($options['parse_mode'])) {
            $options['parse_mode'] = 'html';
        }
        $data = MediaBuilder::prepareMediaData($chatId, 'video', $video, $caption, $options);
        $data = array_filter($data, static fn ($value) => $value !== null);

        return self::push($chatId, 'sendVideo', $data, $type, $priority, $sendAfter);
    }

    /**
     * Метод добавляет в очередь группу медиафайлов в Телеграм
     *
     * @param int         $chatId    Id чата
     * @param array       $media     Массив, сформированный через MediaBuilder::buildInputMedia()
     * @param string      $type      Тип сообщения. Возможные значения: 'push', 'message'
     * @param int         $priority  Приоритет сообщения. 2 - самый низкий, 0 - самый высокий. По-умолчанию 2
     * @param array       $options   Дополнительные параметры из API https://core.telegram.org/bots/api#sendmediagroup
     * @param string|null $sendAfter Время после которого отправлять сообщение в Телеграм
     *
     * @return bool Удалось ли добавить в очередь на отправку. True - удалось, False - не удалось
     */
    /**
     * Ставит в очередь отправку медиагруппы (альбом).
     *
     * @param int $chatId Идентификатор чата
     * @param array $media Массив элементов InputMedia*
     * @param string $type Тип сообщения для аналитики
     * @param int $priority Приоритет в очереди (2 — высокий)
     * @param array $options Дополнительные опции Telegram API
     * @param string|null $sendAfter Отложенная отправка
     * @return bool Успешно ли поставлено в очередь
     */
    public static function mediaGroup(
        int $chatId,
        array $media,
        string $type = 'media-group',
        int $priority = 2,
        array $options = [],
        ?string $sendAfter = null
    ): bool {
        $media = array_map(static function ($item) {
            if (is_array($item)) {
                if (!isset($item['parse_mode']) && !empty($item['caption'])) {
                    $item['parse_mode'] = 'html';
                }
                return array_filter($item, static fn ($value) => $value !== null);
            }
            return $item;
        }, $media);

        $data = [
            'chat_id' => $chatId,
            'media' => $media,
        ];

        $data = array_merge($data, $options);
        $data = array_filter($data, static fn ($value) => $value !== null);

        return self::push($chatId, 'sendMediaGroup', $data, $type, $priority, $sendAfter);
    }

    /**
     * Метод добавляет в очередь стикер в Телеграм
     *
     * @param int         $chatId    Id чата
     * @param string      $sticker   Стикер
     * @param string      $type      Тип сообщения. Возможные значения: 'push', 'message'
     * @param int         $priority  Приоритет сообщения. 2 - самый низкий, 0 - самый высокий. По-умолчанию 2
     * @param array       $options   Дополнительные параметры из API https://core.telegram.org/bots/api#sendsticker
     * @param string|null $sendAfter Дата и время, после которой отправляется запрос
     *
     * @return bool Удалось ли добавить в очередь на отправку. True - удалось, False - не удалось
     */
    /**
     * Ставит в очередь отправку стикера.
     *
     * @param int $chatId Идентификатор чата
     * @param string $sticker FileID/URL стикера
     * @param string $type Тип сообщения для аналитики
     * @param int $priority Приоритет в очереди (2 — высокий)
     * @param array $options Дополнительные опции Telegram API
     * @param string|null $sendAfter Отложенная отправка
     * @return bool Успешно ли поставлено в очередь
     */
    public static function sticker(
        int $chatId,
        string $sticker,
        string $type = 'sticker',
        int $priority = 2,
        array $options = [],
        ?string $sendAfter = null
    ): bool {
        $data = [
            'chat_id' => $chatId,
            'sticker' => $sticker,
        ];

        $data = array_merge($data, $options);
        $data = array_filter($data, static fn ($value) => $value !== null);

        return self::push($chatId, 'sendSticker', $data, $type, $priority, $sendAfter);
    }

    /**
     * Метод добавляет в очередь анимацию в Телеграм
     *
     * @param int         $chatId    Id чата
     * @param string      $animation Анимация в формате GIF или H.264/MPEG-4 AVC размером до 50Мб
     * @param string      $caption   Краткое описание анимации до 1024 символов
     * @param string      $type      Тип сообщения. Возможные значения: 'push', 'message'
     * @param int         $priority  Приоритет сообщения. 2 - самый низкий, 0 - самый высокий. По-умолчанию 2
     * @param array       $options   Дополнительные параметры из API https://core.telegram.org/bots/api#sendanimation
     * @param string|null $sendAfter Дата и время, после которой отправляется запрос
     *
     * @return bool Удалось ли добавить в очередь на отправку. True - удалось, False - не удалось
     */
    public static function animation(
        int $chatId,
        string $animation,
        string $caption = '',
        string $type = 'animation',
        int $priority = 2,
        array $options = [],
        ?string $sendAfter = null
    ): bool {
        $data = [
            'chat_id' => $chatId,
            'animation' => $animation,
        ];

        if (!empty($caption)) {
            $data['caption'] = $caption;
        }

        if (!isset($options['parse_mode'])) {
            $options['parse_mode'] = 'html';
        }
        $data = array_merge($data, $options);
        $data = array_filter($data, static fn ($value) => $value !== null);

        return self::push($chatId, 'sendAnimation', $data, $type, $priority, $sendAfter);
    }

    /**
     * Метод добавляет в очередь голосовое сообщение в Телеграм
     *
     * @param int         $chatId    Id чата
     * @param string      $voice     Звуковой файл .OGG кодированный в OPUS, или .MP3 format, или .M4A format
     * @param string      $type      Тип сообщения. Возможные значения: 'push', 'message'
     * @param int         $priority  Приоритет сообщения. 2 - самый низкий, 0 - самый высокий. По-умолчанию 2
     * @param array       $options   Дополнительные параметры из API https://core.telegram.org/bots/api#sendvoice
     * @param string|null $sendAfter Дата и время, после которой отправляется запрос
     *
     * @return bool Удалось ли добавить в очередь на отправку. True - удалось, False - не удалось
     */
    public static function voice(
        int $chatId,
        string $voice,
        string $type = 'voice',
        int $priority = 2,
        array $options = [],
        ?string $sendAfter = null
    ): bool {
        $data = [
            'chat_id' => $chatId,
            'voice' => $voice,
        ];

        if (!isset($options['parse_mode'])) {
            $options['parse_mode'] = 'html';
        }
        $data = array_merge($data, $options);

        return self::push($chatId, 'sendVoice', $data, $type, $priority, $sendAfter);
    }

    /**
     * Метод добавляет в очередь видео кружочек в Телеграм
     *
     * @param int         $chatId    Id чата
     * @param string      $videoNote Квадратный видеофайл в формате .MP4 до 1Мб
     * @param string      $type      Тип сообщения. Возможные значения: 'push', 'message'
     * @param int         $priority  Приоритет сообщения. 2 - самый низкий, 0 - самый высокий. По-умолчанию 2
     * @param array       $options   Дополнительные параметры из API https://core.telegram.org/bots/api#sendvideonote
     * @param string|null $sendAfter Дата и время, после которой отправляется запрос
     *
     * @return bool Удалось ли добавить в очередь на отправку. True - удалось, False - не удалось
     */
    public static function videoNote(
        int $chatId,
        string $videoNote,
        string $type = 'video-note',
        int $priority = 2,
        array $options = [],
        ?string $sendAfter = null
    ): bool {
        $data = [
            'chat_id' => $chatId,
            'video_note' => $videoNote,
        ];

        if (!isset($options['parse_mode'])) {
            $options['parse_mode'] = 'html';
        }
        $data = array_merge($data, $options);

        return self::push($chatId, 'sendVideoNote', $data, $type, $priority, $sendAfter);
    }

    /**
     * Добавляет в очередь инвойс в Телеграм.
     *
     * @param int         $chatId    Id чата
     * @param array       $invoiceData Данные инвойса из API https://core.telegram.org/bots/api#sendinvoice
     * @param string      $type      Тип сообщения. Возможные значения: 'push', 'message'
     * @param int         $priority  Приоритет сообщения. 2 - самый низкий, 0 - самый высокий. По-умолчанию 2
     * @param string|null $sendAfter Дата и время, после которой отправляется запрос
     *
     * @return bool Удалось ли добавить в очередь на отправку. True - удалось, False - не удалось
     */
    public static function invoice(
        int $chatId,
        array $invoiceData,
        string $type = 'invoice',
        int $priority = 2,
        ?string $sendAfter = null
    ): bool {
        $data = array_merge(['chat_id' => $chatId], $invoiceData);

        return self::push($chatId, 'sendInvoice', $data, $type, $priority, $sendAfter);
    }

    /**
     * Метод добавляет в очередь произвольный запрос в Телеграм из документации
     * https://core.telegram.org/bots/api#available-methods
     *
     * @param string      $method    Метод отправки запроса
     * @param array       $data      Данные запроса
     * @param int|null    $chatId    Id чата
     * @param string      $type      Тип сообщения. Возможные значения: 'push', 'message'
     * @param int         $priority  Приоритет сообщения. 2 - самый низкий, 0 - самый высокий. По-умолчанию 2
     * @param string|null $sendAfter Дата и время, после которой отправляется запрос
     *
     * @return bool Удалось ли добавить в очередь на отправку. True - удалось, False - не удалось
     */
    public static function custom(
        string $method,
        array $data,
        ?int $chatId = null,
        string $type = 'push',
        int $priority = 2,
        ?string $sendAfter = null,
        ?int $scheduledId = null
    ): bool {
        if (!isset($data['parse_mode']) && (isset($data['text']) || isset($data['caption']))) {
            $data['parse_mode'] = 'html';
        }
        return self::push($chatId, $method, $data, $type, $priority, $sendAfter, $scheduledId);
    }

    /**
     * @param int|null    $chatId    ID чата
     * @param string      $method    Метод пуша
     * @param array       $data      Данные для пуша
     * @param string      $type      Тип пуша для различения отдельных пушей и рассылок
     * @param int         $priority  Приоритет сообщения. 2 - самый низкий, 0 - самый высокий. По-умолчанию 2
     * @param string|null $sendAfter Дата и время, после которой пуш будет выполнен
     *
     * @return bool True, если пуш прошел успешно, иначе False
     */
    private static function push(
        ?int $chatId,
        string $method,
        array $data,
        string $type = 'push',
        int $priority = 2,
        ?string $sendAfter = null,
        ?int $scheduledId = null
    ): bool {
        try {
            $redis = RedisHelper::getInstance();
            $db = Database::getInstance();

            if ($redis !== null && $db !== null) {
                // Данные для вставки в БД
                $insertData = [
                    'user_id' => $chatId,
                    'method' => $method,
                    'data' => json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'type' => $type,
                    'priority' => $priority,
                ];

                // Если указано будущее время отправки — используем таблицу расписания
                if (!empty($sendAfter) && strtotime($sendAfter) !== false && strtotime($sendAfter) > time()) {
                    try {
                        $stmt = $db->prepare(
                            'INSERT INTO `telegram_scheduled_messages` (`user_id`, `method`, `type`, `data`, `priority`, `send_after`) '
                            . 'VALUES (:user_id, :method, :type, :data, :priority, :send_after)'
                        );
                        $stmt->execute($insertData + ['send_after' => $sendAfter]);
                    } catch (Throwable $e) {
                        Logger::error(
                            "Не удалось добавить запланированное сообщение. {$e->getMessage()}.",
                            ['exception' => $e]
                        );
                        return false;
                    }
                } else {
                    try {
                        $hasScheduledId = self::columnExists($db, 'telegram_messages', 'scheduled_id');
                        if ($scheduledId !== null && $hasScheduledId) {
                            $stmt = $db->prepare(
                                'INSERT INTO `telegram_messages` (`user_id`, `method`, `type`, `scheduled_id`, `data`, `priority`) VALUES (:user_id, :method, :type, :scheduled_id, :data, :priority)'
                            );
                            $stmt->execute($insertData + ['scheduled_id' => $scheduledId]);
                        } else {
                            // Fallback for legacy schema without scheduled_id column
                            $stmt = $db->prepare(
                                'INSERT INTO `telegram_messages` (`user_id`, `method`, `type`, `data`, `priority`) VALUES (:user_id, :method, :type, :data, :priority)'
                            );
                            $stmt->execute($insertData);
                        }

                        // ID записанного сообщения
                        $id = $db->lastInsertId();
                    } catch (Throwable $e) {
                        Logger::error(
                            "Не удалось добавить в очередь пуш в Телеграм. {$e->getMessage()}.",
                            ['exception' => $e]
                        );
                        return false;
                    }

                    // $messageKey = RedisHelper::REDIS_MESSAGE_KEY . ':' . $id; // Ключ сообщения
                    $messageKey = RedisKeyHelper::key('telegram', 'message', (string)$id);
                    //  Ключ очереди сообщений
                    $queueKey = RedisKeyHelper::key(
                        RedisHelper::REDIS_MESSAGES_QUEUE_KEY,
                        (string) $priority
                    );

                    // Добавляем сообщение в Redis с уникальным ключом
                    $redisMessage = $insertData;
                    $redisMessage['key'] = (string)$id;
                    $addMessageResult = $redis->set($messageKey, $redisMessage);

                    if (empty($addMessageResult)) {
                        $errorMessage = 'Не удалось добавить сообщение в Redis.';

                        $stmt = $db->prepare(
                            'UPDATE `telegram_messages` SET `status` = :status, `error` = :error WHERE `id` = :id'
                        );
                        $stmt->execute(['status' => 'error', 'error' => $errorMessage, 'id' => $id]);

                        throw new RuntimeException($errorMessage);
                    }

                    // Добавляем сообщение в очередь с учетом приоритета
                    $messageData = [
                        'id' => $id,
                        'send_after' => $sendAfter ? strtotime($sendAfter) : null,
                        'key' => (string)$id,
                    ];

                    $addQueueResult = $redis->rPush($queueKey, $messageData);

                    if ($addQueueResult === false) {
                        $redis->del($messageKey);

                        $errorMessage = 'Не удалось добавить сообщение в очередь в Redis.';

                        $stmt = $db->prepare(
                            'UPDATE `telegram_messages` SET `status` = :status, `error` = :error WHERE `id` = :id'
                        );
                        $stmt->execute(['status' => 'error', 'error' => $errorMessage, 'id' => $id]);

                        throw new RuntimeException($errorMessage);
                    }
                }

                return true;
            }
        } catch (Throwable $e) {
            Logger::error(
                'Не удалось добавить в очередь пуш в Телеграм.',
                ['exception' => $e]
            );
        }

        return false;
    }
}
