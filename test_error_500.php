<?php
// Teste específico para o erro 500 no login
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTE ESPECÍFICO DO ERRO 500 NO LOGIN ===\n";

// Teste 1: Incluir arquivos básicos
try {
    echo "1. Incluindo config.php...\n";
    include_once 'includes/config.php';
    echo "   ✓ Config incluído\n";
} catch (Exception $e) {
    echo "   ✗ Erro no config: " . $e->getMessage() . "\n";
    die("Parando por causa do config");
}

// Teste 2: Verificar se as constantes estão definidas
echo "2. Verificando constantes de banco...\n";
$constantes = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($constantes as $const) {
    if (defined($const)) {
        echo "   ✓ $const definida\n";
    } else {
        echo "   ✗ $const NÃO definida\n";
    }
}

// Teste 3: Incluir DatabaseConnection
try {
    echo "3. Incluindo DatabaseConnection...\n";
    include_once 'includes/DatabaseConnection.php';
    echo "   ✓ DatabaseConnection incluído\n";
} catch (Exception $e) {
    echo "   ✗ Erro no DatabaseConnection: " . $e->getMessage() . "\n";
    die("Parando por causa do DatabaseConnection");
}

// Teste 4: Verificar se a classe Database existe
echo "4. Verificando classe Database...\n";
if (class_exists('Database')) {
    echo "   ✓ Classe Database existe\n";
} else {
    echo "   ✗ Classe Database NÃO existe\n";
    // Verificar se CapivaraLearn\DatabaseConnection existe
    if (class_exists('CapivaraLearn\\DatabaseConnection')) {
        echo "   ✓ Classe CapivaraLearn\\DatabaseConnection existe\n";
        echo "   → Criando alias...\n";
        class_alias('CapivaraLearn\\DatabaseConnection', 'Database');
        echo "   ✓ Alias criado\n";
    } else {
        echo "   ✗ Classe CapivaraLearn\\DatabaseConnection NÃO existe\n";
        die("Parando - nenhuma classe de banco encontrada");
    }
}

// Teste 5: Testar getInstance()
try {
    echo "5. Testando Database::getInstance()...\n";
    $db = Database::getInstance();
    echo "   ✓ getInstance() funcionou\n";
} catch (Exception $e) {
    echo "   ✗ Erro no getInstance(): " . $e->getMessage() . "\n";
    die("Parando por causa do getInstance");
}

// Teste 6: Testar conexão com banco
try {
    echo "6. Testando conexão com banco...\n";
    $result = $db->select("SELECT 1 as test");
    echo "   ✓ Conexão com banco funcionou\n";
} catch (Exception $e) {
    echo "   ✗ Erro na conexão com banco: " . $e->getMessage() . "\n";
    die("Parando por causa da conexão com banco");
}

// Teste 7: Testar tabela usuarios
try {
    echo "7. Testando tabela usuarios...\n";
    $result = $db->select("SELECT COUNT(*) as total FROM usuarios");
    echo "   ✓ Tabela usuarios acessível - Total: " . $result[0]['total'] . "\n";
} catch (Exception $e) {
    echo "   ✗ Erro na tabela usuarios: " . $e->getMessage() . "\n";
}

// Teste 8: Simular o que o login.php faz
echo "8. Simulando processo do login.php...\n";
try {
    // Simular início de sessão
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "   ✓ Sessão iniciada\n";
    }
    
    // Simular include do log_sistema
    include_once 'includes/log_sistema.php';
    echo "   ✓ log_sistema incluído\n";
    
    // Simular log
    log_sistema('Teste de log do debug', 'DEBUG');
    echo "   ✓ log_sistema funcionando\n";
    
    // Simular include do MailService
    include_once 'includes/MailService.php';
    echo "   ✓ MailService incluído\n";
    
    echo "   ✓ Simulação do login.php completa\n";
    
} catch (Exception $e) {
    echo "   ✗ Erro na simulação: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
echo "Se chegou até aqui, o problema pode estar em outra parte do login.php\n";
?>
