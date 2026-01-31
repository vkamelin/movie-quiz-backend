<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMoviesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE `movies` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `status` TINYINT(1) DEFAULT '1',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );");

        $this->execute("CREATE INDEX `idx_movies_status` ON `movies` (`status`);");
    }

    public function down(): void
    {
        $this->execute("DROP INDEX `idx_movies_status` ON `movies`;");
        $this->execute("DROP TABLE `movies`;");
    }
}
