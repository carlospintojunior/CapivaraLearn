<?php
require_once "includes/config.php";

echo "<h1>🔍 Teste do Config Corrigido</h1>";
echo "<pre>";

echo "=== CONSTANTES DE EMAIL ===\n";
echo "MAIL_HOST: " . (defined('MAIL_HOST') ? MAIL_HOST : "❌ NÃO DEFINIDA") . "\n";
echo "MAIL_PORT: " . (defined('MAIL_PORT') ? MAIL_PORT : "❌ NÃO DEFINIDA") . "\n";
echo "MAIL_USERNAME: " . (defined('MAIL_USERNAME') ? MAIL_USERNAME : "❌ NÃO DEFINIDA") . "\n";
echo "MAIL_PASSWORD: " . (defined('MAIL_PASSWORD') ? str_repeat('*', strlen(MAIL_PASSWORD)) : "❌ NÃO DEFINIDA") . "\n";
echo "MAIL_FROM_NAME: " . (defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : "❌ NÃO DEFINIDA") . "\n";
echo "MAIL_FROM_EMAIL: " . (defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : "❌ NÃO DEFINIDA") . "\n";

echo "\n=== TESTE MAILSERVICE ===\n";
try {
    $mailService = MailService::getInstance();
    echo "✅ MailService instanciado\n";
    
    // Mostrar configuração
    $config = $mailService->getConfig();
    foreach ($config as $key => $value) {
        if ($key === 'user' && !empty($value)) {
            $value = substr($value, 0, 3) . '***@' . substr($value, strpos($value, '@'));
        }
        echo "$key: $value\n";
    }
    
    // Teste de envio
    echo "\n🚀 Testando envio de email...\n";
    $result = $mailService->sendConfirmationEmail(
        'carlospintojunior@gmail.com',
        'Teste Config Corrigido',
        'token_config_' . time()
    );
    
    if ($result) {
        echo "✅ EMAIL ENVIADO COM SUCESSO!\n";
        echo "🎉 O problema foi resolvido!\n";
    } else {
        echo "❌ Falha no envio: " . $mailService->getLastError() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n=== OUTRAS CONFIGURAÇÕES ===\n";
echo "APP_ENV: " . APP_ENV . "\n";
echo "DEBUG_MODE: " . (DEBUG_MODE ? "true" : "false") . "\n";
echo "APP_URL: " . APP_URL . "\n";

echo "</pre>";
?>