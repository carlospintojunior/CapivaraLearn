<?php
/**
 * Sistema de Logs Ultra Simples
 * Não depende de nada - só do PHP básico
 */

function simpleLog($message, $level = 'INFO') {
    $logDir = '/opt/lampp/htdocs/CapivaraLearn/logs';
    $logFile = $logDir . '/simple.log';
    
    // Criar diretório se não existir
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    
    // Preparar linha do log
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $logLine = "[$timestamp] $level | IP: $ip | $message" . PHP_EOL;
    
    // Tentar escrever no arquivo
    $result = @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    
    // Fallback: tentar escrever no error_log do sistema
    if ($result === false) {
        @error_log("SimpleLog [$level]: $message");
    }
    
    return $result !== false;
}

// Teste imediato
simpleLog("Sistema de logs simples inicializado", "INIT");
simpleLog("PHP Version: " . PHP_VERSION, "INFO");
simpleLog("Timestamp: " . date('Y-m-d H:i:s'), "INFO");

echo "Sistema de logs simples testado!<br>";
echo "Arquivo: /opt/lampp/htdocs/CapivaraLearn/logs/simple.log<br>";

// Mostrar conteúdo do log
$logFile = '/opt/lampp/htdocs/CapivaraLearn/logs/simple.log';
if (file_exists($logFile)) {
    echo "<h3>Conteúdo do log:</h3>";
    echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
} else {
    echo "❌ Arquivo de log não foi criado!<br>";
}
?>
