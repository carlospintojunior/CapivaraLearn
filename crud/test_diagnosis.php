<?php
// Teste básico para diagnóstico
echo "<h1>Teste de Diagnóstico</h1>";

echo "<h2>1. PHP funcionando</h2>";
echo "✅ PHP está executando<br>";

echo "<h2>2. Testando includes</h2>";
try {
    require_once __DIR__ . '/../includes/config.php';
    echo "✅ Config.php carregado<br>";
} catch (Exception $e) {
    echo "❌ Erro ao carregar config.php: " . $e->getMessage() . "<br>";
}

echo "<h2>3. Testando conexão com banco</h2>";
try {
    if (isset($pdo)) {
        echo "✅ PDO está disponível<br>";
        $stmt = $pdo->query("SELECT 1");
        echo "✅ Conexão com banco funcionando<br>";
    } else {
        echo "❌ PDO não está disponível<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Testando sessão</h2>";
try {
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "✅ Sessão ativa<br>";
    } else {
        echo "❌ Sessão não está ativa<br>";
    }
    
    if (isset($_SESSION['user_id'])) {
        echo "✅ Usuário logado (ID: " . $_SESSION['user_id'] . ")<br>";
    } else {
        echo "❌ Usuário não está logado<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro na sessão: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Testando tabela universidades</h2>";
try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SHOW TABLES LIKE 'universidades'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabela universidades existe<br>";
            
            $stmt = $pdo->query("DESCRIBE universidades");
            echo "✅ Estrutura da tabela:<br>";
            while ($row = $stmt->fetch()) {
                echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
            }
        } else {
            echo "❌ Tabela universidades não existe<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar tabela: " . $e->getMessage() . "<br>";
}

phpinfo();
?>
