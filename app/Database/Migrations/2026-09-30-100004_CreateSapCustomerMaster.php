<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSapCustomerMaster extends Migration
{
    public function up()
    {
        $sql = <<<'SQL'
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
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->forge->dropTable('sap_customer_master', true);
    }
}
