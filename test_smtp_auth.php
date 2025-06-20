<?php
// Include do sistema de log simples
function simpleLog($message, $level = 'INFO') {
    $logDir = '/opt/lampp/htdocs/CapivaraLearn/logs';
    $logFile = $logDir . '/smtp_auth_test.log';
    
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] $level | $message" . PHP_EOL;
    
    @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

// Verificar se PHPMailer existe
if (!file_exists('vendor/autoload.php')) {
    echo "❌ PHPMailer não encontrado. Instale com: composer install<br>";
    simpleLog("PHPMailer não encontrado", "ERROR");
    exit;
}

require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

simpleLog("=== TESTE DE AUTENTICAÇÃO SMTP ===");

echo "<h1>🔐 Teste de Autenticação SMTP</h1>";

// Configurações
$host = 'mail.capivaralearn.com.br';
$port = 465;
$username = 'capivara@capivaralearn.com.br';
$password = '_,CeLlORRy,92';

echo "<p><strong>Host:</strong> $host</p>";
echo "<p><strong>Porta:</strong> $port</p>";
echo "<p><strong>Usuário:</strong> $username</p>";
echo "<p><strong>Senha:</strong> " . str_repeat('*', strlen($password)) . "</p>";

try {
    $mail = new PHPMailer(true);
    
    // Configurar SMTP
    $mail->isSMTP();
    $mail->Host = $host;
    $mail->SMTPAuth = true;
    $mail->Username = $username;
    $mail->Password = $password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL na porta 465
    $mail->Port = $port;
    $mail->Timeout = 30;
    
    // Configurações SSL menos restritivas
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // Capturar debug detalhado
    $debugOutput = '';
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
        $debugOutput .= "[$level] $str\n";
        simpleLog("SMTP [$level]: " . trim($str), "DEBUG");
    };
    
    simpleLog("Iniciando teste de autenticação...", "INFO");
    echo "<h3>⏳ Testando autenticação...</h3>";
    
    $startTime = microtime(true);
    
    // Tentar conectar e autenticar
    $mail->smtpConnect();
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "✅ <strong>Autenticação bem-sucedida!</strong> Tempo: {$duration}s<br>";
    simpleLog("Autenticação SMTP OK - Tempo: {$duration}s", "SUCCESS");
    
    // Fechar conexão
    $mail->smtpClose();
    
    echo "<h3>📧 Testando envio básico...</h3>";
    
    // Configurar email de teste
    $mail->setFrom($username, 'CapivaraLearn Test');
    $mail->addAddress('carloscfcortez@gmail.com', 'Teste');
    $mail->Subject = 'Teste SMTP - ' . date('Y-m-d H:i:s');
    $mail->Body = '<h1>Teste de Email</h1><p>Este email foi enviado em ' . date('Y-m-d H:i:s') . '</p>';
    $mail->isHTML(true);
    
    $startTime = microtime(true);
    $mail->send();
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "✅ <strong>Email enviado com sucesso!</strong> Tempo: {$duration}s<br>";
    simpleLog("Email enviado com sucesso - Tempo: {$duration}s", "SUCCESS");
    
} catch (Exception $e) {
    $endTime = microtime(true);
    $duration = isset($startTime) ? round($endTime - $startTime, 2) : 0;
    
    echo "❌ <strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "⏱️ <strong>Tempo:</strong> {$duration}s<br>";
    
    simpleLog("ERRO: " . $e->getMessage(), "ERROR");
    simpleLog("Tempo: {$duration}s", "ERROR");
    
    if (isset($mail) && !empty($mail->ErrorInfo)) {
        echo "📋 <strong>ErrorInfo:</strong> " . htmlspecialchars($mail->ErrorInfo) . "<br>";
        simpleLog("PHPMailer ErrorInfo: " . $mail->ErrorInfo, "ERROR");
    }
}

// Mostrar debug output
if (!empty($debugOutput)) {
    echo "<h3>📋 Debug Output SMTP</h3>";
    echo "<pre style='background: #f0f0f0; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto; font-size: 12px;'>";
    echo htmlspecialchars($debugOutput);
    echo "</pre>";
}

simpleLog("=== TESTE FINALIZADO ===");

echo "<h3>📄 Links Úteis</h3>";
echo "<p><a href='view_logs_simple.php?file=smtp_auth_test.log'>📋 Ver log deste teste</a></p>";
echo "<p><a href='view_logs_simple.php'>📋 Ver todos os logs</a></p>";
echo "<p><a href='test_smtp_simple.php'>🔌 Teste de conectividade</a></p>";

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h3 { color: #2c3e50; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
a { color: #3498db; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
