-- Migration: Adiciona preferências de notificação por email
-- Data: 2024-12-03

-- Adiciona campos de preferência de email na tabela licenses
ALTER TABLE licenses 
    ADD COLUMN notification_email VARCHAR(255) DEFAULT NULL AFTER last_error_message,
    ADD COLUMN notify_on_update TINYINT(1) DEFAULT 0 AFTER notification_email,
    ADD COLUMN notify_on_error TINYINT(1) DEFAULT 1 AFTER notify_on_update,
    ADD COLUMN notify_on_rollback TINYINT(1) DEFAULT 1 AFTER notify_on_error;

-- Tabela para filas de email (para processamento assíncrono)
CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    to_name VARCHAR(255) NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    template VARCHAR(50) NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    last_error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para logs de email enviados
CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NULL,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    status ENUM('sent', 'failed') NOT NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE SET NULL,
    INDEX idx_license_id (license_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
