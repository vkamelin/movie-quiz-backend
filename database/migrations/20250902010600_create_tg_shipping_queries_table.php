<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTgShippingQueriesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `tg_shipping_queries` (
            `shipping_query_id` VARCHAR(255) PRIMARY KEY COMMENT 'Идентификатор запроса на доставку',
            `from_user_id` BIGINT NOT NULL COMMENT 'Пользователь, отправивший запрос',
            `invoice_payload` TEXT NOT NULL COMMENT 'Платёжный payload разработчика',
            `shipping_address` JSON NOT NULL COMMENT 'Адрес доставки в формате JSON',
            `received_at` TIMESTAMP NOT NULL COMMENT 'Время получения запроса',
            CHECK (JSON_VALID(shipping_address))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}

