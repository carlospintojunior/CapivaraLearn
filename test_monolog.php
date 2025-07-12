<?php
// Teste específico do Monolog
require_once '/opt/lampp/htdocs/CapivaraLearn/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

echo "<!DOCTYPE html><html><head><title>Teste Monolog</title></head><body>";
echo "<h1>Teste do Sistema de Logs (Monolog)</h1>";

try {
    // Criar logger
    $logger = new Logger('test');
    
    // Handler para arquivo simples
    $streamHandler = new StreamHandler('/opt/lampp/htdocs/CapivaraLearn/logs/monolog_test.log', Logger::DEBUG);
    $formatter = new LineFormatter(null, null, false, true);
    $streamHandler->setFormatter($formatter);
    $logger->pushHandler($streamHandler);
    
    // Testar diferentes níveis de log
    $logger->debug('Teste de log DEBUG');
    $logger->info('Teste de log INFO');
    $logger->notice('Teste de log NOTICE');
    $logger->warning('Teste de log WARNING');
    $logger->error('Teste de log ERROR');
    $logger->critical('Teste de log CRITICAL');
    
    // Log com contexto
    $logger->info('Teste com contexto', [
        'usuario_id' => 123,
        'acao' => 'teste',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    echo "<p>✅ Logs criados com sucesso!</p>";
    
    // Verificar se o arquivo foi criado
    $logFile = '/opt/lampp/htdocs/CapivaraLearn/logs/monolog_test.log';
    if (file_exists($logFile)) {
        echo "<h2>Conteúdo do Log:</h2>";
        echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
    } else {
        echo "<p>❌ Arquivo de log não foi criado</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro ao testar Monolog: " . $e->getMessage() . "</p>";
    echo "<p>Trace: " . $e->getTraceAsString() . "</p>";
}

// Teste de permissões
echo "<h2>Teste de Permissões</h2>";
$logDir = '/opt/lampp/htdocs/CapivaraLearn/logs';
if (is_dir($logDir)) {
    echo "<p>✅ Diretório de logs existe</p>";
    if (is_writable($logDir)) {
        echo "<p>✅ Diretório é gravável</p>";
    } else {
        echo "<p>❌ Diretório não é gravável</p>";
    }
} else {
    echo "<p>❌ Diretório de logs não existe</p>";
}

echo "</body></html>";
?>
