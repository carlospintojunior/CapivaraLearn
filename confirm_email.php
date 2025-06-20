<?php
// Carregar configuração com classe Database
require_once __DIR__ . '/includes/config.php';
// Incluir DatabaseConnection e fallback caso Database não exista
require_once __DIR__ . '/includes/DatabaseConnection.php';
if (!class_exists('Database') && class_exists('CapivaraLearn\\DatabaseConnection')) {
    class_alias('CapivaraLearn\\DatabaseConnection', 'Database');
}

// Sistema de logs
require_once __DIR__ . '/includes/log_sistema.php';
// Garantir existência de logActivity() para registrar atividades, se não definida
if (!function_exists('logActivity')) {
    function logActivity($action, $description = '', $userId = null) {
        log_sistema("[logActivity fallback] {$action} | {$description}", 'INFO');
    }
}

// Handlers para capturar erros fatais e exceções
set_exception_handler(function (\Throwable $e) {
    log_sistema("Exceção não capturada em confirm_email.php: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine(), 'ERROR');
});
set_error_handler(function ($severity, $message, $file, $line) {
    log_sistema("Erro [" . $severity . "] " . $message . " em " . $file . ":" . $line, 'ERROR');
    return false;
});
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        log_sistema("Fatal shutdown error em confirm_email.php: " . $error['message'] . " em " . $error['file'] . ":" . $error['line'], 'ERROR');
    }
});
// Registrar acesso à confirmação de email
log_sistema('Tela de confirmação de email carregada', 'INFO');

$message = '';
$success = false;
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = 'Token de confirmação inválido ou ausente.';
} else {
    $db = Database::getInstance();
    
    try {
        // Buscar token válido
        $tokenData = $db->select(
            "SELECT et.*, u.nome, u.email 
             FROM email_tokens et
             JOIN usuarios u ON et.usuario_id = u.id
             WHERE et.token = ? 
             AND et.tipo = 'confirmacao' 
             AND et.usado = FALSE 
             AND et.data_expiracao > NOW()",
            [$token]
        );
        // Debug: registrar conteúdo retornado de tokenData
        log_sistema("[confirm_email] tokenData: " . json_encode($tokenData), 'DEBUG');

        if (empty($tokenData)) {
            // Log token inválido/expirado para auditoria (já registrado em WARNING above)
            $message = 'Token inválido, expirado ou já utilizado.';
        } else {
            $tokenInfo = $tokenData[0];
            
            // Marcar email como verificado (sem data_verificacao, coluna não existe em algumas versões)
            $verified = $db->execute(
                "UPDATE usuarios 
                 SET email_verificado = TRUE 
                 WHERE id = ?",
                [$tokenInfo['usuario_id']]
            );
            
            // Marcar token como usado
            $tokenUsed = $db->execute(
                "UPDATE email_tokens 
                 SET usado = TRUE 
                 WHERE id = ?",
                [$tokenInfo['id']]
            );
            
            if ($verified && $tokenUsed) {
                $success = true;
                $message = "Email confirmado com sucesso! Agora você pode fazer login no sistema.";
                
                // Log da confirmação
                logActivity('email_confirmed', "Email confirmado para usuário: {$tokenInfo['email']}");
            } else {
                // Log falha de atualização de confirmação
                log_sistema("Falha ao atualizar confirmação de email. verified: " . var_export($verified, true) . "; tokenUsed: " . var_export($tokenUsed, true) . "; tokenID: " . $tokenInfo['id'], 'ERROR');
                $message = 'Erro interno. Tente novamente mais tarde.';
            }
        }
        
    } catch (Exception $e) {
        $message = 'Erro interno do sistema. Tente novamente mais tarde.';
        // Registrar no sistema.log
        log_sistema("Erro confirmação email: " . $e->getMessage(), 'ERROR');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmação de Email - CapivaraLearn</title>
    <link rel="icon" type="image/png" href="public/assets/images/logo.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .confirmation-container {
            background: white;
            border-radius: 25px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 50px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(-50%, -50%) rotate(0deg); }
            50% { transform: translate(-50%, -50%) rotate(180deg); }
        }

        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            position: relative;
            z-index: 2;
        }

        .logo-image {
            width: 100px;
            height: 100px;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 600;
            letter-spacing: -1px;
        }

        .content {
            padding: 50px 40px;
            text-align: center;
        }

        .status-icon {
            font-size: 4em;
            margin-bottom: 20px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .success-icon {
            color: #27ae60;
        }

        .error-icon {
            color: #e74c3c;
        }

        .message {
            font-size: 1.2em;
            margin-bottom: 30px;
            line-height: 1.6;
            color: #2c3e50;
        }

        .message.success {
            color: #27ae60;
        }

        .message.error {
            color: #e74c3c;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-success {
            background: linear-gradient(135deg, #27ae60, #219a52);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(39, 174, 96, 0.4);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.4);
        }

        .additional-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 0.9em;
            color: #7f8c8d;
        }

        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #ecf0f1;
            font-size: 0.9em;
            color: #7f8c8d;
        }

        @media (max-width: 480px) {
            .confirmation-container {
                margin: 10px;
                border-radius: 20px;
            }
            
            .header {
                padding: 40px 30px;
            }
            
            .content {
                padding: 40px 30px;
            }
            
            .header h1 {
                font-size: 2em;
            }

            .logo-image {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="header">
            <div class="logo-container">
                <img src="public/assets/images/logo.png" alt="CapivaraLearn" class="logo-image" onerror="this.style.display='none';">
                <h1>CapivaraLearn</h1>
            </div>
        </div>

        <div class="content">
            <?php if ($success): ?>
                <div class="status-icon success-icon">✅</div>
                <div class="message success"><?= htmlspecialchars($message) ?></div>
                
                <a href="login.php" class="btn btn-success">
                    🚀 Fazer Login Agora
                </a>
                
                <div class="additional-info">
                    <strong>🎉 Parabéns!</strong><br>
                    Seu email foi confirmado com sucesso. Agora você pode:
                    <ul style="text-align: left; margin: 10px 0; padding-left: 20px;">
                        <li>Fazer login no sistema</li>
                        <li>Criar seus módulos de estudo</li>
                        <li>Organizar seus tópicos</li>
                        <li>Receber notificações por email</li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="status-icon error-icon">❌</div>
                <div class="message error"><?= htmlspecialchars($message) ?></div>
                
                <a href="login.php" class="btn btn-primary">
                    🏠 Voltar ao Login
                </a>
                
                <div class="additional-info">
                    <strong>⚠️ Problemas com a confirmação?</strong><br>
                    <ul style="text-align: left; margin: 10px 0; padding-left: 20px;">
                        <li>Verifique se o link não foi cortado pelo email</li>
                        <li>O token pode ter expirado (válido por 24 horas)</li>
                        <li>Tente se cadastrar novamente se necessário</li>
                        <li>Entre em contato conosco em caso de problemas</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            © 2025 CapivaraLearn - Sistema de Organização de Estudos
        </div>
    </div>
</body>
</html>