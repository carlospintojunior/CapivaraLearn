<?php
/**
 * CapivaraLearn - Configurações do Sistema
 * Domínio: capivaralearn.com.br
 * Versão completa - corrigida e funcional
 * 
 * Para definir o ambiente manualmente, copie o arquivo environment.ini.example
 * para environment.ini e ajuste as configurações conforme necessário.
 * Se o arquivo environment.ini não existir, o ambiente será detectado
 * automaticamente com base no domínio.
 */

// =============================================
// DETECTAR AMBIENTE PRIMEIRO
// =============================================

// Tentar carregar configuração do arquivo .ini
$envFile = __DIR__ . '/environment.ini';
$config = null;

if (file_exists($envFile)) {
    $config = parse_ini_file($envFile, true);
    $isProduction = isset($config['environment']['environment']) && 
                   strtolower($config['environment']['environment']) === 'production';
} else {
    // Fallback para detecção automática baseada no domínio
    $isProduction = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'capivaralearn.com.br') !== false);
}

// =============================================
// CONFIGURAÇÕES DE SESSÃO (ANTES de session_start)
// =============================================
if (session_status() === PHP_SESSION_NONE) {
    // Configurar parâmetros de sessão apenas se sessão não estiver ativa
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.use_only_cookies', 1);
    
    if ($isProduction) {
        @ini_set('session.cookie_secure', 1);
        @ini_set('session.cookie_samesite', 'Strict');
    }
    
    session_start();
}

// =============================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// =============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'capivaralearn');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// =============================================
// CONFIGURAÇÕES DA APLICAÇÃO
// =============================================
define('APP_NAME', 'CapivaraLearn');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Sistema de Organização de Estudos Modulares');

if ($isProduction) {
    // CONFIGURAÇÕES DE PRODUÇÃO
    define('APP_URL', 'https://capivaralearn.com.br');
    define('APP_ENV', 'production');
    define('DEBUG_MODE', false);
    
    // Forçar HTTPS apenas se não estiver em CLI
    if (php_sapi_name() !== 'cli') {
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location: $redirect_url", true, 301);
            exit();
        }
    }
} else {
    // CONFIGURAÇÕES DE DESENVOLVIMENTO
    define('APP_URL', 'http://localhost/CapivaraLearn');
    define('APP_ENV', 'development');
    define('DEBUG_MODE', true);
}

define('TIMEZONE', 'America/Sao_Paulo');

// =============================================
// CONFIGURAÇÕES DE SEGURANÇA
// =============================================
define('SECRET_KEY', 'capivaralearn_2025_' . ($isProduction ? 'prod' : 'dev') . '_secret_key');
define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 dias
define('PASSWORD_MIN_LENGTH', 6);

// Configurar timezone
date_default_timezone_set(TIMEZONE);

// =============================================
// CONFIGURAÇÕES DE EMAIL - CORRIGIDAS
// =============================================

// Configurações padrão que funcionam (baseadas no seu teste que funciona)
// SEMPRE usar o IP direto que funciona nos testes, tanto em dev quanto em prod
define('MAIL_HOST', '38.242.252.19'); // IP direto que funciona
define('MAIL_PORT', 465);
define('MAIL_USERNAME', 'capivara@capivaralearn.com.br');
define('MAIL_PASSWORD', '_,CeLlORRy,92');
define('MAIL_FROM_NAME', 'CapivaraLearn');

// Configurações adicionais de email
define('MAIL_FROM_EMAIL', MAIL_USERNAME);
define('MAIL_SECURE', 'ssl'); // ssl para porta 465
define('MAIL_AUTH', true);

// Configurações adicionais de compatibilidade para scripts que usam SMTP_*
define('SMTP_HOST', MAIL_HOST);
define('SMTP_PORT', MAIL_PORT);
define('SMTP_USER', MAIL_USERNAME);
define('SMTP_PASS', MAIL_PASSWORD);
define('SMTP_FROM_NAME', MAIL_FROM_NAME);

// =============================================
// CLASSE DE CONEXÃO COM BANCO
// =============================================
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Erro de conexão: " . $e->getMessage());
            } else {
                die("Erro interno do sistema. Tente novamente em alguns minutos.");
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Método para executar queries SELECT
    public function select($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erro SQL SELECT: " . $e->getMessage());
            return false;
        }
    }
    
    // Método para executar queries INSERT/UPDATE/DELETE
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Erro SQL EXECUTE: " . $e->getMessage());
            return false;
        }
    }
    
    // Método para pegar o último ID inserido
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    // Método para contar registros
    public function count($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erro SQL COUNT: " . $e->getMessage());
            return 0;
        }
    }
}

// =============================================
// CLASSE DE SERVIÇO DE EMAIL - CORRIGIDA
// =============================================
require_once "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailService {
    private static $instance = null;
    private $lastError = '';
    
    private function __construct() {
        // Log de inicialização
        error_log("MailService inicializado - Host: " . MAIL_HOST . ", Port: " . MAIL_PORT . ", User: " . MAIL_USERNAME);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getLastError() {
        return $this->lastError;
    }
    
    public function sendConfirmationEmail($email, $nome, $token) {
        try {
            error_log("MailService: Tentando enviar email para $email");
            
            $mail = new PHPMailer(true);
            
            // Configurações SMTP usando as constantes definidas
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = MAIL_AUTH;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL para porta 465
            $mail->Port = MAIL_PORT;
            
            // Debug apenas em desenvolvimento
            if (DEBUG_MODE) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $mail->Debugoutput = function($str, $level) {
                    error_log("SMTP Debug: $str");
                };
            }
            
            // Configurações SSL (mesmo do teste que funciona)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Configurações do email
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($email, $nome);
            $mail->addReplyTo(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Confirme seu cadastro no CapivaraLearn';
            
            // URL de confirmação
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $path = dirname($_SERVER['PHP_SELF'] ?? '/');
            $confirmUrl = $protocol . $host . $path . '/confirm_email.php?token=' . urlencode($token);
            
            error_log("MailService: URL de confirmação: $confirmUrl");
            
            // HTML do email
            $mail->Body = $this->getConfirmationEmailTemplate($nome, $confirmUrl);
            $mail->AltBody = "Olá $nome,\n\nPara confirmar seu cadastro, acesse: $confirmUrl\n\nEquipe CapivaraLearn";
            
            $mail->send();
            error_log("MailService: Email enviado com sucesso para $email");
            $this->lastError = '';
            return true;
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("MailService: Erro ao enviar email: " . $this->lastError);
            if (isset($mail)) {
                error_log("MailService: ErrorInfo: " . $mail->ErrorInfo);
            }
            return false;
        }
    }
    
    public function getConfig() {
        return [
            'host' => MAIL_HOST,
            'port' => MAIL_PORT,
            'user' => MAIL_USERNAME,
            'from_name' => MAIL_FROM_NAME,
            'from_email' => MAIL_FROM_EMAIL,
            'secure' => MAIL_SECURE,
            'auth' => MAIL_AUTH
        ];
    }
    
    private function getConfirmationEmailTemplate($nome, $confirmUrl) {
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
                    <h1>🎓 CapivaraLearn</h1>
                    <p>Sistema de Organização de Estudos</p>
                </div>
                <div class='content'>
                    <h2>Olá, $nome!</h2>
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

// =============================================
// FUNÇÕES AUXILIARES
// =============================================

/**
 * Função para gerar hash de senha
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Função para verificar senha
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Função para gerar token de sessão
 */
function generateToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Função para formatar datas
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/**
 * Função para calcular status do tópico
 */
function getTopicStatus($startDate, $endDate, $completed = false) {
    $today = new DateTime();
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    
    if ($completed) {
        return [
            'status' => 'completed',
            'class' => 'status-completed',
            'text' => 'Concluído',
            'color' => '#27ae60'
        ];
    }
    
    if ($today > $end) {
        return [
            'status' => 'overdue',
            'class' => 'status-overdue', 
            'text' => 'Atrasado',
            'color' => '#e74c3c'
        ];
    }
    
    if ($today >= $start && $today <= $end) {
        return [
            'status' => 'active',
            'class' => 'status-active',
            'text' => 'Ativo',
            'color' => '#f39c12'
        ];
    }
    
    return [
        'status' => 'upcoming',
        'class' => 'status-upcoming',
        'text' => 'Futuro',
        'color' => '#3498db'
    ];
}

/**
 * Função para verificar se usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Função para redirecionar se não estiver logado
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Função para fazer logout
 */
function logout() {
    // Invalidar token na base (implementar se necessário)
    if (isset($_SESSION['token'])) {
        $db = Database::getInstance();
        $db->execute(
            "UPDATE sessoes SET ativo = 0 WHERE token = ?",
            [$_SESSION['token']]
        );
    }
    
    // Limpar sessão
    session_destroy();
    
    // Redirecionar
    header('Location: login.php');
    exit();
}

/**
 * Função para escapar output HTML
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Função para debug (apenas em desenvolvimento)
 */
function debug($data) {
    if (DEBUG_MODE) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}

/**
 * Função para retornar JSON
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Função para validar email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Função para limpar entrada
 */
function clean($input) {
    return trim(strip_tags($input));
}

/**
 * Função para gerar URLs absolutas
 */
function url($path = '') {
    return APP_URL . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Função para gerar URLs de assets
 */
function asset($path) {
    return url('public/assets/' . ltrim($path, '/'));
}

// =============================================
// CONFIGURAÇÕES DE ERRO
// =============================================
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL); // Temporariamente habilitado para debug
    ini_set('display_errors', 1); // Temporariamente habilitado para debug
    ini_set('log_errors', 1);
    
    // Criar pasta de logs se não existir
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    if (is_dir($logDir) && is_writable($logDir)) {
        ini_set('error_log', $logDir . '/php_errors.log');
    }
}

// Configurar o arquivo de log de erros do PHP
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

/**
 * Função para verificar permissões de pasta
 */
function checkFolderPermissions($folder) {
    // Verificar se a pasta existe
    if (!is_dir($folder)) {
        return false;
    }
    
    // Verificar permissões
    $permissions = substr(sprintf('%o', fileperms($folder)), -4);
    return $permissions === '0777' || $permissions === '0755';
}

// Verificar permissões da pasta de logs
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

// =============================================
// HEADERS DE SEGURANÇA (apenas em produção)
// =============================================
if (APP_ENV === 'production') {
    // Configurações de segurança
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // HSTS (apenas HTTPS)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// =============================================
// LOG DE SISTEMA
// =============================================
function logActivity($action, $details = '') {
    // Só logar em desenvolvimento e se a pasta existir e for writável
    if (!DEBUG_MODE) return;
    
    $logDir = __DIR__ . '/../logs';
    
    if (is_dir($logDir) && is_writable($logDir)) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $_SESSION['user_id'] ?? 'guest',
            'action' => $action,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200) // Limitar tamanho
        ];
        
        $logContent = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        
        // Usar @ para suprimir warnings e verificar resultado
        if (@file_put_contents($logDir . '/activity.log', $logContent, FILE_APPEND | LOCK_EX) === false) {
            // Se falhar, não fazer nada (modo silencioso)
        }
    }
}

// Log desta inicialização (apenas em desenvolvimento)
if (DEBUG_MODE) {
    logActivity('config_loaded', 'Sistema inicializado para ' . APP_ENV);
    error_log("Config carregado: MAIL_HOST=" . MAIL_HOST . ", MAIL_PORT=" . MAIL_PORT . ", MAIL_USER=" . MAIL_USERNAME);
}
?>