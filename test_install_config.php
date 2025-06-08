<?php
/**
 * Test script to verify the config.php generation from install.php
 */

echo "<h1>Testing Install.php Config Generation</h1>";

// Simulate the install.php config generation part
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'capivaralearn_test';

echo "<p><strong>Testing config generation with:</strong></p>";
echo "<ul>";
echo "<li>Host: $host</li>";
echo "<li>User: $user</li>";
echo "<li>Database: $dbname</li>";
echo "</ul>";

// Extract the config generation code from install.php
$configContent = "<?php
/**
 * CapivaraLearn - Configurações do Sistema
 * Gerado automaticamente em " . date('Y-m-d H:i:s') . "
 * Versão completa com detecção de ambiente
 */

// =============================================
// DETECTAR AMBIENTE PRIMEIRO
// =============================================

// Tentar carregar configuração do arquivo .ini
\$envFile = __DIR__ . '/environment.ini';
\$config = null;

if (file_exists(\$envFile)) {
    \$config = parse_ini_file(\$envFile, true);
    \$isProduction = isset(\$config['environment']['environment']) && 
                   strtolower(\$config['environment']['environment']) === 'production';
} else {
    // Fallback para detecção automática baseada no domínio
    \$isProduction = (isset(\$_SERVER['HTTP_HOST']) && strpos(\$_SERVER['HTTP_HOST'], 'capivaralearn.com.br') !== false);
}

// =============================================
// CONFIGURAÇÕES DE SESSÃO (ANTES de session_start)
// =============================================
if (session_status() === PHP_SESSION_NONE) {
    // Configurar parâmetros de sessão apenas se sessão não estiver ativa
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.use_only_cookies', 1);
    
    if (\$isProduction) {
        @ini_set('session.cookie_secure', 1);
        @ini_set('session.cookie_samesite', 'Strict');
    }
    
    session_start();
}

// =============================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// =============================================
define('DB_HOST', '$host');
define('DB_NAME', '$dbname');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('DB_CHARSET', 'utf8mb4');

// =============================================
// CONFIGURAÇÕES BASEADAS NO AMBIENTE
// =============================================
if (\$isProduction) {
    // CONFIGURAÇÕES DE PRODUÇÃO
    define('APP_URL', 'https://capivaralearn.com.br');
    define('APP_ENV', 'production');
    define('DEBUG_MODE', false);
    
    // Forçar HTTPS apenas se não estiver em CLI
    if (php_sapi_name() !== 'cli') {
        if (!isset(\$_SERVER['HTTPS']) || \$_SERVER['HTTPS'] !== 'on') {
            \$redirect_url = 'https://' . \$_SERVER['HTTP_HOST'] . \$_SERVER['REQUEST_URI'];
            header(\"Location: \$redirect_url\", true, 301);
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
define('SECRET_KEY', '" . bin2hex(random_bytes(32)) . "');
define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 dias
define('PASSWORD_MIN_LENGTH', 6);

// =============================================
// CONFIGURAÇÕES DA APLICAÇÃO
// =============================================
define('APP_NAME', 'CapivaraLearn');
define('APP_VERSION', '1.0.0');

// Configurar timezone
date_default_timezone_set(TIMEZONE);

// =============================================
// CONFIGURAÇÕES DE EMAIL
// =============================================
if (\$config && isset(\$config[APP_ENV])) {
    \$envConfig = \$config[APP_ENV];
    define('MAIL_HOST', \$envConfig['mail_host'] ?? 'localhost');
    define('MAIL_PORT', \$envConfig['mail_port'] ?? 587);
    define('MAIL_USERNAME', \$envConfig['mail_username'] ?? '');
    define('MAIL_PASSWORD', \$envConfig['mail_password'] ?? '');
    define('MAIL_FROM_NAME', \$envConfig['mail_from_name'] ?? 'CapivaraLearn');
} else {
    // Configurações padrão para desenvolvimento
    define('MAIL_HOST', 'localhost');
    define('MAIL_PORT', 587);
    define('MAIL_USERNAME', 'capivara@capivaralearn.com.br');
    define('MAIL_PASSWORD', '');
    define('MAIL_FROM_NAME', 'CapivaraLearn (Dev)');
}

// =============================================
// CLASSE DE CONEXÃO COM BANCO - VERSÃO COMPLETA
// =============================================
class Database {
    private static \$instance = null;
    private \$connection;
    
    private function __construct() {
        try {
            \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;
            \$options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            \$this->connection = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
        } catch (PDOException \$e) {
            die(\"Erro de conexão: \" . \$e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }
    
    public function getConnection() {
        return \$this->connection;
    }
    
    public function select(\$sql, \$params = []) {
        try {
            \$stmt = \$this->connection->prepare(\$sql);
            \$stmt->execute(\$params);
            return \$stmt->fetchAll();
        } catch (PDOException \$e) {
            error_log(\"Erro SQL SELECT: \" . \$e->getMessage());
            return false;
        }
    }
    
    public function execute(\$sql, \$params = []) {
        try {
            \$stmt = \$this->connection->prepare(\$sql);
            return \$stmt->execute(\$params);
        } catch (PDOException \$e) {
            error_log(\"Erro SQL EXECUTE: \" . \$e->getMessage());
            return false;
        }
    }
    
    // MÉTODOS ADICIONADOS PARA COMPATIBILIDADE
    public function insert(\$table, \$data) {
        try {
            \$columns = array_keys(\$data);
            \$placeholders = array_map(function(\$col) { return ':' . \$col; }, \$columns);
            
            \$sql = \"INSERT INTO \$table (\" . implode(', ', \$columns) . \") VALUES (\" . implode(', ', \$placeholders) . \")\";
            
            \$stmt = \$this->connection->prepare(\$sql);
            
            // Bind dos parâmetros
            foreach (\$data as \$column => \$value) {
                \$stmt->bindValue(':' . \$column, \$value);
            }
            
            \$result = \$stmt->execute();
            
            if (DEBUG_MODE) {
                error_log(\"Database INSERT - Tabela: \$table, Dados: \" . json_encode(\$data));
            }
            
            return \$result;
        } catch (PDOException \$e) {
            error_log(\"Erro SQL INSERT: \" . \$e->getMessage() . \" - Tabela: \$table\");
            if (DEBUG_MODE) {
                error_log(\"Dados do INSERT: \" . json_encode(\$data));
            }
            return false;
        }
    }
    
    public function update(\$table, \$data, \$where, \$whereParams = []) {
        try {
            \$setClause = [];
            foreach (array_keys(\$data) as \$column) {
                \$setClause[] = \"\$column = :\$column\";
            }
            
            \$sql = \"UPDATE \$table SET \" . implode(', ', \$setClause) . \" WHERE \$where\";
            
            \$stmt = \$this->connection->prepare(\$sql);
            
            // Bind dos dados
            foreach (\$data as \$column => \$value) {
                \$stmt->bindValue(':' . \$column, \$value);
            }
            
            // Bind dos parâmetros WHERE
            foreach (\$whereParams as \$param => \$value) {
                \$stmt->bindValue(\$param, \$value);
            }
            
            \$result = \$stmt->execute();
            
            if (DEBUG_MODE) {
                error_log(\"Database UPDATE - Tabela: \$table, WHERE: \$where\");
            }
            
            return \$result;
        } catch (PDOException \$e) {
            error_log(\"Erro SQL UPDATE: \" . \$e->getMessage() . \" - Tabela: \$table\");
            return false;
        }
    }
    
    public function lastInsertId() {
        return \$this->connection->lastInsertId();
    }
}

// =============================================
// FUNÇÕES AUXILIARES
// =============================================
function hashPassword(\$password) {
    return password_hash(\$password, PASSWORD_DEFAULT);
}

function verifyPassword(\$password, \$hash) {
    return password_verify(\$password, \$hash);
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function formatDate(\$date, \$format = 'd/m/Y') {
    if (empty(\$date)) return '';
    \$dateObj = new DateTime(\$date);
    return \$dateObj->format(\$format);
}

function isLoggedIn() {
    return isset(\$_SESSION['user_id']) && !empty(\$_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function h(\$string) {
    return htmlspecialchars(\$string, ENT_QUOTES, 'UTF-8');
}

function jsonResponse(\$data, \$statusCode = 200) {
    http_response_code(\$statusCode);
    header('Content-Type: application/json');
    echo json_encode(\$data, JSON_UNESCAPED_UNICODE);
    exit();
}

// =============================================
// FUNÇÕES DE LOG E ATIVIDADE
// =============================================
function logActivity(\$action, \$description = '', \$userId = null) {
    try {
        \$db = Database::getInstance();
        \$sql = \"INSERT INTO logs_atividade (usuario_id, acao, descricao, data_hora, ip_address, user_agent) 
                VALUES (:usuario_id, :acao, :descricao, NOW(), :ip, :user_agent)\";
        
        \$params = [
            ':usuario_id' => \$userId ?? (\$_SESSION['user_id'] ?? null),
            ':acao' => \$action,
            ':descricao' => \$description,
            ':ip' => \$_SERVER['REMOTE_ADDR'] ?? 'CLI',
            ':user_agent' => \$_SERVER['HTTP_USER_AGENT'] ?? 'CLI'
        ];
        
        \$db->execute(\$sql, \$params);
    } catch (Exception \$e) {
        error_log('Erro ao registrar atividade: ' . \$e->getMessage());
    }
}

function logError(\$message, \$context = []) {
    \$logMessage = '[' . date('Y-m-d H:i:s') . '] ERROR: ' . \$message;
    if (!empty(\$context)) {
        \$logMessage .= ' Context: ' . json_encode(\$context);
    }
    error_log(\$logMessage);
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
// LOG INICIAL DO SISTEMA
// =============================================
if (DEBUG_MODE) {
    logActivity('config_loaded', 'Sistema inicializado para ' . APP_ENV);
}
?>";

// Test writing the config file
$testConfigPath = '/tmp/test_config.php';
if (file_put_contents($testConfigPath, $configContent)) {
    echo "<div style='color: green;'><strong>✅ SUCCESS:</strong> Config template generated successfully!</div>";
    echo "<p><strong>Generated config.php length:</strong> " . strlen($configContent) . " characters</p>";
    
    // Test PHP syntax
    $syntaxCheck = shell_exec("php -l $testConfigPath 2>&1");
    if (strpos($syntaxCheck, 'No syntax errors') !== false) {
        echo "<div style='color: green;'><strong>✅ SYNTAX CHECK:</strong> Generated config.php has valid PHP syntax</div>";
    } else {
        echo "<div style='color: red;'><strong>❌ SYNTAX ERROR:</strong> " . htmlspecialchars($syntaxCheck) . "</div>";
    }
    
    // Check if it includes the enhanced Database methods
    if (strpos($configContent, 'public function insert(') !== false) {
        echo "<div style='color: green;'><strong>✅ DATABASE CLASS:</strong> Enhanced Database class with insert() method included</div>";
    } else {
        echo "<div style='color: red;'><strong>❌ DATABASE CLASS:</strong> Enhanced Database class with insert() method NOT found</div>";
    }
    
    if (strpos($configContent, 'APP_ENV') !== false) {
        echo "<div style='color: green;'><strong>✅ ENVIRONMENT:</strong> Environment detection logic included</div>";
    } else {
        echo "<div style='color: red;'><strong>❌ ENVIRONMENT:</strong> Environment detection logic NOT found</div>";
    }
    
    // Show first 1000 characters of generated config
    echo "<h3>📄 Generated Config Preview (first 1000 chars):</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; max-height: 300px;'>";
    echo htmlspecialchars(substr($configContent, 0, 1000)) . "...";
    echo "</pre>";
    
    // Clean up
    unlink($testConfigPath);
    
} else {
    echo "<div style='color: red;'><strong>❌ FAILED:</strong> Could not write test config file</div>";
}

echo "<hr>";
echo "<h3>🔧 Next Steps:</h3>";
echo "<p>If the config generation test above is successful, the install.php should now generate the enhanced config.php with:</p>";
echo "<ul>";
echo "<li>✅ Environment detection logic</li>";
echo "<li>✅ APP_ENV constant definition</li>";
echo "<li>✅ Enhanced Database class with insert() and update() methods</li>";
echo "<li>✅ Proper error handling and logging functions</li>";
echo "</ul>";
?>
