<?php
// Configuração de logs com Monolog para o sistema original
require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

// Criar logger global
$logger = new Logger('capivaralearn');

// Handler para logs rotativos (arquivo principal)
$rotatingHandler = new RotatingFileHandler(__DIR__ . '/../logs/sistema.log', 0, Logger::DEBUG);
$formatter = new LineFormatter(null, null, false, true);
$rotatingHandler->setFormatter($formatter);
$logger->pushHandler($rotatingHandler);

// Handler para erros
$errorHandler = new StreamHandler(__DIR__ . '/../logs/php_errors.log', Logger::ERROR);
$errorHandler->setFormatter($formatter);
$logger->pushHandler($errorHandler);

// Função helper para log simples
function logInfo($message, $context = []) {
    global $logger;
    $logger->info($message, $context);
}

function logWarning($message, $context = []) {
    global $logger;
    $logger->warning($message, $context);
}

function logError($message, $context = []) {
    global $logger;
    $logger->error($message, $context);
}

function logDebug($message, $context = []) {
    global $logger;
    $logger->debug($message, $context);
}

// Função para registrar atividade do usuário
function logActivity($userId, $action, $details, $pdo = null) {
    global $logger;
    
    try {
        // Log no arquivo
        $logger->info('User Activity', [
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        // Log no banco de dados (se $pdo estiver disponível e $userId não for null)
        if ($pdo && $userId !== null) {
            $stmt = $pdo->prepare("INSERT INTO logs_atividade (usuario_id, acao, detalhes, timestamp) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$userId, $action, $details]);
        }
        
    } catch (Exception $e) {
        $logger->error('Erro ao registrar atividade', [
            'error' => $e->getMessage(),
            'user_id' => $userId,
            'action' => $action
        ]);
    }
}

return $logger;
?>
