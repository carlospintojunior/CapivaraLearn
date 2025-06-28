<?php
/**
 * Configuração simplificada com Medoo
 */

// Iniciar sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir Medoo
require_once __DIR__ . '/../Medoo.php';

use Medoo\Medoo;

// Configurações do banco
$database_config = [
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'capivaralearn',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_general_ci',
    'port' => 3306,
    'option' => [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
];

// Inicializar Medoo
try {
    $database = new Medoo($database_config);
    
    // Testar conexão
    $test = $database->query("SELECT 1")->fetchAll();
    
} catch (Exception $e) {
    die("Erro de conexão com banco: " . $e->getMessage());
}

// Função para verificar login
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /CapivaraLearn/login.php');
        exit;
    }
}

// Função para sanitizar dados
function sanitize($data) {
    return htmlspecialchars(trim($data));
}

// Função para log de atividades
function log_activity($action, $details = '') {
    global $database;
    
    try {
        $database->insert('logs', [
            'usuario_id' => $_SESSION['user_id'] ?? null,
            'acao' => $action,
            'detalhes' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        // Log silencioso em caso de erro
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}
?>
