<?php
// Debug específico para login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/home/carlos/Documents/GitHub/CapivaraLearn/logs/debug_login.log');

echo "=== DEBUG LOGIN.PHP ===\n";
echo "Iniciando debug do login.php...\n";

// Teste 1: Verificar se o PHP está funcionando
echo "1. PHP está funcionando: OK\n";

// Teste 2: Verificar se podemos incluir arquivos
echo "2. Testando includes...\n";
try {
    if (file_exists('includes/config.php')) {
        echo "   - config.php existe: OK\n";
        include_once 'includes/config.php';
        echo "   - config.php incluído: OK\n";
    } else {
        echo "   - config.php NÃO EXISTE: ERRO\n";
    }
} catch (Exception $e) {
    echo "   - Erro ao incluir config.php: " . $e->getMessage() . "\n";
}

try {
    if (file_exists('Medoo.php')) {
        echo "   - Medoo.php existe: OK\n";
        include_once 'Medoo.php';
        echo "   - Medoo.php incluído: OK\n";
    } else {
        echo "   - Medoo.php NÃO EXISTE: ERRO\n";
    }
} catch (Exception $e) {
    echo "   - Erro ao incluir Medoo.php: " . $e->getMessage() . "\n";
}

try {
    if (file_exists('includes/header.php')) {
        echo "   - header.php existe: OK\n";
        // Não incluir header.php ainda para não gerar HTML
    } else {
        echo "   - header.php NÃO EXISTE: ERRO\n";
    }
} catch (Exception $e) {
    echo "   - Erro com header.php: " . $e->getMessage() . "\n";
}

// Teste 3: Verificar conexão com banco
echo "3. Testando conexão com banco...\n";
try {
    if (isset($database)) {
        echo "   - Variável \$database existe: OK\n";
        $test = $database->query("SELECT 1 as test")->fetchAll();
        echo "   - Conexão com banco: OK\n";
    } else {
        echo "   - Variável \$database NÃO EXISTE: ERRO\n";
    }
} catch (Exception $e) {
    echo "   - Erro na conexão com banco: " . $e->getMessage() . "\n";
}

// Teste 4: Verificar se podemos iniciar sessão
echo "4. Testando sessão...\n";
try {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
        echo "   - Sessão iniciada: OK\n";
    } else {
        echo "   - Sessão já estava ativa: OK\n";
    }
} catch (Exception $e) {
    echo "   - Erro ao iniciar sessão: " . $e->getMessage() . "\n";
}

// Teste 5: Simular processamento do login
echo "5. Testando processamento do login...\n";
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "   - POST detectado: OK\n";
        if (isset($_POST['username'])) {
            echo "   - Username recebido: " . htmlspecialchars($_POST['username']) . "\n";
        }
        if (isset($_POST['password'])) {
            echo "   - Password recebido: [HIDDEN]\n";
        }
    } else {
        echo "   - Método: " . $_SERVER['REQUEST_METHOD'] . "\n";
    }
} catch (Exception $e) {
    echo "   - Erro no processamento: " . $e->getMessage() . "\n";
}

// Teste 6: Verificar estrutura da tabela usuarios
echo "6. Testando estrutura da tabela usuarios...\n";
try {
    if (isset($database)) {
        $usuarios = $database->query("DESCRIBE usuarios")->fetchAll();
        echo "   - Colunas da tabela usuarios:\n";
        foreach ($usuarios as $coluna) {
            echo "     * " . $coluna['Field'] . " (" . $coluna['Type'] . ")\n";
        }
    }
} catch (Exception $e) {
    echo "   - Erro ao verificar tabela usuarios: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO DEBUG ===\n";
echo "Se chegou até aqui, o problema pode estar na lógica específica do login.php\n";
echo "Verifique o arquivo de log: logs/debug_login.log\n";
?>
    echo "<p>✅ Sessão OK</p>";
    
    echo "<p>2. Carregando Medoo...</p>";
    require_once 'Medoo.php';
    use Medoo\Medoo;
    echo "<p>✅ Medoo OK</p>";
    
    echo "<p>3. Testando conexão com banco...</p>";
    $database = new Medoo([
        'type' => 'mysql',
        'host' => 'localhost',
        'database' => 'capivaralearn',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ]);
    echo "<p>✅ Banco OK</p>";
    
    echo "<p>4. Carregando arquivos include...</p>";
    
    if (file_exists('includes/config.php')) {
        require_once 'includes/config.php';
        echo "<p>✅ config.php carregado</p>";
    }
    
    if (file_exists('includes/log_sistema.php')) {
        require_once 'includes/log_sistema.php';
        echo "<p>✅ log_sistema.php carregado</p>";
    }
    
    echo "<p>5. Simulando verificação de usuário...</p>";
    $users = $database->select("usuarios", ["id", "nome", "email"], ["LIMIT" => 1]);
    echo "<p>✅ Consulta usuários OK - Total: " . count($users) . "</p>";
    
    echo "<h3>🎉 Todos os componentes funcionando!</h3>";
    echo "<p><strong>O problema deve estar em alguma linha específica do login.php</strong></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ ERRO ENCONTRADO:</h3>";
    echo "<pre style='background: #fee; padding: 10px; border: 1px solid red;'>";
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
?>
