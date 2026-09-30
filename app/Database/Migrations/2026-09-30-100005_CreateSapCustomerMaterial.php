<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSapCustomerMaterial extends Migration
{
    public function up()
    {
        $sql = <<<'SQL'
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
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->forge->dropTable('sap_customer_material', true);
    }
}
