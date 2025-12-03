-- Migration: Adiciona tabela para status de atualizações dos clientes
-- Data: 2024-12-03

-- Adiciona colunas na tabela de licenças para status rápido
ALTER TABLE licenses 
    ADD COLUMN update_status ENUM('ok', 'error', 'pending', 'rollback') DEFAULT NULL AFTER site_url,
    ADD COLUMN update_status_at DATETIME DEFAULT NULL AFTER update_status,
    ADD COLUMN last_error_message TEXT DEFAULT NULL AFTER update_status_at;

-- Tabela detalhada de histórico de atualizações
CREATE TABLE IF NOT EXISTS update_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    plugin_slug VARCHAR(100) NOT NULL,
    from_version VARCHAR(20) NULL,
    to_version VARCHAR(20) NOT NULL,
    status ENUM('started', 'success', 'error', 'rollback') NOT NULL DEFAULT 'started',
    error_message TEXT NULL,
    error_type VARCHAR(50) NULL,
    health_check_passed TINYINT(1) DEFAULT NULL,
    rollback_performed TINYINT(1) DEFAULT 0,
    site_url VARCHAR(255) NULL,
    wp_version VARCHAR(20) NULL,
    php_version VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    INDEX idx_license_id (license_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
