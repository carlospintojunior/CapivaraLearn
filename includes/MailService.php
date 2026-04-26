<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/log_sistema.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!class_exists('MailService')) {
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
        $logFile = __DIR__ . '/../logs/mailservice.log';
        
        // Função de log inline
        $logMessage = function($message, $level = 'INFO') use ($logFile) {
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                if (!mkdir($logDir, 0777, true) && !is_dir($logDir)) {
                    log_sistema("[MailService] Falha ao criar diretório de log: $logDir", 'ERROR');
                }
            }
            $timestamp = date('Y-m-d H:i:s');
            $logLine = "[$timestamp] $level | $message" . PHP_EOL;
            if (file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX) === false) {
                log_sistema("[MailService] Falha ao escrever no arquivo de log: $logFile", 'ERROR');
            }
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
            $mail->Host = defined('MAIL_HOST') ? MAIL_HOST : 'mail.capivaralearn.com.br';
            $mail->SMTPAuth = true;
            $mail->Username = defined('MAIL_USERNAME') ? MAIL_USERNAME : '';
            $mail->Password = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL para porta 465
            $mail->Port = defined('MAIL_PORT') ? MAIL_PORT : 465;
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
            $fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'capivara@capivaralearn.com.br';
            $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'CapivaraLearn';
            
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
    
    public function sendPasswordResetEmail($email, $name, $token) {
        $logFile = __DIR__ . '/../logs/mailservice.log';
        $logMessage = function($message, $level = 'INFO') use ($logFile) {
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($logFile, "[$timestamp] $level | $message" . PHP_EOL, FILE_APPEND | LOCK_EX);
        };

        $logMessage("=== INICIANDO ENVIO EMAIL RESET SENHA ===");
        $logMessage("Destinatario: $email");
        log_sistema("[MailService] Iniciando envio de email de reset para $email", 'INFO');

        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = defined('MAIL_HOST') ? MAIL_HOST : 'mail.capivaralearn.com.br';
            $mail->SMTPAuth   = true;
            $mail->Username   = defined('MAIL_USERNAME') ? MAIL_USERNAME : '';
            $mail->Password   = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = defined('MAIL_PORT') ? MAIL_PORT : 465;
            $mail->Timeout    = 30;
            $mail->SMTPOptions = [
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]
            ];

            $debugOutput = '';
            $mail->SMTPDebug  = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function($str, $level) use (&$debugOutput, $logMessage) {
                $debugOutput .= "[$level] $str\n";
                $logMessage("SMTP DEBUG [$level]: " . trim($str));
            };

            $fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'capivara@capivaralearn.com.br';
            $fromName  = defined('MAIL_FROM_NAME')  ? MAIL_FROM_NAME  : 'CapivaraLearn';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($email, $name);
            $mail->addReplyTo($fromEmail, $fromName);

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Redefinição de senha - CapivaraLearn';

            $protocol  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $path      = dirname($_SERVER['PHP_SELF'] ?? '/');
            $resetUrl  = $protocol . $host . $path . '/reset_password.php?token=' . urlencode($token);

            $logMessage("URL reset: $resetUrl");

            $mail->Body    = $this->getPasswordResetEmailTemplate($name, $resetUrl);
            $mail->AltBody = "Olá $name,\n\nPara redefinir sua senha, acesse: $resetUrl\n\nEste link expira em 1 hora.\n\nSe você não solicitou a redefinição, ignore este email.\n\nEquipe CapivaraLearn";

            $mail->send();

            $logMessage("EMAIL RESET ENVIADO COM SUCESSO!");
            log_sistema("[MailService] Email de reset enviado com sucesso para $email", 'SUCCESS');

            $this->lastError = '';
            return true;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            $logMessage("ERRO no envio de reset: " . $this->lastError, 'ERROR');
            log_sistema("[MailService] ERRO ao enviar email de reset para $email: " . $this->lastError, 'ERROR');
            return false;
        }
    }

    private function getPasswordResetEmailTemplate($name, $resetUrl) {
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
                .button { display: inline-block; background: #e74c3c; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; }
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
                    <p>Recebemos uma solicitação para redefinir a senha da sua conta no <strong>CapivaraLearn</strong>.</p>
                    <p>Clique no botão abaixo para criar uma nova senha:</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='$resetUrl' class='button'>🔑 Redefinir Senha</a>
                    </p>
                    <p><small>Ou copie e cole este link no seu navegador:<br>
                    <a href='$resetUrl'>$resetUrl</a></small></p>

                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>

                    <p><strong>⚠️ Importante:</strong></p>
                    <ul>
                        <li>Este link expira em <strong>1 hora</strong></li>
                        <li>Se você não solicitou a redefinição, ignore este email — sua senha permanece a mesma</li>
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
} // end class_exists('MailService')
?>