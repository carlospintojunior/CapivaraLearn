<?php
/**
 * Teste detalhado de envio de email com captura de erros
 */

// Incluir configuração
require_once 'includes/config.php';
require_once 'includes/MailService.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste de Email Detalhado</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧪 Teste Detalhado de Envio de Email</h1>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'] ?? '';
        
        if (empty($email)) {
            echo '<div class="result error">❌ Email é obrigatório!</div>';
        } else {
            echo '<div class="result info">📧 Tentando enviar email para: <strong>' . htmlspecialchars($email) . '</strong></div>';
            
            // Ativar captura de erros
            ob_start();
            
            try {
                $mailService = new MailService();
                
                // Mostrar configurações (sem senha)
                echo '<div class="result info">';
                echo '<h3>⚙️ Configurações do MailService:</h3>';
                echo '<pre>';
                echo "Host: " . $mailService->getHost() . "\n";
                echo "Port: " . $mailService->getPort() . "\n";
                echo "Username: " . $mailService->getUsername() . "\n";
                echo "Secure: " . $mailService->getSecure() . "\n";
                echo "Auth: " . ($mailService->getAuth() ? 'true' : 'false') . "\n";
                echo '</pre>';
                echo '</div>';
                
                // Tentar enviar email
                $subject = "Teste de Email - " . date('Y-m-d H:i:s');
                $message = "Este é um email de teste enviado em " . date('Y-m-d H:i:s') . "\n\n";
                $message .= "Se você recebeu este email, o sistema de envio está funcionando corretamente!";
                
                echo '<div class="result info">📤 Enviando email...</div>';
                
                $resultado = $mailService->sendEmail($email, $subject, $message);
                
                if ($resultado) {
                    echo '<div class="result success">✅ Email enviado com sucesso!</div>';
                } else {
                    echo '<div class="result error">❌ Falha no envio do email</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="result error">';
                echo '<h3>💥 Erro capturado:</h3>';
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                echo '<h4>🔍 Stack Trace:</h4>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                echo '</div>';
            }
            
            // Capturar qualquer output/erro adicional
            $output = ob_get_clean();
            if (!empty($output)) {
                echo '<div class="result info">';
                echo '<h3>📋 Output adicional:</h3>';
                echo '<pre>' . htmlspecialchars($output) . '</pre>';
                echo '</div>';
            }
        }
    }
    ?>
    
    <form method="post">
        <h3>🎯 Teste de Envio</h3>
        <p>
            <label for="email">Email de destino:</label><br>
            <input type="email" name="email" id="email" value="carloscfcortez@gmail.com" style="width: 300px; padding: 8px;" required>
        </p>
        <p>
            <button type="submit" style="padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 3px; cursor: pointer;">
                📧 Enviar Email de Teste
            </button>
        </p>
    </form>
    
    <hr>
    
    <h3>🔧 Informações do Sistema</h3>
    <div class="result info">
        <pre><?php
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "OpenSSL: " . (extension_loaded('openssl') ? 'Carregado' : 'NÃO CARREGADO') . "\n";
        echo "Curl: " . (extension_loaded('curl') ? 'Carregado' : 'NÃO CARREGADO') . "\n";
        echo "Socket: " . (extension_loaded('sockets') ? 'Carregado' : 'NÃO CARREGADO') . "\n";
        echo "Date/Time: " . date('Y-m-d H:i:s T') . "\n";
        echo "Server: " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\n";
        ?></pre>
    </div>
    
</body>
</html>
