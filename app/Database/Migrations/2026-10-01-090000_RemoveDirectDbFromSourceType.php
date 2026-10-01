<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveDirectDbFromSourceType extends Migration
{
    public function up()
    {
        // Convert existing rows first so narrowing the enum cannot fail.
        $this->db->query(<<<'SQL'
UPDATE `api_sync_tasks`
    SET `source_type` = 'HTTP_GET'
    WHERE `source_type` = 'DIRECT_DB';
SQL
        );

        $this->db->query(<<<'SQL'
ALTER TABLE `api_sync_tasks`
    MODIFY COLUMN `source_type` ENUM('HTTP_GET','HTTP_POST') NOT NULL DEFAULT 'HTTP_GET';
SQL
        );
    }

    public function down()
    {
        $this->db->query(<<<'SQL'
ALTER TABLE `api_sync_tasks`
    MODIFY COLUMN `source_type` ENUM('DIRECT_DB','HTTP_GET','HTTP_POST') NOT NULL DEFAULT 'HTTP_GET';
SQL
        );

        // No data restore: HTTP_GET rows stay HTTP_GET.
    }
}
