<?php
// Teste de conexão SMTP direta
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require_once '/opt/lampp/htdocs/CapivaraLearn/vendor/autoload.php';

echo "=== TESTE DE CONEXÃO SMTP ===\n";

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'mail.capivaralearn.com.br';
    $mail->SMTPAuth = true;
    $mail->Username = 'capivara@capivaralearn.com.br';
    $mail->Password = '_,CeLlORRy,92';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
    $mail->Timeout = 30;
    
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    echo "Testando conexão SMTP...\n";
    $connected = $mail->smtpConnect();
    
    if ($connected) {
        echo "✅ Conexão SMTP bem-sucedida!\n";
        $mail->smtpClose();
    } else {
        echo "❌ Falha na conexão SMTP\n";
        echo "Erro: " . $mail->ErrorInfo . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

// Teste de DNS
echo "\n=== TESTE DE DNS ===\n";
$host = 'mail.capivaralearn.com.br';
$ip = gethostbyname($host);
if ($ip != $host) {
    echo "✅ DNS OK: $host → $ip\n";
} else {
    echo "❌ DNS FALHOU: Não conseguiu resolver $host\n";
}

// Teste de conectividade de rede
echo "\n=== TESTE DE CONECTIVIDADE ===\n";
$fp = @fsockopen('mail.capivaralearn.com.br', 465, $errno, $errstr, 10);
if ($fp) {
    echo "✅ Porta 465 acessível\n";
    fclose($fp);
} else {
    echo "❌ Porta 465 não acessível - Erro: $errno $errstr\n";
}
?>
