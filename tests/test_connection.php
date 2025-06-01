<?php
require_once __DIR__ . "/../includes/config.php";
require_once "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 Teste de Conectividade SMTP\n\n";

echo "=== CONFIGURAÇÕES ===\n";
echo "Host: " . SMTP_HOST . "\n";
echo "Porta: " . SMTP_PORT . "\n";
echo "Usuário: " . SMTP_USER . "\n";
echo "Nome: " . SMTP_FROM_NAME . "\n\n";

echo "=== TESTE DE DNS ===\n";
$ip = gethostbyname(SMTP_HOST);
echo "Resolução DNS: " . SMTP_HOST . " -> " . $ip . "\n\n";

echo "=== TESTE DE PORTA ===\n";
$fp = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 5);
if (!$fp) {
    echo "❌ Erro ao conectar na porta: $errstr ($errno)\n";
} else {
    echo "✅ Porta acessível\n";
    fclose($fp);
}
echo "\n";

echo "=== TESTE SMTP DETALHADO ===\n";
try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION; // Mais detalhes sobre a conexão
    $mail->Debugoutput = function($str, $level) {
        echo "DEBUG[$level]: $str\n";
    };
    
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->Port = SMTP_PORT;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->SMTPAuth = true;
    
    // Desativar verificação de certificado
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    
    echo "\nTentando conexão SMTP...\n";
    $mail->smtpConnect();
    echo "✅ Conexão SMTP bem sucedida!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    if (isset($mail)) {
        echo "Detalhes: " . $mail->ErrorInfo . "\n";
    }
}

// Testes adicionais de rede
echo "\n=== INFORMAÇÕES DE REDE ===\n";
echo "Servidor: " . php_uname() . "\n";
echo "IP Local: " . $_SERVER['SERVER_ADDR'] ?? 'N/A' . "\n";
echo "Hostname: " . gethostname() . "\n";

// Tentar ping no host (se permitido)
echo "\n=== TESTE DE PING ===\n";
$pingResult = shell_exec("ping -c 1 " . SMTP_HOST . " 2>&1");
echo "Resultado do ping:\n$pingResult\n";
?>
