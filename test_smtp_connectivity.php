<?php
/**
 * Teste de Conectividade SMTP Simples
 * Teste direto sem PHPMailer para identificar problemas de rede
 */

echo "<h1>🔍 Diagnóstico de Conectividade SMTP</h1>";

// Configurações SMTP
$smtpHost = 'mail.capivaralearn.com.br';
$smtpPort = 465;
$timeout = 10;

echo "<h2>📊 Teste de Conectividade</h2>";
echo "<p><strong>Host:</strong> $smtpHost</p>";
echo "<p><strong>Porta:</strong> $smtpPort</p>";
echo "<p><strong>Timeout:</strong> {$timeout}s</p>";

echo "<h3>1. 🌐 Teste de DNS</h3>";
$ip = gethostbyname($smtpHost);
if ($ip === $smtpHost) {
    echo "❌ <strong>Falha na resolução DNS!</strong> Host não foi resolvido.<br>";
} else {
    echo "✅ <strong>DNS OK:</strong> $smtpHost → $ip<br>";
}

echo "<h3>2. 🔌 Teste de Conectividade TCP</h3>";
$startTime = microtime(true);

// Teste 1: Socket simples
$socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, $timeout);
$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

if ($socket) {
    echo "✅ <strong>Conexão TCP OK!</strong> Conectado em {$duration}s<br>";
    
    // Tentar ler resposta SMTP
    $response = @fread($socket, 512);
    if ($response) {
        echo "✅ <strong>Resposta SMTP:</strong> " . htmlspecialchars(trim($response)) . "<br>";
    } else {
        echo "⚠️ <strong>Sem resposta SMTP</strong> (pode ser normal para SSL)<br>";
    }
    fclose($socket);
} else {
    echo "❌ <strong>Falha na conexão TCP!</strong><br>";
    echo "<strong>Erro #{$errno}:</strong> $errstr<br>";
    echo "<strong>Tempo:</strong> {$duration}s<br>";
}

echo "<h3>3. 🔒 Teste de Conectividade SSL</h3>";
$startTime = microtime(true);

$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
]);

$sslSocket = @stream_socket_client(
    "ssl://$smtpHost:$smtpPort",
    $errno,
    $errstr,
    $timeout,
    STREAM_CLIENT_CONNECT,
    $context
);

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

if ($sslSocket) {
    echo "✅ <strong>Conexão SSL OK!</strong> Conectado em {$duration}s<br>";
    
    // Tentar ler resposta SMTP via SSL
    $response = @fread($sslSocket, 512);
    if ($response) {
        echo "✅ <strong>Resposta SMTP SSL:</strong> " . htmlspecialchars(trim($response)) . "<br>";
    }
    fclose($sslSocket);
} else {
    echo "❌ <strong>Falha na conexão SSL!</strong><br>";
    echo "<strong>Erro #{$errno}:</strong> $errstr<br>";
    echo "<strong>Tempo:</strong> {$duration}s<br>";
}

echo "<h3>4. 🧪 Teste com PHPMailer</h3>";

require_once 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = 'capivara@capivaralearn.com.br';
    $mail->Password = '_,CeLlORRy,92';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = $smtpPort;
    $mail->Timeout = $timeout;
    
    // Capturar debug do PHPMailer
    $debugOutput = '';
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
        $debugOutput .= "[$level] $str\n";
    };
    
    // Configurações SSL
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    echo "<p>⏳ Testando conexão PHPMailer...</p>";
    $startTime = microtime(true);
    
    // Apenas tentar conectar, sem enviar
    $mail->smtpConnect();
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "✅ <strong>PHPMailer conectou com sucesso!</strong> Tempo: {$duration}s<br>";
    
    $mail->smtpClose();
    
} catch (Exception $e) {
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "❌ <strong>Erro no PHPMailer:</strong><br>";
    echo "<strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Tempo:</strong> {$duration}s<br>";
}

if (!empty($debugOutput)) {
    echo "<h4>📋 Debug Output PHPMailer:</h4>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px; font-size: 12px;'>";
    echo htmlspecialchars($debugOutput);
    echo "</pre>";
}

echo "<h3>5. 🌍 Teste de Portas Alternativas</h3>";

$alternativePorts = [587, 25, 2525];
foreach ($alternativePorts as $port) {
    echo "<p><strong>Testando porta $port:</strong> ";
    $socket = @fsockopen($smtpHost, $port, $errno, $errstr, 5);
    if ($socket) {
        echo "✅ Conecta</p>";
        fclose($socket);
    } else {
        echo "❌ Falha ($errstr)</p>";
    }
}

echo "<h3>6. 🔧 Informações do Sistema</h3>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . PHP_VERSION . "</li>";
echo "<li><strong>OpenSSL:</strong> " . (extension_loaded('openssl') ? '✅ Carregado' : '❌ NÃO CARREGADO') . "</li>";
echo "<li><strong>cURL:</strong> " . (extension_loaded('curl') ? '✅ Carregado' : '❌ NÃO CARREGADO') . "</li>";
echo "<li><strong>Sockets:</strong> " . (extension_loaded('sockets') ? '✅ Carregado' : '❌ NÃO CARREGADO') . "</li>";
echo "<li><strong>allow_url_fopen:</strong> " . (ini_get('allow_url_fopen') ? '✅ Habilitado' : '❌ DESABILITADO') . "</li>";
echo "<li><strong>Server Time:</strong> " . date('Y-m-d H:i:s T') . "</li>";
echo "</ul>";

?>
<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h2, h3 { color: #2c3e50; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>
