<?php
require_once 'includes/config.php';

echo "<h1>🔍 Debug do Sistema de Cadastro</h1>";
echo "<pre>";

// Verificar se foi submetido um formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "=== DADOS RECEBIDOS ===\n";
    print_r($_POST);
    
    echo "\n=== CONFIGURAÇÕES DE EMAIL ===\n";
    echo "MAIL_HOST: " . (defined('MAIL_HOST') ? MAIL_HOST : "NÃO DEFINIDO") . "\n";
    echo "MAIL_PORT: " . (defined('MAIL_PORT') ? MAIL_PORT : "NÃO DEFINIDO") . "\n";
    echo "MAIL_USERNAME: " . (defined('MAIL_USERNAME') ? MAIL_USERNAME : "NÃO DEFINIDO") . "\n";
    
    echo "\n=== TESTE MAILSERVICE ===\n";
    try {
        $mail = MailService::getInstance();
        echo "✅ MailService instanciado\n";
        
        // Teste de envio
        $testResult = $mail->sendConfirmationEmail(
            'carlospintojunior@gmail.com',
            'Teste Debug Cadastro',
            'debug_token_' . time()
        );
        
        echo "Resultado do teste: " . ($testResult ? "✅ SUCESSO" : "❌ FALHA") . "\n";
        if (!$testResult) {
            echo "Erro: " . $mail->getLastError() . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Erro: " . $e->getMessage() . "\n";
    }
}

echo "\n=== VERIFICAR TABELAS ===\n";
try {
    $db = Database::getInstance();
    
    // Verificar tabela usuarios
    $users = $db->select("SELECT COUNT(*) as total FROM usuarios");
    echo "Total de usuários: " . ($users ? $users[0]['total'] : 'ERRO') . "\n";
    
    // Verificar tabela email_tokens
    $tokens = $db->select("SELECT COUNT(*) as total FROM email_tokens");
    echo "Total de tokens: " . ($tokens ? $tokens[0]['total'] : 'ERRO') . "\n";
    
    // Verificar últimos registros usando estrutura correta do install.php
    $lastUsers = $db->select("SELECT id, nome, email, email_verificado, data_cadastro FROM usuarios ORDER BY id DESC LIMIT 3");
    echo "\nÚltimos usuários:\n";
    if ($lastUsers && is_array($lastUsers)) {
        foreach ($lastUsers as $user) {
            echo "ID: {$user['id']}, Nome: {$user['nome']}, Email: {$user['email']}, Verificado: " . ($user['email_verificado'] ? 'SIM' : 'NÃO') . ", Cadastro: {$user['data_cadastro']}\n";
        }
    } else {
        echo "Nenhum usuário encontrado ou erro na query\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro no banco: " . $e->getMessage() . "\n";
}

echo "\n=== LOGS RECENTES ===\n";
$logFile = __DIR__ . '/logs/php_errors.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lastLogs = array_slice(explode("\n", $logs), -10);
    foreach ($lastLogs as $log) {
        if (!empty(trim($log))) {
            echo trim($log) . "\n";
        }
    }
} else {
    echo "Arquivo de log não encontrado: $logFile\n";
}

echo "</pre>";

// Formulário de teste
?>
<h2>📝 Teste de Cadastro</h2>
<form method="POST" style="background: #f0f0f0; padding: 20px; border-radius: 10px;">
    <div style="margin-bottom: 10px;">
        <label>Nome:</label><br>
        <input type="text" name="nome" value="Teste Debug" style="width: 300px; padding: 5px;">
    </div>
    <div style="margin-bottom: 10px;">
        <label>Email:</label><br>
        <input type="email" name="email" value="teste.debug@gmail.com" style="width: 300px; padding: 5px;">
    </div>
    <div style="margin-bottom: 10px;">
        <label>Senha:</label><br>
        <input type="password" name="password" value="123456" style="width: 300px; padding: 5px;">
    </div>
    <div style="margin-bottom: 10px;">
        <label>Confirmar Senha:</label><br>
        <input type="password" name="confirm_password" value="123456" style="width: 300px; padding: 5px;">
    </div>
    <input type="hidden" name="action" value="register">
    <button type="submit" style="background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px;">
        🧪 Testar Cadastro
    </button>
</form>