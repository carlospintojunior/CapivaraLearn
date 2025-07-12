<?php
// Teste rápido do sistema após correção
session_start();
require_once '/opt/lampp/htdocs/CapivaraLearn/includes/logger_config.php';

echo "=== Teste do Sistema de Logs ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

// Teste 1: Logs básicos
echo "1. Testando logs básicos...\n";
logInfo('Teste de log INFO');
logWarning('Teste de log WARNING'); 
logError('Teste de log ERROR');
echo "   ✅ Logs básicos OK\n\n";

// Teste 2: Log de atividade
echo "2. Testando log de atividade...\n";
logActivity(1, 'test_action', 'Teste de atividade do usuário');
echo "   ✅ Log de atividade OK\n\n";

// Teste 3: Verificar arquivos criados
echo "3. Arquivos de log criados:\n";
$logFiles = glob('/opt/lampp/htdocs/CapivaraLearn/logs/*.log');
foreach ($logFiles as $file) {
    echo "   - " . basename($file) . " (" . filesize($file) . " bytes)\n";
}

echo "\n=== Teste Concluído ===\n";
echo "Verifique os logs em: /opt/lampp/htdocs/CapivaraLearn/logs/\n";
?>
