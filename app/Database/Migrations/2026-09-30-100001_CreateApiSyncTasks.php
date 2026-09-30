<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiSyncTasks extends Migration
{
    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `api_sync_tasks` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `task_code` VARCHAR(50) NOT NULL,
    `task_name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'SAP_MASTER',
    `source_type` ENUM('DIRECT_DB','HTTP_GET','HTTP_POST') NOT NULL DEFAULT 'HTTP_GET',
    `source_endpoint` TEXT NOT NULL,
    `target_table` VARCHAR(100) NOT NULL,
    `batch_size` INT UNSIGNED NOT NULL DEFAULT 1500,
    `cron_expression` VARCHAR(50) NULL DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_task_code` (`task_code`),
    KEY `idx_category_status` (`category`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->forge->dropTable('api_sync_tasks', true);
    }
}
