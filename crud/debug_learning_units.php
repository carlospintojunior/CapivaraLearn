<?php
echo "Debug - Unidades de Aprendizagem<br>";

// Verificar se sessão está ativa
session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'não definido') . "</p>";

// Se não estiver logado, mostrar link para login
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>⚠️ Usuário não está logado</p>";
    echo "<p><a href='../login.php'>Fazer Login</a></p>";
    exit;
}

echo "<p style='color: green;'>✅ Usuário logado com sucesso</p>";

// Tentar carregar Medoo
try {
    require_once __DIR__ . '/../Medoo.php';
    echo "<p style='color: green;'>✅ Medoo carregado com sucesso</p>";
    
    $database = new Medoo\Medoo([
        'type' => 'mysql',
        'host' => 'localhost',
        'database' => 'capivaralearn',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ]);
    
    echo "<p style='color: green;'>✅ Conexão com banco estabelecida</p>";
    
    // Verificar se tabela existe
    $tables = $database->query("SHOW TABLES LIKE 'unidades_aprendizagem'")->fetchAll();
    
    if (empty($tables)) {
        echo "<p style='color: red;'>❌ Tabela 'unidades_aprendizagem' não existe</p>";
        echo "<p><a href='../create_learning_units_table.php'>Criar Tabela</a></p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Tabela 'unidades_aprendizagem' existe</p>";
    
    // Testar query de tópicos
    $user_id = $_SESSION['user_id'];
    $topicos = $database->select("topicos", [
        "[>]disciplinas" => ["disciplina_id" => "id"]
    ], [
        "topicos.id",
        "topicos.nome",
        "disciplinas.nome(disciplina_nome)"
    ], [
        "topicos.usuario_id" => $user_id
    ]);
    
    echo "<p>✅ Tópicos encontrados: " . count($topicos) . "</p>";
    
    if (empty($topicos)) {
        echo "<p style='color: orange;'>⚠️ Nenhum tópico encontrado. <a href='topics_simple.php'>Criar tópico primeiro</a></p>";
    } else {
        echo "<ul>";
        foreach ($topicos as $topico) {
            echo "<li>" . htmlspecialchars($topico['disciplina_nome'] . ' > ' . $topico['nome']) . " (ID: " . $topico['id'] . ")</li>";
        }
        echo "</ul>";
    }
    
    // Testar query de unidades
    $unidades = $database->select("unidades_aprendizagem", [
        "[>]topicos" => ["topico_id" => "id"],
        "[>]disciplinas" => ["topicos.disciplina_id" => "disciplinas.id"]
    ], [
        "unidades_aprendizagem.id",
        "unidades_aprendizagem.nome",
        "topicos.nome(topico_nome)",
        "disciplinas.nome(disciplina_nome)",
        "unidades_aprendizagem.nota"
    ], [
        "unidades_aprendizagem.usuario_id" => $user_id
    ]);
    
    echo "<p>✅ Unidades encontradas: " . count($unidades) . "</p>";
    
    if (!empty($unidades)) {
        echo "<ul>";
        foreach ($unidades as $unidade) {
            echo "<li>" . htmlspecialchars($unidade['nome']) . " - Nota: " . $unidade['nota'] . " (Disciplina: " . htmlspecialchars($unidade['disciplina_nome']) . " > " . htmlspecialchars($unidade['topico_nome']) . ")</li>";
        }
        echo "</ul>";
    }
    
    echo "<p><a href='learning_units_simple.php'>🔗 Ir para CRUD Completo</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<p><a href='../dashboard.php'>🏠 Voltar ao Dashboard</a></p>";
?>
