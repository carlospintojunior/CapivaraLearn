<?php
// Include do sistema de log simples
function simpleLog($message, $level = 'INFO') {
    $logDir = '/opt/lampp/htdocs/CapivaraLearn/logs';
    $logFile = $logDir . '/smtp_test.log';
    
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $logLine = "[$timestamp] $level | $message" . PHP_EOL;
    
    @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

// Log início do teste
simpleLog("=== INICIANDO TESTE SMTP ===");

echo "<h1>🔍 Teste SMTP Simples</h1>";

// Configurações
$host = 'mail.capivaralearn.com.br';
$port = 465;

simpleLog("Host: $host, Port: $port");

echo "<h3>1. 🌐 Teste de DNS</h3>";
$ip = gethostbyname($host);
if ($ip === $host) {
    echo "❌ DNS FALHOU - Host não resolve<br>";
    simpleLog("DNS FALHOU - Host não resolve", "ERROR");
} else {
    echo "✅ DNS OK: $host → $ip<br>";
    simpleLog("DNS OK: $host → $ip", "SUCCESS");
}

echo "<h3>2. 🔌 Teste de Conectividade</h3>";
simpleLog("Testando conectividade TCP...");

$startTime = microtime(true);
$socket = @fsockopen($host, $port, $errno, $errstr, 10);
$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

if ($socket) {
    echo "✅ Conectou com sucesso em {$duration}s<br>";
    simpleLog("Conectividade TCP OK - Tempo: {$duration}s", "SUCCESS");
    fclose($socket);
} else {
    echo "❌ Falha na conexão: #{$errno} - $errstr<br>";
    simpleLog("Falha TCP: #{$errno} - $errstr - Tempo: {$duration}s", "ERROR");
}

echo "<h3>3. 🔒 Teste SSL</h3>";
simpleLog("Testando conectividade SSL...");

$startTime = microtime(true);
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
]);

$sslSocket = @stream_socket_client(
    "ssl://$host:$port",
    $errno,
    $errstr,
    10,
    STREAM_CLIENT_CONNECT,
    $context
);

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

if ($sslSocket) {
    echo "✅ SSL conectou em {$duration}s<br>";
    simpleLog("SSL OK - Tempo: {$duration}s", "SUCCESS");
    
    // Tentar ler resposta SMTP
    $response = @fread($sslSocket, 512);
    if ($response) {
        $responseClean = trim($response);
        echo "✅ Resposta SMTP: " . htmlspecialchars($responseClean) . "<br>";
        simpleLog("Resposta SMTP: $responseClean", "INFO");
    }
    fclose($sslSocket);
} else {
    echo "❌ SSL falhou: #{$errno} - $errstr<br>";
    simpleLog("SSL FALHOU: #{$errno} - $errstr - Tempo: {$duration}s", "ERROR");
}

echo "<h3>4. 🌍 Testando portas alternativas</h3>";
$alternativePorts = [587, 25, 2525];
foreach ($alternativePorts as $testPort) {
    $socket = @fsockopen($host, $testPort, $errno, $errstr, 5);
    if ($socket) {
        echo "✅ Porta $testPort: Conecta<br>";
        simpleLog("Porta $testPort: OK", "INFO");
        fclose($socket);
    } else {
        echo "❌ Porta $testPort: Falha ($errstr)<br>";
        simpleLog("Porta $testPort: FALHA ($errstr)", "WARNING");
    }
}

simpleLog("=== TESTE SMTP FINALIZADO ===");

echo "<h3>📋 Log do Teste</h3>";
$logFile = '/opt/lampp/htdocs/CapivaraLearn/logs/smtp_test.log';
if (file_exists($logFile)) {
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars(file_get_contents($logFile));
    echo "</pre>";
} else {
    echo "❌ Arquivo de log não encontrado<br>";
}

echo "<p><a href='view_logs.php'>📋 Ver todos os logs</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h3 { color: #2c3e50; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>
