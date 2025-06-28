<?php
// Teste básico do Medoo
echo "<h1>Teste Medoo</h1>";

echo "<p>1. ✅ PHP funcionando</p>";

// Teste 2: Carregar Medoo
echo "<p>2. Testando carregamento do Medoo...</p>";
try {
    require_once __DIR__ . '/../Medoo.php';
    echo "<p>✅ Medoo.php carregado</p>";
} catch (Exception $e) {
    echo "<p>❌ Erro ao carregar Medoo: " . $e->getMessage() . "</p>";
    exit;
}

// Teste 3: Verificar classe
echo "<p>3. Verificando classe Medoo...</p>";
if (class_exists('Medoo\\Medoo')) {
    echo "<p>✅ Classe Medoo\\Medoo existe</p>";
} elseif (class_exists('Medoo')) {
    echo "<p>✅ Classe Medoo existe (sem namespace)</p>";
} else {
    echo "<p>❌ Classe Medoo não encontrada</p>";
    exit;
}

// Teste 4: Tentar criar instância
echo "<p>4. Criando instância do Medoo...</p>";
try {
    $config = [
        'type' => 'mysql',
        'host' => 'localhost',
        'database' => 'capivaralearn',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ];
    
    // Tentar com namespace
    if (class_exists('Medoo\\Medoo')) {
        $database = new Medoo\Medoo($config);
        echo "<p>✅ Instância criada com namespace</p>";
    } else {
        $database = new Medoo($config);
        echo "<p>✅ Instância criada sem namespace</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro ao criar instância: " . $e->getMessage() . "</p>";
    exit;
}

// Teste 5: Testar query simples
echo "<p>5. Testando query simples...</p>";
try {
    $result = $database->query("SELECT 1 as test")->fetchAll();
    echo "<p>✅ Query executada: " . json_encode($result) . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Erro na query: " . $e->getMessage() . "</p>";
}

echo "<p><strong>Todos os testes concluídos!</strong></p>";
?>
