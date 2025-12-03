-- Migration: Adicionar campos de nota fiscal na tabela payments
-- Data: 2024-12-03

-- Adiciona campo para indicar se deve gerar nota fiscal
ALTER TABLE `payments` 
ADD COLUMN IF NOT EXISTS `generate_invoice` TINYINT(1) DEFAULT 0 COMMENT 'Se deve gerar nota fiscal' AFTER `invoice_url`;

-- Adiciona campo para status da nota fiscal
ALTER TABLE `payments` 
ADD COLUMN IF NOT EXISTS `invoice_status` VARCHAR(20) NULL COMMENT 'Status da nota fiscal: pending, issued, error' AFTER `generate_invoice`;

-- Adiciona campo para número da nota fiscal
ALTER TABLE `payments` 
ADD COLUMN IF NOT EXISTS `invoice_number` VARCHAR(50) NULL COMMENT 'Número da nota fiscal gerada' AFTER `invoice_status`;

-- Adiciona campo para URL do PDF da nota fiscal
ALTER TABLE `payments` 
ADD COLUMN IF NOT EXISTS `invoice_pdf_url` VARCHAR(500) NULL COMMENT 'URL do PDF da nota fiscal' AFTER `invoice_number`;

-- Adiciona índice para buscar pagamentos que precisam de nota
ALTER TABLE `payments` 
ADD INDEX IF NOT EXISTS `idx_invoice` (`generate_invoice`);
