<?php
echo "Verificando tabela unidades_aprendizagem...<br>";

try {
    require_once __DIR__ . '/Medoo.php';
    
    $database = new Medoo\Medoo([
        'type' => 'mysql',
        'host' => 'localhost',
        'database' => 'capivaralearn',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ]);

    // Verificar se a tabela existe
    $tables = $database->query("SHOW TABLES LIKE 'unidades_aprendizagem'")->fetchAll();
    
    if (empty($tables)) {
        echo "❌ Tabela 'unidades_aprendizagem' NÃO EXISTE<br>";
        echo "📝 Execute: <a href='create_learning_units_table.php'>Criar Tabela</a><br>";
    } else {
        echo "✅ Tabela 'unidades_aprendizagem' existe<br>";
        
        // Verificar registros
        $count = $database->count("unidades_aprendizagem");
        echo "📊 Registros: $count<br>";
        
        // Verificar estrutura
        $structure = $database->query("DESCRIBE unidades_aprendizagem")->fetchAll();
        echo "<h3>Estrutura da tabela:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($structure as $field) {
            echo "<tr>";
            echo "<td>{$field['Field']}</td>";
            echo "<td>{$field['Type']}</td>";
            echo "<td>{$field['Null']}</td>";
            echo "<td>{$field['Key']}</td>";
            echo "<td>{$field['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
