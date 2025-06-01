<?php
require_once "includes/config.php";

echo "<h1>🔍 Debug: DNS vs IP</h1>";
echo "<pre>";

echo "=== VERIFICAÇÃO DE CONECTIVIDADE ===\n";

// Testar IP direto
$ip = '38.242.252.19';
$dns = 'mail.capivaralearn.com.br';
$port = 465;

echo "1. Testando IP direto ($ip:$port):\n";
$fp = @fsockopen($ip, $port, $errno, $errstr, 10);
if ($fp) {
    echo "   ✅ IP direto funcionando\n";
    fclose($fp);
} else {
    echo "   ❌ IP direto falhou: $errstr ($errno)\n";
}

echo "\n2. Testando DNS ($dns:$port):\n";
$fp = @fsockopen($dns, $port, $errno, $errstr, 10);
if ($fp) {
    echo "   ✅ DNS funcionando\n";
    fclose($fp);
} else {
    echo "   ❌ DNS falhou: $errstr ($errno)\n";
}

echo "\n3. Verificando resolução DNS:\n";
$ip_resolved = gethostbyname($dns);
echo "   $dns resolve para: $ip_resolved\n";
if ($ip_resolved === $dns) {
    echo "   ❌ DNS não resolveu\n";
} else {
    echo "   ✅ DNS resolveu\n";
    if ($ip_resolved === $ip) {
        echo "   ✅ IP coincide com o esperado\n";
    } else {
        echo "   ⚠️ IP diferente do esperado ($ip)\n";
    }
}

echo "\n=== TESTE DE CONFIGURAÇÃO ATUAL ===\n";
echo "MAIL_HOST atual: " . MAIL_HOST . "\n";

// Forçar uso do IP se DNS não funcionar
if (MAIL_HOST !== $ip) {
    echo "\n⚠️ Configuração usando DNS, vamos testar IP:\n";
    
    // Redefinir temporariamente
    if (defined('MAIL_HOST')) {
        // PHP não permite redefinir constantes, então vamos simular
        $temp_host = $ip;
    }
} else {
    echo "✅ Configuração já usa IP direto\n";
    $temp_host = MAIL_HOST;
}

echo "\n=== TESTE MAILSERVICE COM IP DIRETO ===\n";
try {
    // Teste manual com IP forçado
    require_once "vendor/autoload.php";
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $ip; // Forçar IP
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    $mail->setFrom(MAIL_USERNAME, 'Debug Test');
    $mail->addAddress('carlospintojunior@gmail.com');
    $mail->Subject = 'Debug: IP vs DNS - ' . date('H:i:s');
    $mail->Body = 'Teste forçando IP direto: ' . $ip;
    
    $mail->send();
    echo "✅ EMAIL ENVIADO COM SUCESSO usando IP direto!\n";
    echo "🎯 SOLUÇÃO: Use sempre o IP direto ($ip)\n";
    
} catch (Exception $e) {
    echo "❌ Erro mesmo com IP direto: " . $e->getMessage() . "\n";
}

echo "\n=== ENVIRONMENT.INI STATUS ===\n";
$envFile = __DIR__ . '/includes/environment.ini';
if (file_exists($envFile)) {
    echo "✅ environment.ini existe\n";
    $config = parse_ini_file($envFile, true);
    if (isset($config['production']['mail_host'])) {
        echo "Mail host no .ini: " . $config['production']['mail_host'] . "\n";
        echo "⚠️ O .ini pode estar sobrescrevendo a configuração!\n";
    }
} else {
    echo "ℹ️ environment.ini não existe (ok)\n";
}

echo "</pre>";
?>