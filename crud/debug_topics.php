<?php
/**
 * DEBUG - CRUD Simplificado de Tópicos
 * Sistema CapivaraLearn
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>Debug Tópicos</title></head><body>";

echo "<h1>Debug CRUD Tópicos</h1>";

// Verificar se sessão está ativa
session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'não definido') . "</p>";

// Se não estiver logado, mostrar link para login
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>⚠️ Usuário não está logado</p>";
    echo "<p><a href='../login.php'>Fazer Login</a></p>";
    echo "</body></html>";
    exit;
}

echo "<p style='color: green;'>✅ Usuário logado com sucesso</p>";

// Tentar carregar Medoo
try {
    require_once __DIR__ . '/includes/medoo_config.php';
    echo "<p style='color: green;'>✅ Medoo carregado com sucesso</p>";
    
    // Testar conexão
    $user_id = $_SESSION['user_id'];
    
    // Buscar disciplinas do usuário
    $disciplinas = $database->select("disciplinas", 
        ["id", "nome"],
        ["usuario_id" => $user_id, "ORDER" => "nome"]
    );
    
    echo "<p>✅ Disciplinas encontradas: " . count($disciplinas) . "</p>";
    
    if (empty($disciplinas)) {
        echo "<p style='color: orange;'>⚠️ Nenhuma disciplina encontrada. <a href='modules_simple.php'>Criar disciplina primeiro</a></p>";
    } else {
        echo "<ul>";
        foreach ($disciplinas as $disciplina) {
            echo "<li>" . htmlspecialchars($disciplina['nome']) . " (ID: " . $disciplina['id'] . ")</li>";
        }
        echo "</ul>";
    }
    
    // Buscar tópicos do usuário
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
    
    if (!empty($topicos)) {
        echo "<ul>";
        foreach ($topicos as $topico) {
            echo "<li>" . htmlspecialchars($topico['nome']) . " (Disciplina: " . htmlspecialchars($topico['disciplina_nome']) . ")</li>";
        }
        echo "</ul>";
    }
    
    echo "<p><a href='topics_simple.php'>🔗 Ir para CRUD Completo de Tópicos</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<p><a href='../dashboard.php'>🏠 Voltar ao Dashboard</a></p>";
echo "</body></html>";
?>
