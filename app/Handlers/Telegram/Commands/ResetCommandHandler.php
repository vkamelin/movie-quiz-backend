<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\Commands;

use App\Helpers\Logger;
use App\Helpers\Push;
use Exception;
use Longman\TelegramBot\Entities\Update;
use PDO;

/**
 * @property PDO $db
 */
class ResetCommandHandler extends AbstractCommandHandler
{
    /**
     * @param Update $update
     * @return void
     */
    public function handle(Update $update): void
    {
        $message = $update->getMessage();
        $chatId = $message->getChat()->getId();

        // Начинаем транзакцию
        $this->db->beginTransaction();

        try {
            // Удаляем данные пользователя
            $userStmt = $this->db->prepare('DELETE FROM telegram_users WHERE user_id = :user_id');
            $userStmt->execute([':user_id' => $chatId]);

            // Подтверждаем транзакцию
            $this->db->commit();

            Push::text($chatId, "Данные успешно сброшены для пользователя {$chatId}. Не забудьте отправить команду <code>/start</code>");
        } catch (Exception $e) {
            // Откатываем транзакцию в случае ошибки
            $this->db->rollBack();

            // Логируем ошибку (опционально) и уведомляем пользователя о неудаче
            Logger::error("Ошибка при сбросе данных для пользователя {$chatId}. {$e->getMessage()}");

            // Отправляем сообщение об ошибке пользователю
            Push::text($chatId, 'Произошла ошибка при сбросе данных. Пожалуйста, попробуйте позже.');
        }
    }
}
