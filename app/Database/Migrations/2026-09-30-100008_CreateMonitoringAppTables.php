<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMonitoringAppTables extends Migration
{
    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `monitoring_app_tables` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `monitoring_app_id` INT UNSIGNED NOT NULL,
    `table_code` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(150) NOT NULL,
    `api_resource` VARCHAR(255) NULL DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_monitoring_app_table` (`monitoring_app_id`, `table_code`),
    KEY `idx_monitoring_app_tables_active_sort` (`monitoring_app_id`, `is_active`, `sort_order`),
    CONSTRAINT `fk_monitoring_app_tables_app`
        FOREIGN KEY (`monitoring_app_id`) REFERENCES `monitoring_apps` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $this->db->query($sql);
    }

    public function down()
    {
        $this->forge->dropTable('monitoring_app_tables', true);
    }
}
