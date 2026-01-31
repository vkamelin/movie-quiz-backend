<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTgPreCheckoutTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `tg_pre_checkout` (
            `pre_checkout_query_id` VARCHAR(255) PRIMARY KEY COMMENT 'Идентификатор pre-checkout запроса',
            `from_user_id` BIGINT NOT NULL COMMENT 'Пользователь, инициировавший оплату',
            `currency` VARCHAR(10) NOT NULL COMMENT 'Валюта платежа',
            `total_amount` BIGINT NOT NULL COMMENT 'Сумма в минимальных единицах валюты',
            `invoice_payload` TEXT NOT NULL COMMENT 'Платёжный payload разработчика',
            `shipping_option_id` TEXT NULL COMMENT 'Выбранная опция доставки',
            `order_info` JSON NULL COMMENT 'Информация о заказе в формате JSON',
            `received_at` TIMESTAMP NOT NULL COMMENT 'Время получения запроса',
            CHECK (JSON_VALID(order_info))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}

