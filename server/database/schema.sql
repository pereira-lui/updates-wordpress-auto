-- =============================================
-- Premium Updates - Database Schema
-- =============================================
-- Sistema simplificado: Cliente contrata direto no plugin WordPress
-- Períodos: mensal, trimestral, semestral, anual, vitalícia
-- =============================================

-- ---------------------------------------------
-- Tabela: users (Usuários administrativos)
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'editor') DEFAULT 'admin',
    `last_login` DATETIME NULL,
    `last_ip` VARCHAR(45) NULL,
    `reset_token` VARCHAR(64) NULL,
    `reset_expires` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB;

-- Usuário admin padrão (senha: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `name`, `role`) VALUES
('admin', 'admin@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin')
ON DUPLICATE KEY UPDATE `username` = 'admin';

-- ---------------------------------------------
-- Tabela: plugins (Plugins gerenciados)
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `plugins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `version` VARCHAR(20) NOT NULL,
    `description` TEXT NULL,
    `changelog` TEXT NULL,
    `author` VARCHAR(100) NULL,
    `author_uri` VARCHAR(255) NULL,
    `plugin_uri` VARCHAR(255) NULL,
    `requires_wp` VARCHAR(10) DEFAULT '5.0',
    `tested_wp` VARCHAR(10) DEFAULT '6.4',
    `requires_php` VARCHAR(10) DEFAULT '7.4',
    `zip_file` VARCHAR(255) NULL,
    `downloads` INT UNSIGNED DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_slug` (`slug`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB;

-- ---------------------------------------------
-- Tabela: licenses (Licenças)
-- Período: monthly, quarterly, semiannual, yearly, lifetime
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `licenses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `license_key` VARCHAR(50) NOT NULL UNIQUE,
    `client_name` VARCHAR(100) NOT NULL,
    `client_email` VARCHAR(255) NOT NULL,
    `client_document` VARCHAR(20) NULL,
    `site_url` VARCHAR(255) NULL,
    `period` ENUM('monthly', 'quarterly', 'semiannual', 'yearly', 'lifetime') NOT NULL DEFAULT 'monthly',
    `status` ENUM('pending', 'active', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
    `expires_at` DATETIME NULL,
    `activated_at` DATETIME NULL,
    `last_check_at` DATETIME NULL,
    `last_check_ip` VARCHAR(45) NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_license_key` (`license_key`),
    INDEX `idx_email` (`client_email`),
    INDEX `idx_site` (`site_url`),
    INDEX `idx_status` (`status`),
    INDEX `idx_period` (`period`)
) ENGINE=InnoDB;

-- ---------------------------------------------
-- Tabela: payments (Pagamentos)
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `license_id` INT UNSIGNED NULL,
    `asaas_id` VARCHAR(100) NULL,
    `asaas_customer_id` VARCHAR(100) NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `period` VARCHAR(20) NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `payment_method` VARCHAR(20) NULL,
    `due_date` DATE NULL,
    `paid_at` DATETIME NULL,
    `pix_code` TEXT NULL,
    `pix_qrcode` TEXT NULL,
    `boleto_url` VARCHAR(500) NULL,
    `invoice_url` VARCHAR(500) NULL,
    `raw_data` JSON NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_license` (`license_id`),
    INDEX `idx_asaas` (`asaas_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`license_id`) REFERENCES `licenses`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------
-- Tabela: activity_logs (Logs de atividade)
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(30) NOT NULL,
    `action` VARCHAR(50) NULL,
    `entity_type` VARCHAR(30) NULL,
    `entity_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NULL,
    `message` VARCHAR(255) NOT NULL,
    `data` JSON NULL,
    `license_id` INT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_type` (`type`),
    INDEX `idx_action` (`action`),
    INDEX `idx_entity` (`entity_type`),
    INDEX `idx_license` (`license_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB;
