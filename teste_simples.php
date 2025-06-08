<?php
echo "🦫 PHP funcionando!<br>";

// Testar conexão MySQL
try {
    $pdo = new PDO("mysql:host=localhost;dbname=capivaralearn", "root", "");
    echo "✅ Conexão MySQL OK!<br>";
    
    $result = $pdo->query("SHOW TABLES");
    $tables = $result->fetchAll();
    echo "✅ Tabelas encontradas: " . count($tables) . "<br>";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>