<?php
require_once 'includes/config.php';

// Incluir o MailService se não estiver incluído no config
if (!class_exists('MailService')) {
    require_once 'includes/MailService.php';
}

echo "<h1>🧪 Teste Simplificado de Cadastro</h1>";
echo "<pre>";

// Verificar se foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    echo "=== DADOS RECEBIDOS ===\n";
    echo "Nome: $nome\n";
    echo "Email: $email\n";
    echo "Senha: " . str_repeat('*', strlen($password)) . "\n";
    
    echo "\n=== VERIFICAÇÕES ===\n";
    
    // 1. Verificar classes
    echo "Database class existe: " . (class_exists('Database') ? "✅" : "❌") . "\n";
    echo "MailService class existe: " . (class_exists('MailService') ? "✅" : "❌") . "\n";
    
    // 2. Verificar função generateToken
    echo "generateToken function existe: " . (function_exists('generateToken') ? "✅" : "❌") . "\n";
    
    try {
        // 3. Testar conexão de banco
        $db = Database::getInstance();
        echo "Conexão com banco: ✅\n";
        
        // 4. Verificar se email já existe
        $existing = $db->select("SELECT id, email_verificado FROM usuarios WHERE email = ?", [$email]);
        echo "Email já existe: " . ($existing ? "SIM" : "NÃO") . "\n";
        
        if (!$existing) {
            echo "\n=== CRIANDO USUÁRIO ===\n";
            
            // 5. Criar usuário
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            echo "Senha hasheada: ✅\n";
            
            $stmt = $db->getConnection()->prepare(
                "INSERT INTO usuarios (nome, email, senha, curso, instituicao, email_verificado) VALUES (?, ?, ?, 'Fisioterapia', NULL, FALSE)"
            );
            
            if ($stmt->execute([$nome, $email, $hashedPassword])) {
                $userId = $db->getConnection()->lastInsertId();
                echo "Usuário criado com ID: $userId ✅\n";
                
                // 6. Criar token
                if (function_exists('generateToken')) {
                    $token = generateToken();
                    echo "Token gerado: " . substr($token, 0, 10) . "... ✅\n";
                } else {
                    $token = bin2hex(random_bytes(32));
                    echo "Token gerado (fallback): " . substr($token, 0, 10) . "... ✅\n";
                }
                
                $expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // 7. Inserir token
                $tokenInserted = $db->execute(
                    "INSERT INTO email_tokens (usuario_id, token, tipo, data_expiracao, ip_address) VALUES (?, ?, 'confirmacao', ?, ?)",
                    [$userId, $token, $expiration, $_SERVER['REMOTE_ADDR'] ?? null]
                );
                
                echo "Token inserido no banco: " . ($tokenInserted ? "✅" : "❌") . "\n";
                
                // 8. Testar MailService
                echo "\n=== TESTANDO EMAIL ===\n";
                $mail = MailService::getInstance();
                echo "MailService instanciado: ✅\n";
                
                // Mostrar config do MailService
                if (method_exists($mail, 'getConfig')) {
                    $config = $mail->getConfig();
                    echo "Config MailService:\n";
                    foreach ($config as $key => $value) {
                        if ($key === 'user' && !empty($value)) {
                            $value = substr($value, 0, 3) . '***@' . substr($value, strpos($value, '@'));
                        }
                        echo "  $key: $value\n";
                    }
                }
                
                // Tentar enviar email
                echo "\nTentando enviar email...\n";
                $emailSent = $mail->sendConfirmationEmail($email, $nome, $token);
                
                if ($emailSent) {
                    echo "✅ EMAIL ENVIADO COM SUCESSO!\n";
                    echo "🎉 CADASTRO COMPLETADO!\n";
                } else {
                    echo "❌ Falha no envio do email\n";
                    echo "Erro: " . $mail->getLastError() . "\n";
                    echo "⚠️ Usuário criado mas email não enviado\n";
                }
                
            } else {
                echo "❌ Erro ao criar usuário no banco\n";
            }
        } else {
            echo "⚠️ Email já existe, não criando usuário\n";
        }
        
    } catch (Exception $e) {
        echo "❌ ERRO: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
}

echo "</pre>";

// Verificar logs recentes
echo "<h3>📋 Logs Recentes</h3>";
echo "<pre>";
$logFile = __DIR__ . '/logs/php_errors.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lastLogs = array_slice(explode("\n", $logs), -15);
    foreach ($lastLogs as $log) {
        if (!empty(trim($log))) {
            echo trim($log) . "\n";
        }
    }
} else {
    echo "Arquivo de log não encontrado: $logFile\n";
}
echo "</pre>";
?>

<h2>📝 Formulário de Teste</h2>
<form method="POST" style="background: #f0f0f0; padding: 20px; border-radius: 10px; max-width: 400px;">
    <div style="margin-bottom: 10px;">
        <label>Nome:</label><br>
        <input type="text" name="nome" value="Teste Simples" required style="width: 100%; padding: 8px;">
    </div>
    <div style="margin-bottom: 10px;">
        <label>Email:</label><br>
        <input type="email" name="email" value="teste.simples<?= time() ?>@gmail.com" required style="width: 100%; padding: 8px;">
    </div>
    <div style="margin-bottom: 10px;">
        <label>Senha:</label><br>
        <input type="password" name="password" value="123456" required style="width: 100%; padding: 8px;">
    </div>
    <button type="submit" style="background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; width: 100%;">
        🧪 Testar Cadastro
    </button>
</form>

<div style="margin-top: 20px; padding: 15px; background: #e8f4f8; border-radius: 8px;">
    <h4>ℹ️ O que este teste faz:</h4>
    <ul>
        <li>✅ Verifica se todas as classes existem</li>
        <li>✅ Testa conexão com banco</li>
        <li>✅ Cria usuário na estrutura correta</li>
        <li>✅ Gera e salva token</li>
        <li>✅ Testa envio de email</li>
        <li>✅ Mostra logs detalhados</li>
    </ul>
</div>