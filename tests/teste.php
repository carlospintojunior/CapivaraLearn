<?php
// Teste de funcionamento
require_once __DIR__ . '/../includes/config.php';

try {
    $db = Database::getInstance();
    
    echo "<h2>🦫 Teste CapivaraLearn</h2>";
    
    // Testar tabelas
    $tables = $db->select("SHOW TABLES");
    echo "<p>✅ <strong>Tabelas criadas:</strong> " . count($tables) . "</p>";
    
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "<li>📋 $tableName</li>";
    }
    
    // Testar dados de exemplo
    $user = $db->select("SELECT * FROM usuarios WHERE email = 'teste@capivaralearn.com'");
    if ($user) {
        echo "<p>✅ <strong>Usuário de teste:</strong> " . $user[0]['nome'] . "</p>";
    }
    
    $modulos = $db->select("SELECT * FROM modulos");
    echo "<p>✅ <strong>Módulos criados:</strong> " . count($modulos) . "</p>";
    
    $topicos = $db->select("SELECT * FROM topicos");
    echo "<p>✅ <strong>Tópicos criados:</strong> " . count($topicos) . "</p>";
    
    echo "<h3>🎉 Instalação funcionando corretamente!</h3>";
    echo "<p><strong>Próximo passo:</strong> Criar páginas de login e dashboard</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}
?>