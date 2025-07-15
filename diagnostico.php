<?php
// ===== DIAGNÓSTICO DO SISTEMA =====
echo "<h1>🔍 Diagnóstico CapivaraLearn</h1>";

// Verificar versão do PHP
echo "<h2>PHP</h2>";
echo "Versão: " . PHP_VERSION . "<br>";
echo "SAPI: " . php_sapi_name() . "<br>";

// Verificar configurações
echo "<h2>Configurações</h2>";
echo "display_errors: " . ini_get('display_errors') . "<br>";
echo "error_reporting: " . ini_get('error_reporting') . "<br>";
echo "log_errors: " . ini_get('log_errors') . "<br>";
echo "error_log: " . ini_get('error_log') . "<br>";

// Verificar arquivos
echo "<h2>Arquivos</h2>";
$arquivos = [
    'includes/config.php',
    'includes/log_sistema.php',
    'includes/services/UniversityService.php',
    'manage_universities.php',
    'manage_courses.php'
];

foreach ($arquivos as $arquivo) {
    if (file_exists($arquivo)) {
        echo "✅ $arquivo - " . filesize($arquivo) . " bytes<br>";
    } else {
        echo "❌ $arquivo - NÃO ENCONTRADO<br>";
    }
}

// Testar inclusão do config
echo "<h2>Teste de Inclusão</h2>";
try {
    require_once 'includes/config.php';
    echo "✅ config.php carregado com sucesso<br>";
} catch (Exception $e) {
    echo "❌ ERRO ao carregar config.php: " . $e->getMessage() . "<br>";
}

// Verificar sessão
echo "<h2>Sessão</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Status: " . session_status() . "<br>";
echo "ID: " . session_id() . "<br>";
echo "Logado: " . (isset($_SESSION['user_id']) ? 'SIM' : 'NÃO') . "<br>";

// Verificar banco de dados
echo "<h2>Banco de Dados</h2>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=capivaralearn", "root", "");
    echo "✅ Conexão com banco OK<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch();
    echo "Usuários cadastrados: " . $result['total'] . "<br>";
} catch (Exception $e) {
    echo "❌ ERRO no banco: " . $e->getMessage() . "<br>";
}

echo "<h2>Logs de Erro</h2>";
$logFile = 'logs/sistema.log';
if (file_exists($logFile)) {
    echo "Últimas 10 linhas do log:<br><pre>";
    $lines = file($logFile);
    echo implode('', array_slice($lines, -10));
    echo "</pre>";
} else {
    echo "Arquivo de log não encontrado<br>";
}
?>
