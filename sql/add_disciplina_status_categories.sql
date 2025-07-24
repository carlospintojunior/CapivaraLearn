-- CapivaraLearn: Migração para Categorização de Disciplinas
-- Data: 2025-07-23
-- Objetivo: Renomear coluna 'concluido' para 'status' e adicionar novos status
-- 
-- CHANGELOG:
-- [2025-07-23 00:30 UTC] - Migração executada com sucesso no servidor de produção (198.23.132.15)
-- [2025-07-23 00:30 UTC] - 58 disciplinas migradas (55 ativas, 3 concluídas)
-- [2025-07-23 00:31 UTC] - Arquivos PHP atualizados: modules_simple.php, dashboard.php, backup_user_data.php
-- [2025-07-23 00:32 UTC] - Sistema testado e funcionando corretamente
-- 
-- STATUS: ✅ EXECUTADO EM PRODUÇÃO - NÃO EXECUTAR NOVAMENTE

-- Renomear coluna mantendo todos os dados existentes
ALTER TABLE disciplinas 
CHANGE COLUMN concluido status TINYINT(1) DEFAULT 0 
COMMENT 'Status da disciplina: 0=Ativa, 1=Concluída, 2=A Cursar, 3=Aproveitada, 4=Dispensada';

-- Adicionar índice para otimizar consultas por status
CREATE INDEX idx_disciplinas_status ON disciplinas(status);

-- Verificar migração
SELECT 
    COUNT(*) as total_disciplinas,
    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as ativas,
    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as concluidas,
    SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as a_cursar,
    SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as aproveitadas,
    SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END) as dispensadas
FROM disciplinas;

SELECT 'Migração de status de disciplinas concluída com sucesso!' AS Status;

-- ================================================================================
-- REGISTRO DE EXECUÇÃO
-- ================================================================================
-- Data de execução: 2025-07-23 00:30 UTC
-- Servidor: 198.23.132.15 (Produção)
-- Resultado: ✅ SUCESSO
-- 
-- Dados migrados:
-- - Total de disciplinas: 58
-- - Ativas (status=0): 55  
-- - Concluídas (status=1): 3
-- - A Cursar (status=2): 0
-- - Aproveitadas (status=3): 0  
-- - Dispensadas (status=4): 0
-- 
-- Arquivos atualizados:
-- - crud/modules_simple.php (interface com 5 status + badges coloridos)
-- - dashboard.php (exibição atualizada)
-- - backup_user_data.php (estatísticas corrigidas)
-- 
-- ⚠️  IMPORTANTE: Esta migração JÁ FOI EXECUTADA. Não execute novamente!
