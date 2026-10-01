<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSyncTaskCodeToMonitoringAppTables extends Migration
{
    public function up()
    {
        $this->db->query(<<<'SQL'
ALTER TABLE `monitoring_app_tables`
    ADD COLUMN `sync_task_code` VARCHAR(50) NULL DEFAULT NULL AFTER `api_resource`,
    ADD KEY `idx_monitoring_app_tables_task` (`sync_task_code`);
SQL
        );
    }

    public function down()
    {
        $this->db->query(<<<'SQL'
ALTER TABLE `monitoring_app_tables`
    DROP INDEX `idx_monitoring_app_tables_task`,
    DROP COLUMN `sync_task_code`;
SQL
        );
    }
}
