<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSapCustomerSalesArea extends Migration
{
    public function up()
    {
        $sql = <<<'SQL'
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
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->forge->dropTable('sap_customer_sales_area', true);
    }
}
