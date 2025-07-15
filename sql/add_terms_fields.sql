-- Script para adicionar campos de concordância com termos de uso
-- Execute este script no banco de dados para adicionar os campos necessários

-- Adicionar campo para registrar se o usuário aceitou os termos
ALTER TABLE usuarios ADD COLUMN termos_aceitos TINYINT(1) NOT NULL DEFAULT 0;

-- Adicionar campo para registrar quando os termos foram aceitos
ALTER TABLE usuarios ADD COLUMN data_aceitacao_termos TIMESTAMP NULL;

-- Adicionar campo para registrar a versão dos termos aceitos (para futuras atualizações)
ALTER TABLE usuarios ADD COLUMN versao_termos_aceitos VARCHAR(10) DEFAULT '1.0';

-- Criar índice para consultas rápidas
CREATE INDEX idx_termos_aceitos ON usuarios(termos_aceitos);

-- Atualizar usuários existentes para aceitar os termos (necessário para não quebrar o sistema)
UPDATE usuarios SET termos_aceitos = 1, data_aceitacao_termos = NOW(), versao_termos_aceitos = '1.0' WHERE termos_aceitos = 0;

-- Comentário explicativo
-- Campos adicionados:
-- - termos_aceitos: indica se o usuário aceitou os termos (1 = sim, 0 = não)
-- - data_aceitacao_termos: timestamp de quando os termos foram aceitos
-- - versao_termos_aceitos: versão dos termos aceitos (para controle de atualizações)
