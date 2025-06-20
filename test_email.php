<?php
// Teste de configurações de email
require_once 'includes/config.php';

echo "=== TESTE DE CONFIGURAÇÕES DE EMAIL ===\n";
echo "APP_ENV: " . APP_ENV . "\n";
echo "MAIL_HOST: " . MAIL_HOST . "\n";
echo "MAIL_PORT: " . MAIL_PORT . "\n";
echo "MAIL_USERNAME: " . MAIL_USERNAME . "\n";
echo "MAIL_PASSWORD: " . (MAIL_PASSWORD ? str_repeat('*', strlen(MAIL_PASSWORD)) : 'vazia') . "\n";
echo "MAIL_FROM_NAME: " . MAIL_FROM_NAME . "\n";
echo "MAIL_FROM_EMAIL: " . MAIL_FROM_EMAIL . "\n";
echo "MAIL_SECURE: " . MAIL_SECURE . "\n";
echo "MAIL_AUTH: " . (MAIL_AUTH ? 'true' : 'false') . "\n";

// Teste básico de conexão SMTP
echo "\n=== TESTE DE CONEXÃO SMTP ===\n";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = MAIL_AUTH;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = MAIL_PORT;
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
    
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // Apenas testar conexão, não enviar
    echo "Testando conexão SMTP...\n";
    $mail->smtpConnect();
    echo "✅ Conexão SMTP bem-sucedida!\n";
    $mail->smtpClose();
    
} catch (Exception $e) {
    echo "❌ Erro na conexão SMTP: " . $e->getMessage() . "\n";
}
?>
