<?php
require_once "vendor/autoload.php";

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
        try {
            $mail = new PHPMailer(true);
            
            // Log para debug detalhado
            error_log("=== MailService: Iniciando envio de email ===");
            error_log("Destinatário: $email");
            error_log("Nome: $name");
            error_log("Token: $token");
            
            // Configurações SMTP usando constantes ou valores padrão
            $mail->isSMTP();
            $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : '38.242.252.19';
            $mail->SMTPAuth = defined('SMTP_AUTH') ? SMTP_AUTH : true;
            $mail->Username = defined('SMTP_USER') ? SMTP_USER : 'capivara@capivaralearn.com.br';
            $mail->Password = defined('SMTP_PASS') ? SMTP_PASS : '_,CeLlORRy,92';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 465;
            
            // Debug level
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function($str, $level) {
                error_log("SMTP Debug [$level]: $str");
            };
            
            // Log das configurações SMTP
            error_log("=== SMTP Config ===");
            error_log("Host: " . $mail->Host);
            error_log("Port: " . $mail->Port);
            error_log("User: " . $mail->Username);
            error_log("Auth: " . ($mail->SMTPAuth ? "true" : "false"));
            error_log("Secure: " . $mail->SMTPSecure);
            
            // Configurações SSL
            if (defined('SMTP_SSL_OPTIONS')) {
                $mail->SMTPOptions = unserialize(SMTP_SSL_OPTIONS);
            } else {
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
            }
            
            // Configurações do remetente
            $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'capivara@capivaralearn.com.br';
            $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'CapivaraLearn';
            
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($email, $name);
            $mail->addReplyTo($fromEmail, $fromName);
            
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Confirme seu cadastro no CapivaraLearn';
            
            // URL de confirmação
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $path = dirname($_SERVER['PHP_SELF'] ?? '/');
            $confirmUrl = $protocol . $host . $path . '/confirm_email.php?token=' . urlencode($token);
            
            error_log("=== Email Content ===");
            error_log("URL de confirmação: $confirmUrl");
            
            // HTML do email
            $mail->Body = $this->getConfirmationEmailTemplate($name, $confirmUrl);
            $mail->AltBody = "Olá $name,\n\nPara confirmar seu cadastro, acesse: $confirmUrl\n\nEquipe CapivaraLearn";
            
            error_log("=== Enviando email ===");
            $mail->send();
            error_log("=== Email enviado com sucesso ===");
            $this->lastError = '';
            return true;
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("=== ERRO no envio de email ===");
            error_log("Mensagem de erro: " . $this->lastError);
            if (isset($mail)) {
                error_log("PHPMailer ErrorInfo: " . $mail->ErrorInfo);
            }
            error_log("=== Stack Trace ===");
            error_log($e->getTraceAsString());
            return false;
        }
    }
    
    public function getLastError() {
        return $this->lastError;
    }
    
    public function getConfig() {
        return [
            'host' => defined('SMTP_HOST') ? SMTP_HOST : 'NÃO DEFINIDO',
            'port' => defined('SMTP_PORT') ? SMTP_PORT : 'NÃO DEFINIDO',
            'user' => defined('SMTP_USER') ? SMTP_USER : 'NÃO DEFINIDO',
            'from_name' => defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'NÃO DEFINIDO',
            'from_email' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'NÃO DEFINIDO'
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
                    <img src='data:image/png;base64,<?php echo base64_encode(file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/assets/images/logo-small.png")); ?>' alt='CapivaraLearn Logo' style='max-width: 150px;'>
                    <h1>CapivaraLearn</h1>
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