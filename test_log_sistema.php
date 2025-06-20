<?php
require_once __DIR__ . '/includes/log_sistema.php';

echo "Testando função log_sistema()...<br>";

// Teste 1: Log simples
log_sistema('Teste 1: Função log_sistema funcionando', 'INFO');

// Teste 2: Log com nível diferente
log_sistema('Teste 2: Log de erro', 'ERROR');

// Teste 3: Log com arquivo específico
log_sistema('Teste 3: Log em arquivo específico', 'DEBUG', 'teste.log');

echo "Testes executados!<br>";

// Verificar se os arquivos foram criados
$logDir = __DIR__ . '/logs';
echo "Diretório de logs: $logDir<br>";
echo "Arquivos no diretório de logs:<br>";

if (is_dir($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "- $file<br>";
            $content = file_get_contents($logDir . '/' . $file);
            echo "<pre>" . htmlspecialchars($content) . "</pre>";
        }
    }
} else {
    echo "Diretório de logs não existe!<br>";
}
?>
