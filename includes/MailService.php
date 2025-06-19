<?php
require_once "vendor/autoload.php";
require_once __DIR__ . '/log_sistema.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailService {
    private static $instance = null;
    private $lastError = '';
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function sendConfirmationEmail($email, $name, $token) {
        // Sistema de log simples e direto
        $logFile = '/opt/lampp/htdocs/CapivaraLearn/logs/mailservice.log';
        
        // Função de log inline
        $logMessage = function($message, $level = 'INFO') use ($logFile) {
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0777, true);
            }
            
            $timestamp = date('Y-m-d H:i:s');
            $logLine = "[$timestamp] $level | $message" . PHP_EOL;
            @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        };
        
        $logMessage("=== INICIANDO ENVIO EMAIL ===");
        $logMessage("Destinatario: $email");
        $logMessage("Nome: $name");
        
        // Log global do sistema
        log_sistema("[MailService] Iniciando envio de email para $email", 'INFO');
        
        try {
            $mail = new PHPMailer(true);
            
            // Configurações SMTP 
            $mail->isSMTP();
            $mail->Host = 'mail.capivaralearn.com.br';
            $mail->SMTPAuth = true;
            $mail->Username = 'capivara@capivaralearn.com.br';
            $mail->Password = '_,CeLlORRy,92';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL para porta 465
            $mail->Port = 465;
            $mail->Timeout = 30;
            
            $logMessage("Configuracoes SMTP definidas");
            $logMessage("Host: " . $mail->Host);
            $logMessage("Port: " . $mail->Port);
            $logMessage("Username: " . $mail->Username);
            $logMessage("SMTPSecure: " . $mail->SMTPSecure);
            
            // Configurações SSL
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            $logMessage("Configuracoes SSL definidas");
            
            // Debug do PHPMailer
            $debugOutput = '';
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function($str, $level) use (&$debugOutput, $logMessage) {
                $debugOutput .= "[$level] $str\n";
                $logMessage("SMTP DEBUG [$level]: " . trim($str));
            };
            
            // Configurações do remetente
            $fromEmail = 'capivara@capivaralearn.com.br';
            $fromName = 'CapivaraLearn';
            
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($email, $name);
            $mail->addReplyTo($fromEmail, $fromName);
            
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Confirme seu cadastro no CapivaraLearn';
            
            $logMessage("Configuracoes de email definidas");
            
            // URL de confirmação
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $path = dirname($_SERVER['PHP_SELF'] ?? '/');
            $confirmUrl = $protocol . $host . $path . '/confirm_email.php?token=' . urlencode($token);
            
            $logMessage("URL confirmacao: $confirmUrl");
            
            // HTML do email
            $mail->Body = $this->getConfirmationEmailTemplate($name, $confirmUrl);
            $mail->AltBody = "Olá $name,\n\nPara confirmar seu cadastro, acesse: $confirmUrl\n\nEquipe CapivaraLearn";
            
            $logMessage("Conteudo do email definido");
            $logMessage("Tentando enviar email...");
            
            $startTime = microtime(true);
            $mail->send();
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            $logMessage("EMAIL ENVIADO COM SUCESSO! Tempo: {$duration}s");
            $logMessage("=== ENVIO FINALIZADO COM SUCESSO ===");
            
            // Log global de sucesso
            log_sistema("[MailService] Email enviado com sucesso para $email", 'SUCCESS');
            
            $this->lastError = '';
            return true;
            
        } catch (Exception $e) {
            $endTime = microtime(true);
            $duration = isset($startTime) ? round($endTime - $startTime, 2) : 0;
            
            $this->lastError = $e->getMessage();
            
            $logMessage("=== ERRO NO ENVIO ===", "ERROR");
            $logMessage("Mensagem: " . $this->lastError, "ERROR");
            $logMessage("Tempo: {$duration}s", "ERROR");
            
            if (isset($mail) && !empty($mail->ErrorInfo)) {
                $logMessage("PHPMailer ErrorInfo: " . $mail->ErrorInfo, "ERROR");
            }
            
            if (!empty($debugOutput)) {
                $logMessage("=== DEBUG OUTPUT ===", "ERROR");
                $logMessage($debugOutput, "ERROR");
            }
            
            $logMessage("=== ENVIO FINALIZADO COM ERRO ===", "ERROR");
            
            // Log global de erro
            log_sistema("[MailService] ERRO ao enviar email para $email: " . $this->lastError, 'ERROR');
            
            return false;
        }
    }
    
    public function getLastError() {
        return $this->lastError;
    }
    
    public function getConfig() {
        return [
            'host' => defined('MAIL_HOST') ? MAIL_HOST : 'NÃO DEFINIDO',
            'port' => defined('MAIL_PORT') ? MAIL_PORT : 'NÃO DEFINIDO',
            'user' => defined('MAIL_USERNAME') ? MAIL_USERNAME : 'NÃO DEFINIDO',
            'from_name' => defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'NÃO DEFINIDO',
            'from_email' => defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'NÃO DEFINIDO'
        ];
    }
    
    private function getConfirmationEmailTemplate($name, $confirmUrl) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; }
                .button { display: inline-block; background: #27ae60; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; }
                .footer { background: #ecf0f1; padding: 20px; text-align: center; font-size: 12px; color: #7f8c8d; border-radius: 0 0 10px 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🦫 CapivaraLearn</h1>
                    <p>Sistema de Organização de Estudos</p>
                </div>
                <div class='content'>
                    <h2>Olá, $name!</h2>
                    <p>Bem-vindo ao <strong>CapivaraLearn</strong>! Para completar seu cadastro, você precisa confirmar seu endereço de email.</p>
                    <p>Clique no botão abaixo para ativar sua conta:</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='$confirmUrl' class='button'>✅ Confirmar Email</a>
                    </p>
                    <p><small>Ou copie e cole este link no seu navegador:<br>
                    <a href='$confirmUrl'>$confirmUrl</a></small></p>
                    
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                    
                    <p><strong>⚠️ Importante:</strong></p>
                    <ul>
                        <li>Este link expira em 24 horas</li>
                        <li>Se você não solicitou este cadastro, pode ignorar este email</li>
                        <li>Não compartilhe este link com outras pessoas</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>Este email foi enviado automaticamente pelo sistema CapivaraLearn.<br>
                    Se você tem dúvidas, entre em contato conosco.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>