<?php
require_once 'includes/config.php';
require_once 'includes/MailService.php';

try {
    $mail = MailService::getInstance();
    $config = $mail->getConfig();
    echo "Configurações do MailService:\n";
    print_r($config);
    echo "\nMailService carregado com sucesso!\n";
} catch (Exception $e) {
    echo "Erro ao carregar MailService: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
