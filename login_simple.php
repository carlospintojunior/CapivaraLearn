<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log');

require_once 'Medoo.php';
use Medoo\Medoo;

echo "<h2>🔍 Login Simplificado</h2>";

// Iniciar sessão
session_start();

// Incluir dependências uma por uma
try {
    echo "<p>✅ Medoo carregado</p>";
    
    // Conexão com banco
    $database = new Medoo([
        'type' => 'mysql',
        'host' => DB_HOST,
        'database' => DB_NAME,
        'username' => DB_USER,
        'password' => DB_PASS,
        'charset' => 'utf8mb4'
    ]);
    echo "<p>✅ Banco conectado</p>";
    
    // Verificar se há POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "<p>🔄 Processando login...</p>";
        
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        
        if (empty($email) || empty($senha)) {
            echo "<p>❌ Email e senha são obrigatórios</p>";
        } else {
            echo "<p>📧 Email: " . htmlspecialchars($email) . "</p>";
            
            // Buscar usuário
            $user = $database->get("usuarios", "*", ["email" => $email]);
            
            if ($user) {
                echo "<p>✅ Usuário encontrado: " . htmlspecialchars($user['nome']) . "</p>";
                
                if (password_verify($senha, $user['senha'])) {
                    echo "<p>✅ Senha correta!</p>";
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nome'];
                    echo "<p>🎯 Redirecionando para dashboard...</p>";
                    echo "<script>setTimeout(() => { window.location.href = 'dashboard.php'; }, 2000);</script>";
                } else {
                    echo "<p>❌ Senha incorreta</p>";
                }
            } else {
                echo "<p>❌ Usuário não encontrado</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ ERRO: " . $e->getMessage() . "</p>";
    echo "<p>🔍 Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Simplificado - CapivaraLearn</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        input { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h2>Login CapivaraLearn</h2>
    <form method="POST">
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Senha:</label>
            <input type="password" name="senha" required>
        </div>
        <button type="submit">Entrar</button>
    </form>
    
    <hr>
    <p><a href="test_login_fix.php">🔧 Debug Components</a></p>
    <p><a href="login.php">🔄 Login Original</a></p>
</body>
</html>
