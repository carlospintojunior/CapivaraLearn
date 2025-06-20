<?php
// Teste direto de escrita de log
echo "Testando escrita direta de log...\n";

$logFile = '/opt/lampp/htdocs/CapivaraLearn/logs/debug_direto.log';
$message = "Teste direto - " . date('Y-m-d H:i:s') . "\n";

echo "Arquivo de log: $logFile\n";
echo "Mensagem: " . trim($message) . "\n";

// Tentar escrever
$result = file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);

if ($result !== false) {
    echo "✅ Sucesso! Escreveu $result bytes\n";
    echo "Conteúdo do arquivo:\n";
    echo file_get_contents($logFile);
} else {
    echo "❌ Falha na escrita!\n";
    echo "Erro: " . error_get_last()['message'] . "\n";
}

// Verificar permissões
echo "\nPermissões do diretório:\n";
echo "Diretório: " . decoct(fileperms('/opt/lampp/htdocs/CapivaraLearn/logs/') & 0777) . "\n";

if (file_exists($logFile)) {
    echo "Arquivo: " . decoct(fileperms($logFile) & 0777) . "\n";
}

// Testar via função do MailService
echo "\n=== TESTANDO VIA MAILSERVICE ===\n";

require_once '/opt/lampp/htdocs/CapivaraLearn/includes/MailService.php';

$mailService = MailService::getInstance(); // Singleton correto
echo "MailService instanciado via getInstance()\n";

// Verificar se o método existe
if (method_exists($mailService, 'sendConfirmationEmail')) {
    echo "Método sendConfirmationEmail existe\n";
    
    // Tentar fazer um teste REAL
    echo "Testando envio de email real...\n";
    $result = $mailService->sendConfirmationEmail('teste@exemplo.com', 'Teste', 'token123');
    
    if ($result) {
        echo "✅ sendConfirmationEmail retornou TRUE\n";
    } else {
        echo "❌ sendConfirmationEmail retornou FALSE\n";
        echo "Último erro: " . $mailService->getLastError() . "\n";
    }
} else {
    echo "❌ Método sendConfirmationEmail NÃO EXISTE\n";
}
?>
