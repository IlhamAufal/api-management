<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMonitoringApps extends Migration
{
    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `monitoring_apps` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `app_code` VARCHAR(50) NOT NULL,
    `app_name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `base_url` VARCHAR(255) NULL DEFAULT NULL,
    `database_name` VARCHAR(100) NULL DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_monitoring_app_code` (`app_code`),
    KEY `idx_monitoring_apps_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $this->db->query($sql);
    }

    public function down()
    {
        $this->forge->dropTable('monitoring_apps', true);
    }
}
