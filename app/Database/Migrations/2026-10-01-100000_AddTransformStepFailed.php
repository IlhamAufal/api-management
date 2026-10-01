<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTransformStepFailed extends Migration
{
    public function up()
    {
        $this->db->query(<<<'SQL'
ALTER TABLE `sys_sync_logs`
    MODIFY COLUMN `step_failed` ENUM('NONE','FETCH_GET','TRANSFORM','DB_INSERT') NOT NULL DEFAULT 'NONE';
SQL
        );
    }

    public function down()
    {
        // Rows logged with TRANSFORM must be converted before narrowing the enum.
        $this->db->query(<<<'SQL'
UPDATE `sys_sync_logs`
    SET `step_failed` = 'NONE'
    WHERE `step_failed` = 'TRANSFORM';
SQL
        );

        $this->db->query(<<<'SQL'
ALTER TABLE `sys_sync_logs`
    MODIFY COLUMN `step_failed` ENUM('NONE','FETCH_GET','DB_INSERT') NOT NULL DEFAULT 'NONE';
SQL
        );
    }
}
