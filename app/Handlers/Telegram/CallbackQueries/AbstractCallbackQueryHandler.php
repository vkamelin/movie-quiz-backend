<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\CallbackQueries;

use App\Helpers\Database;
use App\Helpers\Logger;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
use PDO;

/**
 * Базовый обработчик CallbackQuery из Telegram.
 *
 * Содержит подключение к БД и контракт метода handle().
 */
abstract class AbstractCallbackQueryHandler
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Метод для обработки команды
     *
     * @param Update $update
     */
    abstract public function handle(Update $update): void;

    /**
     * Отправляет ответ на CallbackQuery
     *
     * @param int $chatId Идентификатор пользователя, который вызвал CallbackQuery
     * @param int $callbackQueryId Уникальный идентификатор запроса, на который должен быть дан ответ
     * @param string $text Текст уведомления. Если не указано иное, пользователю ничего не будет показано, 0-200 символов
     * @param bool $showAlert Если значение равно True, клиент отобразит пользователю предупреждение вместо уведомления
     *                        в верхней части экрана чата. По умолчанию установлено значение false.
     * @param string $url URL-адрес, который будет открыт клиентом пользователя. Если вы создали игру и приняли условия
     *                    с помощью @BotFather, укажите URL-адрес, который открывает вашу игру - обратите внимание, что
     *                    это сработает, только если запрос будет отправлен с кнопки callback_game. В противном случае
     *                    вы можете использовать ссылки типа t.me/your_bot?start=XXXX, которые открывают вашего бота с
     *                    помощью параметра.
     */
    public function answerCallbackQuery(int $chatId, int $callbackQueryId, string $text = '', bool $showAlert = false, string $url = ''): void
    {
        $data = [
            'callback_query_id' => $callbackQueryId,
            'show_alert' => $showAlert,
        ];

        // Если есть текст, то добавляем его
        if (!empty($text)) {
            if (mb_strlen($text) < 200) {
                $data['text'] = $text;
            } else {
                Logger::warning("Текст answerCallbackQuery cлишком длинный. CallbackQueryId: {$callbackQueryId}. Текст: {$text}");
            }
        }

        // Если есть url, то добавляем его
        if (!empty($url)) {
            $data['url'] = $url;
        }

        $response = Request::answerCallbackQuery($data);
    }
}
