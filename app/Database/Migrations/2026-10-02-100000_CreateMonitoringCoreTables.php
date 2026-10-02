<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Skema final monitoring MD-Bridge: sources / watched_tables /
 * table_snapshots / check_history. Lihat plan bagian 4.
 */
class CreateMonitoringCoreTables extends Migration
{
    public function up()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE `sources` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `label` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_source_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->db->query(<<<'SQL'
CREATE TABLE `watched_tables` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `table_name` VARCHAR(100) NOT NULL,
    `label` VARCHAR(150) NOT NULL,
    `sync_column` VARCHAR(100) NOT NULL DEFAULT 'synced_at',
    `stale_after_minutes` INT UNSIGNED NOT NULL DEFAULT 1440,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_table_name` (`table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        // Tanpa FK — riwayat tidak boleh hilang saat watched_table di-hard-delete.
        $this->db->query(<<<'SQL'
CREATE TABLE `check_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `watched_table_id` BIGINT UNSIGNED NOT NULL,
    `source_id` BIGINT UNSIGNED NOT NULL,
    `trigger_type` ENUM('MANUAL_UI','CRON') NOT NULL DEFAULT 'MANUAL_UI',
    `row_count` BIGINT UNSIGNED NULL DEFAULT NULL,
    `last_synced_at` DATETIME NULL DEFAULT NULL,
    `status` ENUM('NEVER_SYNCED','STALE','OK','MISSING_TABLE','CONN_ERROR') NOT NULL,
    `error_message` TEXT NULL,
    `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_table_executed` (`watched_table_id`, `executed_at`),
    KEY `idx_source_executed` (`source_id`, `executed_at`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->db->query(<<<'SQL'
CREATE TABLE `table_snapshots` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `watched_table_id` BIGINT UNSIGNED NOT NULL,
    `source_id` BIGINT UNSIGNED NOT NULL,
    `row_count` BIGINT UNSIGNED NULL DEFAULT NULL,
    `last_synced_at` DATETIME NULL DEFAULT NULL,
    `status` ENUM('NEVER_SYNCED','STALE','OK','MISSING_TABLE','CONN_ERROR') NOT NULL,
    `error_message` TEXT NULL,
    `checked_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_table_source` (`watched_table_id`, `source_id`),
    KEY `idx_source` (`source_id`),
    CONSTRAINT `fk_snapshot_table` FOREIGN KEY (`watched_table_id`)
        REFERENCES `watched_tables` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_snapshot_source` FOREIGN KEY (`source_id`)
        REFERENCES `sources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }

    public function down()
    {
        $this->forge->dropTable('table_snapshots', true);
        $this->forge->dropTable('check_history', true);
        $this->forge->dropTable('watched_tables', true);
        $this->forge->dropTable('sources', true);
    }
}
