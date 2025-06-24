<?php
// Teste extremamente básico
echo "<h1>Teste Simples</h1>";

// Teste 1: PHP básico
echo "<p>✅ PHP funcionando</p>";

// Teste 2: Carregar config sem chamar requireLogin
echo "<p>Testando carregamento do config...</p>";
try {
    require_once __DIR__ . '/../includes/config.php';
    echo "<p>✅ Config carregado</p>";
} catch (Exception $e) {
    echo "<p>❌ Erro no config: " . $e->getMessage() . "</p>";
    exit;
}

// Teste 3: Testar Database
echo "<p>Testando Database...</p>";
try {
    $db = Database::getInstance();
    echo "<p>✅ Database getInstance OK</p>";
    
    $pdo = $db->getConnection();
    echo "<p>✅ PDO connection OK</p>";
    
    // Teste simples de query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "<p>✅ Query teste OK: " . $result['test'] . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro Database: " . $e->getMessage() . "</p>";
}

// Teste 4: Verificar se sessão funciona
echo "<p>Testando sessão...</p>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p>✅ Sessão iniciada</p>";
    
    if (isset($_SESSION['user_id'])) {
        echo "<p>✅ Usuário logado: " . $_SESSION['user_id'] . "</p>";
    } else {
        echo "<p>⚠️ Usuário não está logado</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Erro sessão: " . $e->getMessage() . "</p>";
}

// Teste 5: Verificar se requireLogin existe
echo "<p>Testando requireLogin...</p>";
try {
    if (function_exists('requireLogin')) {
        echo "<p>✅ Função requireLogin existe</p>";
        // NÃO vamos chamar requireLogin para evitar redirect
    } else {
        echo "<p>❌ Função requireLogin não existe</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Erro verificando requireLogin: " . $e->getMessage() . "</p>";
}

echo "<p><strong>Fim dos testes</strong></p>";
?>
