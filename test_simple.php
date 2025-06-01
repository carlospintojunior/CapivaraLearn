<?php
require_once "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        echo "DEBUG: $str\n";
    };
    
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
    $mail->Subject = 'Teste Simples - ' . date('Y-m-d H:i:s');
    $mail->Body = "Este é um teste simples de email";
    
    echo "Tentando enviar email...\n";
    $mail->send();
    echo "Email enviado com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro ao enviar email: " . $mail->ErrorInfo . "\n";
}
