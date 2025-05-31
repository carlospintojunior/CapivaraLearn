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
// CONFIGURAÇÕES DE EMAIL
// =============================================
$envConfig = $isProduction ? $config['production'] : $config['development'];
define('MAIL_HOST', $envConfig['mail_host'] ?? 'localhost');
define('MAIL_PORT', $envConfig['mail_port'] ?? 587);
define('MAIL_USERNAME', $envConfig['mail_username'] ?? '');
define('MAIL_PASSWORD', $envConfig['mail_password'] ?? '');
define('MAIL_FROM_NAME', $envConfig['mail_from_name'] ?? 'CapivaraLearn');

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
// CLASSE DE SERVIÇO DE EMAIL
// =============================================
class MailService {
    private static $instance = null;
    
    private function __construct() {
        // Construtor privado para Singleton
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function sendConfirmationEmail($email, $nome, $token) {
        try {
            $confirmationLink = url("confirm_email.php?token=" . urlencode($token));
            
            $subject = APP_NAME . " - Confirmação de Email";
            
            $message = "Olá {$nome},\n\n";
            $message .= "Obrigado por se cadastrar no " . APP_NAME . "!\n\n";
            $message .= "Para confirmar seu email, clique no link abaixo:\n";
            $message .= $confirmationLink . "\n\n";
            $message .= "Este link é válido por 24 horas.\n\n";
            $message .= "Se você não solicitou este cadastro, ignore este email.\n\n";
            $message .= "Atenciosamente,\n";
            $message .= "Equipe " . APP_NAME;
            
            $headers = [
                'From' => MAIL_FROM_NAME . ' <' . MAIL_USERNAME . '>',
                'Reply-To' => MAIL_USERNAME,
                'X-Mailer' => 'PHP/' . phpversion(),
                'MIME-Version' => '1.0',
                'Content-Type' => 'text/plain; charset=UTF-8'
            ];
            
            // Em desenvolvimento, simular o envio e logar detalhes
            if (APP_ENV === 'development') {
                error_log("\n=== EMAIL SIMULADO ===");
                error_log("Para: $email");
                error_log("Assunto: $subject");
                error_log("Mensagem:\n$message");
                error_log("Headers:");
                error_log(print_r($headers, true));
                error_log("Resposta Simulada: 250 OK - Mensagem aceita para entrega");
                error_log("==================\n");
                return true;
            }                // Em produção, usar SMTP real
            if (APP_ENV === 'production' && !empty(MAIL_PASSWORD)) {
                $smtpServer = MAIL_HOST;
                $smtpPort = MAIL_PORT;
                
                // Configurar contexto SSL
                $ctx = stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ]);

                // Iniciar conexão SMTP com SSL
                $smtp = stream_socket_client(
                    "ssl://{$smtpServer}:{$smtpPort}",
                    $errno,
                    $errstr,
                    30,
                    STREAM_CLIENT_CONNECT,
                    $ctx
                );

                if (!$smtp) {
                    error_log("Erro de conexão SMTP: $errstr ($errno)");
                    throw new Exception("Erro de conexão com servidor de email");
                }
                
                // Ler resposta inicial do servidor
                $response = fgets($smtp, 515);
                error_log("Resposta inicial do servidor: $response");
                
                // Enviar EHLO
                fputs($smtp, "EHLO " . MAIL_HOST . "\r\n");
                $response = fgets($smtp, 515);
                error_log("Resposta EHLO: $response");
                
                // Para conexão SSL direta (porta 465), não é necessário STARTTLS
                // Para conexão SSL direta na porta 465, a conexão já está segura
                if (MAIL_PORT == 587) {
                    error_log("Porta 587 não é mais suportada, use a porta 465 para SSL direto");
                    throw new Exception("Configuração de porta SMTP inválida");
                }
                
                // Autenticação
                fputs($smtp, "AUTH LOGIN\r\n");
                $response = fgets($smtp, 515);
                error_log("Resposta AUTH: $response");
                
                fputs($smtp, base64_encode(MAIL_USERNAME) . "\r\n");
                $response = fgets($smtp, 515);
                error_log("Resposta USERNAME: $response");
                
                fputs($smtp, base64_encode(MAIL_PASSWORD) . "\r\n");
                $response = fgets($smtp, 515);
                error_log("Resposta PASSWORD: $response");
                
                // Enviar email
                fputs($smtp, "MAIL FROM: <" . MAIL_USERNAME . ">\r\n");
                $response = fgets($smtp, 515);
                error_log("Resposta MAIL FROM: $response");
                
                fputs($smtp, "RCPT TO: <$email>\r\n");
                $response = fgets($smtp, 515);
                error_log("Resposta RCPT TO: $response");
                
                fputs($smtp, "DATA\r\n");
                $response = fgets($smtp, 515);
                error_log("Resposta DATA: $response");
                
                // Montar cabeçalhos e corpo
                $emailContent = "";
                foreach ($headers as $name => $value) {
                    $emailContent .= "$name: $value\r\n";
                }
                $emailContent .= "Subject: $subject\r\n";
                $emailContent .= "\r\n$message\r\n.\r\n";
                
                fputs($smtp, $emailContent);
                $response = fgets($smtp, 515);
                error_log("Resposta envio: $response");
                
                // Encerrar conexão
                fputs($smtp, "QUIT\r\n");
                fclose($smtp);
                
                if (strpos($response, '250') === 0) {
                    error_log("Email enviado com sucesso para: $email");
                    return true;
                } else {
                    error_log("Falha no envio do email para: $email");
                    throw new Exception("Falha no envio do email: $response");
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
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
    
    // Log de teste para verificar se está funcionando
    error_log("=== Teste de Log ===");
    error_log("Data: " . date('Y-m-d H:i:s'));
    error_log("APP_ENV: " . APP_ENV);
    error_log("MAIL_HOST: " . MAIL_HOST);
    error_log("MAIL_PORT: " . MAIL_PORT);
    error_log("MAIL_USERNAME: " . MAIL_USERNAME);
    error_log("==================");
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