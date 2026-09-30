<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSapMaterialMaster extends Migration
{
    public function up()
    {
        $sql = <<<'SQL'
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
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->forge->dropTable('sap_material_master', true);
    }
}
