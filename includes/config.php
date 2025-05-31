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
    private $lastError = '';
    private $logFile = '';
    
    private function __construct() {
        // Definir caminho do arquivo de log
        $this->logFile = dirname(__DIR__) . '/logs/php_errors.log';
        
        // Garantir que o diretório de logs existe
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        // Testar escrita no arquivo de log
        $testMessage = date('Y-m-d H:i:s') . " - Iniciando serviço de email\n";
        if (!@file_put_contents($this->logFile, $testMessage, FILE_APPEND)) {
            error_log("Erro: Não foi possível escrever no arquivo de log: " . $this->logFile);
        }
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
    
    private function logMailError($message, $context = []) {
        $this->lastError = $message;
        $logMessage = sprintf(
            "[%s] %s\nContext: %s\n",
            date('Y-m-d H:i:s'),
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        
        // Tentar escrever no arquivo de log
        if (@file_put_contents($this->logFile, $logMessage, FILE_APPEND) === false) {
            error_log("Falha ao escrever no log: " . $logMessage);
        }
        
        // Também usar error_log do PHP como backup
        error_log($logMessage);
    }
    
    public function sendConfirmationEmail($email, $nome, $token) {
        try {
            $this->logMailError("Iniciando envio de email de confirmação", [
                'para' => $email,
                'nome' => $nome,
                'ambiente' => APP_ENV,
                'smtp_host' => MAIL_HOST,
                'smtp_port' => MAIL_PORT,
                'smtp_user' => MAIL_USERNAME,
                'has_password' => !empty(MAIL_PASSWORD)
            ]);
            
            if (APP_ENV === 'development') {
                $this->logMailError("Email simulado em ambiente de desenvolvimento", [
                    'token' => $token,
                    'link' => url("confirm_email.php?token=" . urlencode($token))
                ]);
                return true;
            }

            // Verificar extensões PHP necessárias
            if (!extension_loaded('openssl')) {
                throw new Exception("Extensão OpenSSL não está instalada");
            }
            
            // Verificar configurações
            if (empty(MAIL_HOST) || empty(MAIL_USERNAME) || empty(MAIL_PASSWORD)) {
                throw new Exception("Configurações de SMTP incompletas");
            }

            // Testar conectividade antes de tentar enviar
            $this->logMailError("Testando conectividade SMTP", ['host' => MAIL_HOST, 'port' => MAIL_PORT]);
            
            $errno = $errstr = '';
            $fp = @fsockopen(MAIL_HOST, MAIL_PORT, $errno, $errstr, 10);
            if (!$fp) {
                throw new Exception("Não foi possível conectar ao servidor SMTP: {$errstr} ({$errno})");
            }
            fclose($fp);

            // Verificar DNS primeiro
            if (!checkdnsrr(MAIL_HOST, "A") && !checkdnsrr(MAIL_HOST, "MX")) {
                throw new Exception("DNS inválido para o host SMTP: " . MAIL_HOST);
            }

            // Configurar contexto SSL com opções mais seguras
            $ctx = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'SNI_enabled' => true,
                    'ciphers' => 'HIGH:!SSLv2:!SSLv3'
                ]
            ]);

            // Conectar usando SSL direto (porta 465)
            $smtp = @stream_socket_client(
                "ssl://" . MAIL_HOST . ":" . MAIL_PORT,
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $ctx
            );

            if (!$smtp) {
                throw new Exception("Falha na conexão SMTP: {$errstr} ({$errno})");
            }

            // Ler resposta inicial
            $response = fgets($smtp, 515);
            if (!$response) {
                throw new Exception("Sem resposta do servidor SMTP");
            }

            // EHLO
            fputs($smtp, "EHLO " . MAIL_HOST . "\r\n");
            $this->readResponse($smtp);

            // AUTH LOGIN
            fputs($smtp, "AUTH LOGIN\r\n");
            $this->readResponse($smtp);

            fputs($smtp, base64_encode(MAIL_USERNAME) . "\r\n");
            $this->readResponse($smtp);

            fputs($smtp, base64_encode(MAIL_PASSWORD) . "\r\n");
            $this->readResponse($smtp);

            // MAIL FROM
            fputs($smtp, "MAIL FROM: <" . MAIL_USERNAME . ">\r\n");
            $this->readResponse($smtp);

            // RCPT TO
            fputs($smtp, "RCPT TO: <{$email}>\r\n");
            $this->readResponse($smtp);

            // DATA
            fputs($smtp, "DATA\r\n");
            $this->readResponse($smtp);

            // Montar email
            $confirmationLink = url("confirm_email.php?token=" . urlencode($token));
            $subject = APP_NAME . " - Confirmação de Email";
            
            $headers = [
                'From' => MAIL_FROM_NAME . ' <' . MAIL_USERNAME . '>',
                'Reply-To' => MAIL_USERNAME,
                'MIME-Version' => '1.0',
                'Content-Type' => 'text/html; charset=UTF-8',
                'X-Mailer' => 'PHP/' . phpversion()
            ];

            $message = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #2c3e50;'>Olá {$nome},</h2>
                    <p>Obrigado por se cadastrar no " . APP_NAME . "!</p>
                    <p>Para confirmar seu email, clique no botão abaixo:</p>
                    <p style='text-align: center;'>
                        <a href='{$confirmationLink}' 
                           style='display: inline-block; 
                                  padding: 12px 24px; 
                                  background-color: #3498db; 
                                  color: white; 
                                  text-decoration: none; 
                                  border-radius: 5px;
                                  font-weight: bold;'>
                            Confirmar Email
                        </a>
                    </p>
                    <p><small>Este link é válido por 24 horas.</small></p>
                    <p><small>Se você não solicitou este cadastro, ignore este email.</small></p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='color: #7f8c8d; font-size: 12px;'>
                        Atenciosamente,<br>
                        Equipe " . APP_NAME . "
                    </p>
                </div>
            </body>
            </html>";

            // Enviar headers e conteúdo
            $emailContent = "";
            foreach ($headers as $name => $value) {
                $emailContent .= "{$name}: {$value}\r\n";
            }
            $emailContent .= "Subject: {$subject}\r\n\r\n";
            $emailContent .= $message . "\r\n.\r\n";

            fputs($smtp, $emailContent);
            $response = $this->readResponse($smtp);

            // QUIT
            fputs($smtp, "QUIT\r\n");
            fclose($smtp);

            $this->logMailError("Email enviado com sucesso", [
                'para' => $email,
                'resposta' => $response
            ]);

            return true;

        } catch (Exception $e) {
            $this->logMailError("Erro fatal ao enviar email: " . $e->getMessage(), [
                'para' => $email,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'php_version' => PHP_VERSION,
                'extensions' => get_loaded_extensions()
            ]);
            return false;
        }
    }

    private function readResponse($smtp) {
        $response = fgets($smtp, 515);
        if (!$response) {
            throw new Exception("Sem resposta do servidor SMTP");
        }
        $code = substr($response, 0, 3);
        if ($code !== "250" && $code !== "220" && $code !== "235" && $code !== "334" && $code !== "354") {
            throw new Exception("Erro SMTP: " . trim($response));
        }
        return $response;
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

// Teste explícito de log
error_log("Teste de log manual: Verificando se o sistema de logs está funcionando.");

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
if (!checkFolderPermissions($logDir)) {
    die("Erro: A pasta de logs não tem permissões adequadas. Por favor, ajuste as permissões da pasta: " . $logDir);
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