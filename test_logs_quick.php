<?php
// Teste rápido dos logs
require_once '/opt/lampp/htdocs/CapivaraLearn/includes/logger_config.php';

echo "Testando logs...\n";

logInfo('Teste de log - sistema funcionando');
logWarning('Teste de warning');
logError('Teste de erro');

echo "Logs enviados! Verifique os arquivos:\n";
echo "- /opt/lampp/htdocs/CapivaraLearn/logs/sistema-*.log\n";
echo "- /opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log\n";
?>
