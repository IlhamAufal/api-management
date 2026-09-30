<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSysSyncLogs extends Migration
{
    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `sys_sync_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `task_code` VARCHAR(50) NOT NULL,
    `trigger_type` ENUM('CRON','MANUAL_UI','WEBHOOK') NOT NULL DEFAULT 'CRON',
    `status` ENUM('RUNNING','SUCCESS','FAILED','WARNING') NOT NULL DEFAULT 'RUNNING',
    `step_failed` ENUM('NONE','FETCH_GET','DB_INSERT') NOT NULL DEFAULT 'NONE',
    `records_read` INT UNSIGNED NOT NULL DEFAULT 0,
    `records_written` INT UNSIGNED NOT NULL DEFAULT 0,
    `duration_sec` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    `error_message` TEXT NULL DEFAULT NULL,
    `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `finished_at` DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_task_executed` (`task_code`, `executed_at`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->forge->dropTable('sys_sync_logs', true);
    }
}
