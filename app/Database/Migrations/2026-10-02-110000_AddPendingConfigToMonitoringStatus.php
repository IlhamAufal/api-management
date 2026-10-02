<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tambah nilai enum PENDING_CONFIG ke kolom status di table_snapshots
 * dan check_history (plan bagian 8.2 — source terdaftar tapi connection
 * group-nya belum dibuat di Config/Database.php).
 *
 * MySQL: ALTER MODIFY ENUM. SQLite (tests): no-op — kolom bertipe TEXT
 * tanpa CHECK, dan DDL suite test dibuat terpisah oleh MonitoringFixtureTrait.
 */
class AddPendingConfigToMonitoringStatus extends Migration
{
    private const ENUM_WITH_PENDING
        = "ENUM('NEVER_SYNCED','STALE','OK','MISSING_TABLE','CONN_ERROR','PENDING_CONFIG')";
    private const ENUM_ORIGINAL
        = "ENUM('NEVER_SYNCED','STALE','OK','MISSING_TABLE','CONN_ERROR')";

    public function up()
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return;
        }

        foreach (['table_snapshots', 'check_history'] as $table) {
            $this->db->query(
                'ALTER TABLE `' . $table . '`'
                . ' MODIFY COLUMN `status` ' . self::ENUM_WITH_PENDING . ' NOT NULL'
            );
        }
    }

    public function down()
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return;
        }

        foreach (['table_snapshots', 'check_history'] as $table) {
            // Convert dulu supaya penyempitan enum tidak gagal
            // (pola yang sama dengan RemoveDirectDbFromSourceType).
            $this->db->query(
                "UPDATE `{$table}` SET `status` = 'CONN_ERROR' WHERE `status` = 'PENDING_CONFIG'"
            );
            $this->db->query(
                'ALTER TABLE `' . $table . '`'
                . ' MODIFY COLUMN `status` ' . self::ENUM_ORIGINAL . ' NOT NULL'
            );
        }
    }
}
