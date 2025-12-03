-- Migration: Adiciona tabela para histórico de versões dos plugins
-- Data: 2024-12-03

CREATE TABLE IF NOT EXISTS plugin_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plugin_id INT NOT NULL,
    version VARCHAR(20) NOT NULL,
    zip_file VARCHAR(255) NOT NULL,
    changelog TEXT NULL,
    downloads INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_plugin_version (plugin_id, version),
    INDEX idx_plugin_id (plugin_id),
    INDEX idx_version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migra versões atuais dos plugins para a tabela de versões
INSERT IGNORE INTO plugin_versions (plugin_id, version, zip_file, created_at)
SELECT id, version, zip_file, updated_at
FROM plugins
WHERE zip_file IS NOT NULL AND zip_file != '';
