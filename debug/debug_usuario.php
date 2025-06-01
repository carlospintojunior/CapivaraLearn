<?php
require_once __DIR__ . '/../includes/config.php';

$db = Database::getInstance();

echo "<h2>🔍 Debug do Usuário de Teste</h2>";

// Verificar se usuário existe
$user = $db->select("SELECT * FROM usuarios WHERE email = 'teste@capivaralearn.com'");

if ($user) {
    echo "<p>✅ <strong>Usuário encontrado!</strong></p>";
    echo "<pre>";
    print_r($user[0]);
    echo "</pre>";
    
    // Testar senha
    $storedHash = $user[0]['senha'];
    $testPassword = '123456';
    
    echo "<p><strong>Hash armazenado:</strong> " . $storedHash . "</p>";
    echo "<p><strong>Teste de verificação:</strong> ";
    
    if (password_verify($testPassword, $storedHash)) {
        echo "✅ Senha CORRETA</p>";
    } else {
        echo "❌ Senha INCORRETA</p>";
        
        // Gerar novo hash correto
        $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
        echo "<p><strong>Novo hash correto:</strong> " . $newHash . "</p>";
        
        // Atualizar senha no banco
        $updated = $db->select("UPDATE usuarios SET senha = ? WHERE email = 'teste@capivaralearn.com'", [$newHash]);
        
        if ($updated !== false) {
            echo "<p>✅ <strong>Senha corrigida no banco!</strong></p>";
            echo "<p>🚀 <strong>Agora tente fazer login novamente!</strong></p>";
        } else {
            echo "<p>❌ Erro ao atualizar senha</p>";
        }
    }
    
} else {
    echo "<p>❌ <strong>Usuário NÃO encontrado!</strong></p>";
    echo "<p>Vamos criar o usuário:</p>";
    
    // Criar usuário corretamente
    $nome = 'Estudante Teste';
    $email = 'teste@capivaralearn.com';
    $senha = password_hash('123456', PASSWORD_DEFAULT);
    
    $created = $db->select(
        "INSERT INTO usuarios (nome, email, senha, curso, instituicao) VALUES (?, ?, ?, ?, ?)",
        [$nome, $email, $senha, 'Fisioterapia', 'Universidade Exemplo']
    );
    
    if ($created !== false) {
        echo "<p>✅ <strong>Usuário criado com sucesso!</strong></p>";
        echo "<p>🚀 <strong>Agora tente fazer login!</strong></p>";
    } else {
        echo "<p>❌ Erro ao criar usuário</p>";
    }
}
?>