<?php
// Teste das configurações do MailService
require_once '/opt/lampp/htdocs/CapivaraLearn/includes/config.php';

echo "=== CONFIGURAÇÕES DO SISTEMA ===\n";
echo "APP_ENV: " . (defined('APP_ENV') ? APP_ENV : 'NÃO DEFINIDO') . "\n";
echo "DEBUG_MODE: " . (defined('DEBUG_MODE') ? (DEBUG_MODE ? 'true' : 'false') : 'NÃO DEFINIDO') . "\n";
echo "MAIL_HOST: " . (defined('MAIL_HOST') ? MAIL_HOST : 'NÃO DEFINIDO') . "\n";
echo "MAIL_PORT: " . (defined('MAIL_PORT') ? MAIL_PORT : 'NÃO DEFINIDO') . "\n";
echo "MAIL_USERNAME: " . (defined('MAIL_USERNAME') ? MAIL_USERNAME : 'NÃO DEFINIDO') . "\n";
echo "MAIL_PASSWORD: " . (defined('MAIL_PASSWORD') ? (MAIL_PASSWORD ? '***DEFINIDA***' : 'VAZIA') : 'NÃO DEFINIDO') . "\n";
echo "MAIL_SECURE: " . (defined('MAIL_SECURE') ? MAIL_SECURE : 'NÃO DEFINIDO') . "\n";
echo "MAIL_AUTH: " . (defined('MAIL_AUTH') ? (MAIL_AUTH ? 'true' : 'false') : 'NÃO DEFINIDO') . "\n";

echo "\n=== TESTE DO MAILSERVICE ===\n";
$mail = MailService::getInstance();
$config = $mail->getConfig();
print_r($config);

echo "\n=== TESTE DE ENVIO RÁPIDO ===\n";
$result = $mail->sendConfirmationEmail('teste@exemplo.com', 'Teste', 'token123');
echo "Resultado: " . ($result ? 'SUCESSO' : 'FALHA') . "\n";
if (!$result) {
    echo "Erro: " . $mail->getLastError() . "\n";
}
?>
