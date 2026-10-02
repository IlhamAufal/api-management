<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rombak total MD-Bridge: semua tabel pipeline/monitoring lama di-drop.
 * Lihat docs/MD-Bridge-Rearchitecture-Plan.md bagian 4.
 */
class DropLegacyPipelineTables extends Migration
{
    private const LEGACY_TABLES = [
        'monitoring_app_tables',
        'monitoring_apps',
        'sys_sync_logs',
        'sap_customer_sales_area',
        'sap_customer_material',
        'sap_customer_master',
        'sap_material_master',
        'api_sync_tasks',
    ];

    public function up()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach (self::LEGACY_TABLES as $table) {
            $this->forge->dropTable($table, true);
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down()
    {
        // Recreate identik dengan state asli setelah seluruh migration lama
        // (termasuk perubahan 090000/100000/100009) supaya rantai rollback lama
        // tetap bisa jalan setelah migration ini di-revert.
        $this->db->query(<<<'SQL'
CREATE TABLE `api_sync_tasks` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `task_code` VARCHAR(50) NOT NULL,
    `task_name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'SAP_MASTER',
    `source_type` ENUM('HTTP_GET','HTTP_POST') NOT NULL DEFAULT 'HTTP_GET',
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
SQL
        );

        $this->db->query(<<<'SQL'
CREATE TABLE `sys_sync_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `task_code` VARCHAR(50) NOT NULL,
    `trigger_type` ENUM('CRON','MANUAL_UI','WEBHOOK') NOT NULL DEFAULT 'CRON',
    `status` ENUM('RUNNING','SUCCESS','FAILED','WARNING') NOT NULL DEFAULT 'RUNNING',
    `step_failed` ENUM('NONE','FETCH_GET','TRANSFORM','DB_INSERT') NOT NULL DEFAULT 'NONE',
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
SQL
        );

        $this->db->query(<<<'SQL'
CREATE TABLE `sap_material_master` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `material_number` VARCHAR(40) NOT NULL,
    `material_type` VARCHAR(10) NULL,
    `material_description` VARCHAR(255) NULL,
    `base_unit` VARCHAR(10) NULL,
    `material_group` VARCHAR(20) NULL,
    `gross_weight` DECIMAL(13,3) DEFAULT 0.000,
    `net_weight` DECIMAL(13,3) DEFAULT 0.000,
    `weight_unit` VARCHAR(10) NULL,
    `standard_price` DECIMAL(15,2) DEFAULT 0.00,
    `currency` VARCHAR(5) DEFAULT 'IDR',
    `raw_payload` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_material_number` (`material_number`),
    INDEX `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->db->query(<<<'SQL'
CREATE TABLE `sap_customer_master` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `customer_code` VARCHAR(40) NOT NULL,
    `customer_name` VARCHAR(255) NOT NULL,
    `business_partner_group` VARCHAR(20) NULL,
    `address_line` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `postal_code` VARCHAR(20) NULL,
    `country` VARCHAR(10) DEFAULT 'ID',
    `tax_number` VARCHAR(50) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `raw_payload` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_customer_code` (`customer_code`),
    INDEX `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->db->query(<<<'SQL'
CREATE TABLE `sap_customer_material` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `customer_code` VARCHAR(40) NOT NULL,
    `material_number` VARCHAR(40) NOT NULL,
    `customer_material_number` VARCHAR(100) NULL,
    `customer_material_desc` VARCHAR(255) NULL,
    `raw_payload` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_cust_mat` (`customer_code`, `material_number`),
    INDEX `idx_material` (`material_number`),
    INDEX `idx_customer` (`customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->db->query(<<<'SQL'
CREATE TABLE `sap_customer_sales_area` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `customer_code` VARCHAR(40) NOT NULL,
    `sales_organization` VARCHAR(20) NOT NULL,
    `distribution_channel` VARCHAR(10) NOT NULL,
    `division` VARCHAR(10) NOT NULL,
    `sales_office` VARCHAR(20) NULL,
    `currency` VARCHAR(5) DEFAULT 'IDR',
    `payment_terms` VARCHAR(20) NULL,
    `raw_payload` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_cust_sales_area` (`customer_code`, `sales_organization`, `distribution_channel`, `division`),
    INDEX `idx_customer_area` (`customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->db->query(<<<'SQL'
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
SQL
        );

        $this->db->query(<<<'SQL'
CREATE TABLE `monitoring_app_tables` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `monitoring_app_id` INT UNSIGNED NOT NULL,
    `table_code` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(150) NOT NULL,
    `api_resource` VARCHAR(255) NULL DEFAULT NULL,
    `sync_task_code` VARCHAR(50) NULL DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_monitoring_app_table` (`monitoring_app_id`, `table_code`),
    KEY `idx_monitoring_app_tables_active_sort` (`monitoring_app_id`, `is_active`, `sort_order`),
    KEY `idx_monitoring_app_tables_task` (`sync_task_code`),
    CONSTRAINT `fk_monitoring_app_tables_app`
        FOREIGN KEY (`monitoring_app_id`) REFERENCES `monitoring_apps` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }
}
