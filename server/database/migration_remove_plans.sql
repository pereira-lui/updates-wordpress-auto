-- =============================================
-- Migração: Remover Planos, Adicionar Períodos
-- =============================================
-- Execute este script se você já tem o banco de dados antigo
-- =============================================

-- Remove tabela de plugins por plano
DROP TABLE IF EXISTS `plan_plugins`;

-- Remove tabela de planos
DROP TABLE IF EXISTS `plans`;

-- Modifica tabela licenses
-- Remove colunas antigas e adiciona nova coluna period
ALTER TABLE `licenses` 
    DROP COLUMN IF EXISTS `type`,
    DROP COLUMN IF EXISTS `plan_id`,
    ADD COLUMN IF NOT EXISTS `period` ENUM('monthly', 'quarterly', 'semiannual', 'yearly', 'lifetime') NOT NULL DEFAULT 'monthly' AFTER `site_url`;

-- Adiciona índice de período se não existir
CREATE INDEX IF NOT EXISTS `idx_period` ON `licenses` (`period`);

-- Adiciona coluna period na tabela payments se não existir
ALTER TABLE `payments`
    ADD COLUMN IF NOT EXISTS `period` VARCHAR(20) NULL AFTER `amount`;

-- =============================================
-- IMPORTANTE: Configurar preços em Configurações
-- =============================================
-- Após executar a migração, acesse:
-- Configurações > Preços de Assinatura
-- E defina os valores para cada período
-- =============================================
