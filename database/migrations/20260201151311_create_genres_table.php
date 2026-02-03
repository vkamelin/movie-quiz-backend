<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateGenresTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE `genres` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(50) NOT NULL
        );");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE `genres`;");
    }
}
