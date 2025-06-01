<?php
require_once "includes/config.php";
require_once "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log');

function testSMTP($host, $port, $user, $pass) {
    error_log("=== Teste de SMTP ===");
    error_log("Host: $host");
    error_log("Porta: $port");
    error_log("Usuário: $user");
    
    try {
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) {
            error_log("DEBUG SMTP: $str");
            echo "DEBUG: $str\n";
        };
        
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
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
        
        $mail->Username = $user;
        $mail->Password = $pass;
        
        $mail->setFrom($user, 'CapivaraLearn');
        $mail->addAddress('carlospintojunior@gmail.com', 'Carlos');
        
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = 'Teste DNS vs IP - ' . date('Y-m-d H:i:s');
        $mail->Body = "Este é um email de teste usando host: $host";
        
        error_log("Tentando enviar email...");
        $mail->send();
        error_log("Email enviado com sucesso!");
        echo "Email enviado com sucesso!";
        
    } catch (Exception $e) {
        $error = "Erro ao enviar email: " . $mail->ErrorInfo;
        error_log($error);
        echo "<pre>$error</pre>";
    }
    error_log("=== Fim do Teste ===\n");
}

// Teste com IP
error_log("\n=== TESTE COM IP DIRETO ===");
testSMTP('38.242.252.19', 465, 'capivara@capivaralearn.com.br', '_,CeLlORRy,92');

// Pausa para não sobrecarregar
sleep(2);

// Teste com DNS
error_log("\n=== TESTE COM DNS ===");
testSMTP('mail.capivaralearn.com.br', 465, 'capivara@capivaralearn.com.br', '_,CeLlORRy,92');
?>
