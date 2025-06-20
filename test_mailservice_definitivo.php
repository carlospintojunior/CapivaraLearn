<?php
echo "<h2>Teste Definitivo do MailService</h2>";

// Teste 1: Verificar se conseguimos incluir o MailService
echo "<h3>1. Testando include do MailService</h3>";
try {
    require_once __DIR__ . '/includes/MailService.php';
    echo "✅ MailService incluído com sucesso<br>";
} catch (Exception $e) {
    echo "❌ Erro ao incluir MailService: " . $e->getMessage() . "<br>";
    exit;
}

// Teste 2: Verificar se conseguimos criar uma instância
echo "<h3>2. Testando instância do MailService</h3>";
try {
    $mailService = MailService::getInstance();
    echo "✅ Instância do MailService criada com sucesso<br>";
} catch (Exception $e) {
    echo "❌ Erro ao criar instância: " . $e->getMessage() . "<br>";
    exit;
}

// Teste 3: Verificar logs antes do teste
echo "<h3>3. Estado do log antes do teste</h3>";
$logFile = __DIR__ . '/logs/sistema.log';
if (file_exists($logFile)) {
    echo "Log atual:<br><pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
} else {
    echo "Arquivo de log não existe ainda<br>";
}

// Teste 4: Tentar enviar email de teste
echo "<h3>4. Testando envio de email</h3>";
echo "Tentando enviar email para: teste@exemplo.com<br>";

try {
    $result = $mailService->sendConfirmationEmail('teste@exemplo.com', 'Teste', 'token123');
    echo "Resultado do envio: " . ($result ? "✅ Sucesso" : "❌ Falha") . "<br>";
} catch (Exception $e) {
    echo "❌ Exceção durante envio: " . $e->getMessage() . "<br>";
}

// Teste 5: Verificar logs após o teste
echo "<h3>5. Estado do log após o teste</h3>";
if (file_exists($logFile)) {
    $newContent = file_get_contents($logFile);
    echo "Log atualizado:<br><pre>" . htmlspecialchars($newContent) . "</pre>";
} else {
    echo "❌ Arquivo de log ainda não foi criado!<br>";
}

// Teste 6: Verificar erros do último comando
echo "<h3>6. Últimos erros do MailService</h3>";
if (method_exists($mailService, 'getLastError')) {
    $lastError = $mailService->getLastError();
    echo "Último erro: " . htmlspecialchars($lastError) . "<br>";
} else {
    echo "Método getLastError não encontrado<br>";
}

// Teste 7: Verificar se a função log_sistema está disponível
echo "<h3>7. Testando função log_sistema diretamente</h3>";
if (function_exists('log_sistema')) {
    echo "✅ Função log_sistema está disponível<br>";
    log_sistema('Teste direto da função log_sistema pelo teste do MailService', 'TEST');
    echo "✅ Chamada à log_sistema executada<br>";
} else {
    echo "❌ Função log_sistema não está disponível!<br>";
}

echo "<h3>8. Log final completo</h3>";
if (file_exists($logFile)) {
    $finalContent = file_get_contents($logFile);
    echo "Log completo:<br><pre>" . htmlspecialchars($finalContent) . "</pre>";
} else {
    echo "❌ Nenhum log foi criado durante todo o teste!<br>";
}
?>
