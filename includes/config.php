<?php
/**
 * CapivaraLearn - Configurações do Sistema
 * Domínio: capivaralearn.com.br
 * Versão completa - corrigida e funcional
 */

// =============================================
// DETECTAR AMBIENTE PRIMEIRO
// =============================================
$isProduction = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'capivaralearn.com.br') !== false);

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
    
    // Forçar HTTPS
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header("Location: $redirect_url", true, 301);
        exit();
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
// CONFIGURAÇÕES DE EMAIL (para futuro)
// =============================================
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'contato@capivaralearn.com.br');
define('MAIL_PASSWORD', ''); // Configurar quando necessário
define('MAIL_FROM_NAME', 'CapivaraLearn');

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
    error_reporting(0);
    ini_set('display_errors', 0);
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
// LOG DE SISTEMA (versão segura)
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
}
?>