<?php
require_once __DIR__ . "/../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log');

error_log("=== INICIANDO TESTE DE EMAIL ===");

try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        error_log("DEBUG SMTP: $str");
    };
    
    error_log("Configurando SMTP...");
    
    $mail->isSMTP();
    $mail->Host = 'mail.capivaralearn.com.br';
    $mail->Port = 465;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->SMTPAuth = true;
    
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    $mail->Username = 'capivara@capivaralearn.com.br';
    $mail->Password = '_,CeLlORRy,92';
    
    $mail->setFrom('capivara@capivaralearn.com.br', 'CapivaraLearn');
    $mail->addAddress('carlospintojunior@gmail.com', 'Carlos');
    
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    $mail->Subject = 'Teste Final - ' . date('Y-m-d H:i:s');
    $mail->Body = "Este é um teste final de email";
    
    error_log("Tentando enviar email...");
    $mail->send();
    error_log("Email enviado com sucesso!");
    
} catch (Exception $e) {
    error_log("Erro ao enviar email: " . $mail->ErrorInfo);
}
