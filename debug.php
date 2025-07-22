<?php
// Debug simples para identificar o problema
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug CapivaraLearn</h2>";

// Teste 1: PHP básico
echo "<h3>✅ PHP funcionando</h3>";

// Teste 2: Sessão
session_start();
echo "<h3>✅ Sessão iniciada</h3>";

// Teste 3: Medoo
try {
    require_once 'Medoo.php';
    use Medoo\Medoo;
    echo "<h3>✅ Medoo carregado</h3>";
    
    // Teste 4: Banco de dados
    $database = new Medoo([
        'type' => 'mysql',
        'host' => DB_HOST,
        'database' => DB_NAME,
        'username' => DB_USER,
        'password' => DB_PASS,
        'charset' => 'utf8mb4'
    ]);
    
    $count = $database->count("usuarios");
    echo "<h3>✅ Banco conectado - Usuários: $count</h3>";
} catch (Exception $e) {
    echo "<h3>❌ Erro: " . $e->getMessage() . "</h3>";
}

// Teste 5: Arquivos includes
echo "<h3>Testando includes:</h3>";
echo "<ul>";

$files = [
    'includes/config.php',
    'includes/log_sistema.php',
    'includes/Logger.php',
    'includes/DatabaseConnection.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<li>✅ $file existe</li>";
        try {
            require_once $file;
            echo "<li>✅ $file carregado</li>";
        } catch (Exception $e) {
            echo "<li>❌ $file erro: " . $e->getMessage() . "</li>";
        }
    } else {
        echo "<li>❌ $file NÃO EXISTE</li>";
    }
}
echo "</ul>";

echo "<h3>🎯 Agora testando dashboard...</h3>";
echo "<p><a href='dashboard.php'>Testar Dashboard</a></p>";
?>
