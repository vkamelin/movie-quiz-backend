<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\CallbackQueries;

use App\Helpers\Logger;
use App\Helpers\MessageStorage;
use App\Helpers\Push;
use Longman\TelegramBot\Entities\ChatMember\ChatMember;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;

class CheckSubscriptionHandler extends AbstractCallbackQueryHandler
{
    public function handle(Update $update): void
    {
        $channel = '@' . $_ENV['CHANNEL'];
        $userId = $update->getCallbackQuery()->getFrom()->getId();
        $callbackQueryId = (int) $update->getCallbackQuery()->getId();

        $this->answerCallbackQuery($userId, $callbackQueryId);

        $stmt = $this->db->prepare('SELECT * FROM `telegram_users` WHERE `user_id` = :user_id AND `is_subscribed` = 1 LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $isSubscribed = $stmt->fetch();

        if (!empty($isSubscribed)) {
            // Если пользователь уже подписан на канал
            return;
        }

        $response = Request::getChatMember([
            'chat_id' => $channel,
            'user_id' => $userId,
        ]);

        if ($response->isOk()) {
            /**
             * @var ChatMember $chatMember
             */
            $chatMember = $response->getResult();
            $status = $chatMember->getStatus();

            // Проверяем, является ли пользователь членом чата
            $isChatMember = in_array($status, ['creator', 'administrator', 'member']);

            if ($isChatMember) {
                $text = MessageStorage::read('subscribeSuccess') ?? '';
                Push::text($userId, $text, 'subscribed', 1);
            } else {

                $text = MessageStorage::read('subscribeFailed') ?? '';

                // Создаем клавиатуру
                $keyboard = new InlineKeyboard(
                    [
                        [
                            'text' => 'Канал',
                            'url' => 'https://t.me/' . $_ENV['CHANNEL'],
                        ],
                    ],
                    [
                        [
                            'text' => 'Проверить подписку',
                            'callback_data' => 'checkSubscription',
                        ],
                    ],
                );

                Push::text($userId, $text, 'unsubscribed', 1, ['reply_markup' => $keyboard]);
            }

        } else {
            $data = 'Не удалось проверить подписку на канал @yandexedachannel. ' .
                var_export($response->getRawData(), true);

            Logger::warning("Ошибка при проверке подписки. {$data}");
        }
    }
}
