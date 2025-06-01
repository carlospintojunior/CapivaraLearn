<?php
/**
 * CapivaraLearn - Serviço de Email
 * @version 1.0.0
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailService {
    private static $instance = null;
    private $mailer;
    private $configs;
    
    public function __construct() {
        // Carregar configurações do environment.ini
        $this->configs = parse_ini_file(__DIR__ . '/environment.ini', true);
        $env = $this->configs['environment']['environment'];
        
        try {
            $this->mailer = new PHPMailer(true);
            
            // Configuração do servidor SMTP com debug detalhado (igual ao teste que funciona)
            $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            $this->mailer->Debugoutput = function($str, $level) {
                error_log("DEBUG SMTP: $str");
            };
            
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->configs[$env]['mail_host'];
            $this->mailer->Port = $this->configs[$env]['mail_port'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->mailer->SMTPAuth = true;
            
            // Desativar verificação de certificado
            $this->mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Credenciais
            $this->mailer->Username = $this->configs[$env]['mail_username'];
            $this->mailer->Password = $this->configs[$env]['mail_password'];
            
            // Configurações padrão
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->setFrom(
                $this->configs[$env]['mail_username'],
                $this->configs[$env]['mail_from_name']
            );
            
        } catch (Exception $e) {
            error_log("Erro ao inicializar MailService: " . $e->getMessage());
            throw new Exception("Não foi possível inicializar o serviço de email");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Envia um email
     */
    public function send($to, $subject, $body, $isHTML = true) {
        try {
            error_log("=== Iniciando envio de email ===");
            error_log("Para: $to");
            error_log("Assunto: $subject");
            
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML($isHTML);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            if (!$isHTML) {
                $this->mailer->AltBody = strip_tags($body);
            }
            
            error_log("Tentando enviar email...");
            $result = $this->mailer->send();
            error_log("Email enviado com sucesso!");
            
            return $result;
            
        } catch (Exception $e) {
            $error = "Erro ao enviar email: " . $this->mailer->ErrorInfo;
            error_log($error);
            throw new Exception($error);
        }
    }
    
    /**
     * Envia um email de confirmação para um novo usuário
     */
    public function sendConfirmationEmail($to, $name, $token) {
        $subject = "Bem-vindo ao CapivaraLearn! Confirme seu email";
        
        $body = "
            <h2>Olá {$name}!</h2>
            <p>Bem-vindo ao CapivaraLearn! Para começar a usar o sistema, confirme seu email clicando no link abaixo:</p>
            <p><a href='" . $this->configs[$this->configs['environment']['environment']]['base_url'] . "/confirm_email.php?token={$token}'>
                Confirmar meu email
            </a></p>
            <p>Se o botão não funcionar, copie e cole este link no seu navegador:</p>
            <p>" . $this->configs[$this->configs['environment']['environment']]['base_url'] . "/confirm_email.php?token={$token}</p>
            <p>Se você não criou uma conta no CapivaraLearn, ignore este email.</p>
            <br>
            <p>Atenciosamente,<br>Equipe CapivaraLearn</p>
        ";
        
        return $this->send($to, $subject, $body);
    }
}
