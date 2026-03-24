<?php
/**
 * ===============================================
 * 🦫 CapivaraLearn - Arquivo de Configuração
 * ===============================================
 * 
 * ⚠️  ATENÇÃO: ARQUIVO GERADO AUTOMATICAMENTE
 * 
 * Este arquivo foi criado automaticamente pelo instalador em 19/06/2025 02:12:23
 * 
 * 🚨 IMPORTANTE:
 * - NÃO EDITE este arquivo manualmente
 * - Todas as alterações manuais serão PERDIDAS na próxima reinstalação
 * - Para configurações de ambiente, edite o arquivo 'includes/environment.ini'
 * - Para modificações permanentes, edite o template em 'install.php'
 * 
 * 📝 Para recriar este arquivo:
 * 1. Execute: php install.php (via navegador ou linha de comando)
 * 2. Ou delete este arquivo e acesse qualquer página do sistema
 * 
 * 🔧 Gerado pela versão: 1.0.0
 * 📅 Data de criação: 19/06/2025 02:12:23
 * 🖥️  Servidor: localhost
 * 
 * ===============================================
 */

// Configuração de fuso horário para o Brasil (São Paulo)
date_default_timezone_set('America/Sao_Paulo');

// Incluir sistema de versionamento
require_once __DIR__ . '/version.php';

// Incluir e configurar o logger centralizado (Monolog)
require_once __DIR__ . '/logger_config.php';

// Log de início de sistema
logInfo('Sistema iniciado', ['request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A']);

// =============================================
// CONFIGURAÇÃO DE PRODUÇÃO (SEMPRE)
// =============================================

// Carregar configuração do arquivo .ini
$envFile = __DIR__ . '/environment.ini';
$config = null;

if (file_exists($envFile)) {
    $config = parse_ini_file($envFile, true);
}

// SEMPRE PRODUÇÃO
$isProduction = true;

// =============================================
// CONFIGURAÇÕES DE SESSÃO (ANTES de session_start)
// =============================================
if (session_status() === PHP_SESSION_NONE) {
    // Configurar diretório de sessões local para evitar problemas de permissão
    $sessionDir = __DIR__ . '/../logs/sessions';
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0777, true);
    }
    @ini_set('session.save_path', $sessionDir);
    
    // Configurações básicas de cookies
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.use_only_cookies', 1);
    
    // Para desenvolvimento local HTTP - não usar cookie_secure
    // Em produção HTTPS, essas configurações devem ser ajustadas
    @ini_set('session.cookie_secure', 0);
    @ini_set('session.cookie_samesite', 'Lax');
    
    session_start();
}

// =============================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// =============================================
if ($config && isset($config['production'])) {
    $envConfig = $config['production'];
    define('DB_HOST', $envConfig['db_host'] ?? 'localhost');
    define('DB_NAME', $envConfig['db_name'] ?? 'capivaralearn');
    define('DB_USER', $envConfig['db_user'] ?? 'root');
    define('DB_PASS', $envConfig['db_pass'] ?? '');
    define('DB_CHARSET', $envConfig['db_charset'] ?? 'utf8mb4');
} else {
    // Configurações padrão
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'capivaralearn');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
}

// =============================================
// CONFIGURAÇÕES BASEADAS NO AMBIENTE
// =============================================
// CONFIGURAÇÕES DE PRODUÇÃO (SEMPRE)
// =============================================
define('APP_URL', 'http://localhost/CapivaraLearn');
define('APP_ENV', 'production');
define('DEBUG_MODE', true); // Manter debug ativo para logs

define('TIMEZONE', 'America/Sao_Paulo');

// =============================================
// CONFIGURAÇÕES DE EMAIL
// =============================================
// As credenciais são carregadas do arquivo environment.ini
// que NÃO é versionado no Git (listado no .gitignore).
//
// Para alterar configurações de email, edite:
//   includes/environment.ini → seção [production]
//
// Parâmetros disponíveis no environment.ini:
//   mail_host       - Servidor SMTP (ex: mail.capivaralearn.com.br)
//   mail_port       - Porta SMTP (465 para SSL, 587 para TLS)
//   mail_username   - Usuário de autenticação SMTP
//   mail_password   - Senha de autenticação SMTP
//   mail_from_name  - Nome exibido como remetente
//   mail_from_email - Endereço exibido como remetente
//   mail_secure     - Tipo de criptografia (ssl ou tls)
//   mail_auth       - Usar autenticação SMTP (true/false)
// =============================================
if ($config && isset($config['production'])) {
    $envConfig = $config['production'];
    define('MAIL_HOST', $envConfig['mail_host'] ?? 'mail.capivaralearn.com.br');
    define('MAIL_PORT', (int)($envConfig['mail_port'] ?? 465));
    define('MAIL_USERNAME', $envConfig['mail_username'] ?? '');
    define('MAIL_PASSWORD', $envConfig['mail_password'] ?? '');
    define('MAIL_FROM_NAME', $envConfig['mail_from_name'] ?? 'CapivaraLearn');
    define('MAIL_FROM_EMAIL', $envConfig['mail_from_email'] ?? '');
    define('MAIL_SECURE', $envConfig['mail_secure'] ?? 'ssl');
    define('MAIL_AUTH', (bool)($envConfig['mail_auth'] ?? true));
} else {
    // Fallback — sem environment.ini, constantes ficam vazias por segurança.
    // O sistema não conseguirá enviar emails até que o environment.ini seja configurado.
    define('MAIL_HOST', '');
    define('MAIL_PORT', 465);
    define('MAIL_USERNAME', '');
    define('MAIL_PASSWORD', '');
    define('MAIL_FROM_NAME', 'CapivaraLearn');
    define('MAIL_FROM_EMAIL', '');
    define('MAIL_SECURE', 'ssl');
    define('MAIL_AUTH', true);
}

define('APP_NAME', 'CapivaraLearn');

// =============================================
// CLASSE DE BANCO DE DADOS (LEGADO)
// =============================================
/**
 * Classe Database - Conexão com o banco de dados MySQL
 * 
 * Esta classe é responsável por gerenciar a conexão com o banco de dados MySQL
 * utilizando PDO. Ela implementa o padrão Singleton para garantir que apenas
 * uma instância da conexão exista durante a execução do script.
 * 
 * Uso:
 * $db = Database::getInstance()->getConnection();
 * $result = $db->select("SELECT * FROM usuarios WHERE id = ?", [1]);
 */

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Erro de conexão: " . $e->getMessage());
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
    
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Erro SQL EXECUTE: " . $e->getMessage());
            return false;
        }
    }
    
    // MÉTODOS ADICIONADOS PARA COMPATIBILIDADE
    public function insert($table, $data) {
        try {
            $columns = array_keys($data);
            $placeholders = array_map(function($col) { return ':' . $col; }, $columns);
            
            $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->connection->prepare($sql);
            
            // Bind dos parâmetros
            foreach ($data as $column => $value) {
                $stmt->bindValue(':' . $column, $value);
            }
            
            $result = $stmt->execute();
            
            if (DEBUG_MODE) {
                error_log("Database INSERT - Tabela: $table, Dados: " . json_encode($data));
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Erro SQL INSERT: " . $e->getMessage() . " - Tabela: $table");
            if (DEBUG_MODE) {
                error_log("Dados do INSERT: " . json_encode($data));
            }
            return false;
        }
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        try {
            $setClause = [];
            foreach (array_keys($data) as $column) {
                $setClause[] = "$column = :$column";
            }
            
            $sql = "UPDATE $table SET " . implode(', ', $setClause) . " WHERE $where";
            
            $stmt = $this->connection->prepare($sql);
            
            // Bind dos dados
            foreach ($data as $column => $value) {
                $stmt->bindValue(':' . $column, $value);
            }
            
            // Bind dos parâmetros WHERE
            foreach ($whereParams as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            
            $result = $stmt->execute();
            
            if (DEBUG_MODE) {
                error_log("Database UPDATE - Tabela: $table, WHERE: $where");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Erro SQL UPDATE: " . $e->getMessage() . " - Tabela: $table");
            return false;
        }
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

// =============================================
// FUNÇÕES AUXILIARES
// =============================================
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// =============================================
// CONFIGURAÇÕES DE ERRO
// =============================================
if (APP_ENV === 'production') {
    // Produção - Não mostrar erros
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
} else {
    // Desenvolvimento - Mostrar todos os erros
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Configurar timezone
date_default_timezone_set(TIMEZONE);

// Iniciar sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =============================================
// CLASSE DE SERVIÇO DE EMAIL - CORRIGIDA
// =============================================
require_once __DIR__ . "/../vendor/autoload.php";

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
        error_log("DEBUG MailService: Iniciando sendConfirmationEmail para $email");
        
        try {
            error_log("DEBUG MailService: Modo produção - envio real via SMTP");
            $mail = new PHPMailer(true);
            
            error_log("DEBUG MailService: Configurando SMTP...");
            // Configurações SMTP usando constantes
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = MAIL_AUTH;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            
            // Configuração SSL/TLS baseada na porta
            if (MAIL_PORT == 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL para porta 465
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS para porta 587
            }
            
            $mail->Port = MAIL_PORT;
            
            // Timeout e configurações de conexão
            $mail->Timeout = 30; // 30 segundos timeout (aumentado)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            error_log("DEBUG MailService: Configurando remetente e destinatário...");
            // Remetente
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            
            // Destinatário
            $mail->addAddress($email, $name);
            
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = 'Confirme seu cadastro - ' . APP_NAME;
            
            $confirmUrl = APP_URL . '/confirm_email.php?token=' . $token;
            $mail->Body = $this->getConfirmationEmailTemplate($name, $confirmUrl);
            
            error_log("DEBUG MailService: Tentando enviar email...");
            if ($mail->send()) {
                error_log("DEBUG MailService: Email enviado com sucesso!");
                return true;
            } else {
                error_log("DEBUG MailService: Falha ao enviar - " . $mail->ErrorInfo);
                $this->lastError = 'Erro ao enviar email: ' . $mail->ErrorInfo;
                return false;
            }
        } catch (Exception $e) {
            error_log("DEBUG MailService: Exception capturada - " . $e->getMessage());
            $this->lastError = 'Erro no MailService: ' . $e->getMessage();
            
            // Log do erro para debug
            error_log("ERRO SMTP COMPLETO: " . $e->getMessage());
            error_log("HOST: " . MAIL_HOST . " PORT: " . MAIL_PORT);
            error_log("USERNAME: " . MAIL_USERNAME);
            
            return false;
        }
    }
    
    public function getLastError() {
        return $this->lastError;
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
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: #27ae60; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🦫 CapivaraLearn</h1>
                    <p>Bem-vindo(a) ao sistema!</p>
                </div>
                <div class='content'>
                    <h2>Olá, {$nome}!</h2>
                    <p>Obrigado por se cadastrar no CapivaraLearn. Para ativar sua conta, clique no botão abaixo:</p>
                    <div style='text-align: center;'>
                        <a href='{$confirmUrl}' class='button'>✅ Confirmar Cadastro</a>
                    </div>
                    <p><strong>Ou copie e cole este link no seu navegador:</strong></p>
                    <p style='word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 5px;'>{$confirmUrl}</p>
                    <div class='footer'>
                        <p>Este email foi enviado automaticamente. Não responda.</p>
                        <p>© 2025 CapivaraLearn - Sistema de Organização de Estudos</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
}

// =============================================
// LOG INICIAL DO SISTEMA
// =============================================
if (DEBUG_MODE) {
    logActivity('config_loaded', 'Sistema inicializado para ' . APP_ENV);
}

/*
 * ===============================================
 * 🔚 FIM DO ARQUIVO DE CONFIGURAÇÃO
 * ===============================================
 * 
 * ⚠️  LEMBRE-SE: Este arquivo foi gerado automaticamente!
 * 
 * Para modificar configurações:
 * 1. Edite 'includes/environment.ini' para configurações de ambiente
 * 2. Edite 'install.php' para modificações permanentes no template
 * 3. Execute nova instalação para aplicar as mudanças
 * 
 * 📧 Suporte: https://github.com/seu-usuario/CapivaraLearn
 * ===============================================
 */
?>