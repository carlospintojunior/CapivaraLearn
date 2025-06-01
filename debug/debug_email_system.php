<?php
require_once __DIR__ . "/../includes/config.php";
require_once "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico do Sistema de Email</h1>";
echo "<pre>";

// 1. Verificar se as classes existem
echo "=== VERIFICAÇÃO DE CLASSES ===\n";
echo "PHPMailer existe: " . (class_exists('PHPMailer\PHPMailer\PHPMailer') ? "✅ SIM" : "❌ NÃO") . "\n";
echo "MailService existe: " . (class_exists('MailService') ? "✅ SIM" : "❌ NÃO") . "\n";

// 2. Verificar constantes de configuração
echo "\n=== CONFIGURAÇÕES ===\n";
$configs = ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS', 'SMTP_FROM_NAME'];
foreach ($configs as $config) {
    if (defined($config)) {
        $value = constant($config);
        if ($config === 'SMTP_PASS') {
            $value = str_repeat('*', strlen($value));
        }
        echo "$config: $value\n";
    } else {
        echo "$config: ❌ NÃO DEFINIDA\n";
    }
}

// 3. Testar MailService se existir
if (class_exists('MailService')) {
    echo "\n=== TESTE MAILSERVICE ===\n";
    try {
        $mailService = MailService::getInstance();
        echo "MailService instanciado: ✅\n";
        
        // Verificar métodos
        $methods = ['sendConfirmationEmail', 'getLastError'];
        foreach ($methods as $method) {
            echo "Método $method existe: " . (method_exists($mailService, $method) ? "✅" : "❌") . "\n";
        }
        
        // Teste de envio
        echo "\nTentando enviar email de teste...\n";
        $result = $mailService->sendConfirmationEmail(
            'carlospintojunior@gmail.com',
            'Teste Debug',
            'debug_token_' . time()
        );
        
        if ($result) {
            echo "✅ Email enviado com sucesso!\n";
        } else {
            echo "❌ Falha no envio: " . $mailService->getLastError() . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Erro no MailService: " . $e->getMessage() . "\n";
    }
} else {
    echo "\n❌ MailService não encontrado! Verificando arquivos...\n";
    
    // Listar arquivos includes
    $includesDir = __DIR__ . '/includes/';
    if (is_dir($includesDir)) {
        echo "Arquivos em includes/:\n";
        foreach (scandir($includesDir) as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "- $file\n";
            }
        }
    }
}

// 4. Teste direto PHPMailer
echo "\n=== TESTE PHPMAILER DIRETO ===\n";
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = '38.242.252.19';
    $mail->Port = 465;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->SMTPAuth = true;
    $mail->Username = 'capivara@capivaralearn.com.br';
    $mail->Password = '_,CeLlORRy,92';
    
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    $mail->setFrom('capivara@capivaralearn.com.br', 'Debug Test');
    $mail->addAddress('carlospintojunior@gmail.com');
    $mail->Subject = 'Debug Test - ' . date('H:i:s');
    $mail->Body = 'Teste de debug do sistema';
    
    $mail->send();
    echo "✅ PHPMailer direto funcionou!\n";
    
} catch (Exception $e) {
    echo "❌ PHPMailer direto falhou: " . $e->getMessage() . "\n";
}

// 5. Informações do sistema
echo "\n=== INFORMAÇÕES DO SISTEMA ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Include Path: " . get_include_path() . "\n";
echo "Current Dir: " . __DIR__ . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";

echo "</pre>";
?>