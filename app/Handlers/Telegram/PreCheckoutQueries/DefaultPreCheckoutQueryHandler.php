<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\PreCheckoutQueries;

use App\Helpers\Push;
use JsonException;
use Longman\TelegramBot\Entities\Update;

class DefaultPreCheckoutQueryHandler extends AbstractPreCheckoutQueryHandler
{
    public function handle(Update $update): void
    {
        $preCheckoutQuery = $update->getPreCheckoutQuery();
        if ($preCheckoutQuery === null) {
            return;
        }

        $orderInfo = $preCheckoutQuery->getOrderInfo();

        try {
            $orderInfoJson = $orderInfo !== null
                ? json_encode($orderInfo->getRawData(), JSON_THROW_ON_ERROR)
                : null;
        } catch (JsonException $e) {
            $orderInfoJson = null;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO tg_pre_checkout '
            . '(pre_checkout_query_id, from_user_id, currency, total_amount, invoice_payload, shipping_option_id, order_info, received_at) '
            . 'VALUES (:pre_checkout_query_id, :from_user_id, :currency, :total_amount, :invoice_payload, :shipping_option_id, :order_info, NOW())'
        );

        $stmt->execute([
            'pre_checkout_query_id' => $preCheckoutQuery->getId(),
            'from_user_id' => $preCheckoutQuery->getFrom()->getId(),
            'currency' => $preCheckoutQuery->getCurrency(),
            'total_amount' => $preCheckoutQuery->getTotalAmount(),
            'invoice_payload' => $preCheckoutQuery->getInvoicePayload(),
            'shipping_option_id' => $preCheckoutQuery->getShippingOptionId(),
            'order_info' => $orderInfoJson,
        ]);

        $this->answerPreCheckoutQuery($preCheckoutQuery->getId(), true);

        Push::text((int) $preCheckoutQuery->getFrom()->getId(), 'Ваш заказ принят.');
    }
}
