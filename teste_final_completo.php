<?php
// Simular exatamente o que acontece no sistema real
echo "=== TESTE COMPLETO DO SISTEMA ===\n";

// 1. Testar se conseguimos incluir arquivos
try {
    require_once 'includes/config.php';
    echo "✅ config.php carregado\n";
} catch (Exception $e) {
    echo "❌ Erro ao carregar config.php: " . $e->getMessage() . "\n";
    exit;
}

// 2. Testar conexão com banco
try {
    $db = Database::getInstance();
    echo "✅ Database conectado\n";
} catch (Exception $e) {
    echo "❌ Erro de banco: " . $e->getMessage() . "\n";
}

// 3. Testar MailService
try {
    $mail = MailService::getInstance();
    echo "✅ MailService instanciado\n";
    
    // Simular o envio de email como no sistema real
    echo "Testando envio de email...\n";
    $result = $mail->sendConfirmationEmail('teste@exemplo.com', 'Teste User', 'abc123token');
    
    if ($result) {
        echo "✅ Email enviado com sucesso!\n";
    } else {
        echo "❌ Falha no envio: " . $mail->getLastError() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro no MailService: " . $e->getMessage() . "\n";
}

// 4. Verificar arquivos de log criados
echo "\n=== VERIFICANDO LOGS ===\n";
$logDir = '/opt/lampp/htdocs/CapivaraLearn/logs';
$files = scandir($logDir);
foreach ($files as $file) {
    if (substr($file, -4) === '.log') {
        echo "📄 $file (" . filesize($logDir . '/' . $file) . " bytes)\n";
    }
}

// 5. Mostrar último log do mailservice se existir
$mailLog = $logDir . '/mailservice.log';
if (file_exists($mailLog)) {
    echo "\n=== ÚLTIMAS LINHAS DO MAILSERVICE.LOG ===\n";
    $lines = file($mailLog);
    $lastLines = array_slice($lines, -10);
    foreach ($lastLines as $line) {
        echo $line;
    }
} else {
    echo "\n❌ mailservice.log não existe\n";
}
?>
