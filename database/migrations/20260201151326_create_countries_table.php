<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCountriesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE `countries` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(50) NOT NULL
        );");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE `countries`;");
    }
}
