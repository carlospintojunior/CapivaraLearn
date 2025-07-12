<?php
// Teste de log simples
require_once '/opt/lampp/htdocs/CapivaraLearn/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

echo "Iniciando teste de logs...\n";

// Configuração do Monolog
$logger = new Logger('capivaralearn_test');

// Handler para logs rotativos
$rotatingHandler = new RotatingFileHandler('/opt/lampp/htdocs/CapivaraLearn/logs/sistema.log', 0, Logger::DEBUG);
$formatter = new LineFormatter(null, null, false, true);
$rotatingHandler->setFormatter($formatter);
$logger->pushHandler($rotatingHandler);

// Handler para erros
$errorHandler = new StreamHandler('/opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log', Logger::ERROR);
$errorHandler->setFormatter($formatter);
$logger->pushHandler($errorHandler);

// Teste de logs
echo "Testando logs...\n";
$logger->info('Teste de log INFO - ' . date('Y-m-d H:i:s'));
$logger->warning('Teste de log WARNING - ' . date('Y-m-d H:i:s'));
$logger->error('Teste de log ERROR - ' . date('Y-m-d H:i:s'));

echo "Logs testados! Verifique os arquivos de log.\n";
echo "Arquivo de log principal: /opt/lampp/htdocs/CapivaraLearn/logs/sistema.log\n";
echo "Arquivo de log de erros: /opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log\n";
?>
