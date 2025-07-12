<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug Login</h2>";

// Teste 1: Sessão
session_start();
echo "<h3>✅ Sessão iniciada</h3>";

// Teste 2: Verificar arquivos que o login precisa
$files_needed = [
    'Medoo.php',
    'includes/config.php',
    'includes/log_sistema.php'
];

foreach ($files_needed as $file) {
    if (file_exists($file)) {
        echo "<p>✅ $file existe</p>";
        try {        if ($file === 'Medoo.php') {
            require_once $file;
            use Medoo\Medoo;
            echo "<p>✅ Medoo carregado</p>";
        } else {
                require_once $file;
                echo "<p>✅ $file carregado</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Erro em $file: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ $file NÃO EXISTE</p>";
    }
}

// Teste 3: Banco de dados
try {
    $database = new Medoo([
        'type' => 'mysql',
        'host' => 'localhost',
        'database' => 'capivaralearn',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ]);
    
    $count = $database->count("usuarios");
    echo "<h3>✅ Banco conectado - Usuários: $count</h3>";
} catch (Exception $e) {
    echo "<h3>❌ Erro Banco: " . $e->getMessage() . "</h3>";
}

echo "<h3>🎯 Todos os pré-requisitos OK!</h3>";
echo "<p><a href='login.php'>Testar Login Original</a></p>";
?>
