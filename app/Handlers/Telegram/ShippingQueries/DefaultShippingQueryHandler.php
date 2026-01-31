<?php

declare(strict_types=1);

namespace App\Handlers\Telegram\ShippingQueries;

use App\Helpers\Push;
use JsonException;
use Longman\TelegramBot\Entities\Update;

class DefaultShippingQueryHandler extends AbstractShippingQueryHandler
{
    public function handle(Update $update): void
    {
        $shippingQuery = $update->getShippingQuery();
        if ($shippingQuery === null) {
            return;
        }

        $address = $shippingQuery->getShippingAddress();

        try {
            $addressJson = json_encode($address->getRawData(), JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $addressJson = '{}';
        }

        $stmt = $this->db->prepare(
            'INSERT INTO tg_shipping_queries '
            . '(shipping_query_id, from_user_id, invoice_payload, shipping_address, received_at) '
            . 'VALUES (:shipping_query_id, :from_user_id, :invoice_payload, :shipping_address, NOW())'
        );

        $stmt->execute([
            'shipping_query_id' => $shippingQuery->getId(),
            'from_user_id' => $shippingQuery->getFrom()->getId(),
            'invoice_payload' => $shippingQuery->getInvoicePayload(),
            'shipping_address' => $addressJson,
        ]);

        $this->answerShippingQuery($shippingQuery->getId(), true, []);

        Push::text((int) $shippingQuery->getFrom()->getId(), 'Адрес доставки сохранен.');
    }
}
