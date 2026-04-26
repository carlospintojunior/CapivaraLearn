<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/log_sistema.php';
require_once __DIR__ . '/includes/DatabaseConnection.php';
if (!class_exists('Database') && class_exists('CapivaraLearn\\DatabaseConnection')) {
    class_alias('CapivaraLearn\\DatabaseConnection', 'Database');
}
require_once __DIR__ . '/includes/MailService.php';

if (!function_exists('generateToken')) {
    function generateToken() {
        try { return bin2hex(random_bytes(32)); }
        catch (Exception $e) { return md5(uniqid('', true)); }
    }
}

ini_set('display_errors', 0);
error_reporting(0);

// Redirecionar se já autenticado
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Informe um e-mail válido.';
    } else {
        // Sempre exibir mensagem genérica para não revelar se o email existe
        $success = 'Se este e-mail estiver cadastrado, você receberá as instruções em instantes. Verifique também a pasta de spam.';

        try {
            $db   = Database::getInstance();
            $mail = MailService::getInstance();

            $user = $db->select(
                "SELECT id, nome, email FROM usuarios WHERE email = ? AND ativo = 1",
                [$email]
            );

            if ($user) {
                $userId = $user[0]['id'];
                $nome   = $user[0]['nome'];

                // Invalidar tokens anteriores de recuperação pendentes
                $db->execute(
                    "UPDATE email_tokens SET usado = TRUE WHERE usuario_id = ? AND tipo = 'reset_senha' AND usado = FALSE",
                    [$userId]
                );

                $token      = generateToken();
                $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $db->execute(
                    "INSERT INTO email_tokens (usuario_id, token, tipo, data_expiracao, ip_address) VALUES (?, ?, 'reset_senha', ?, ?)",
                    [$userId, $token, $expiration, $_SERVER['REMOTE_ADDR'] ?? null]
                );

                log_sistema("Token de recuperação gerado para usuario_id=$userId, email=$email", 'INFO');

                if ($mail->sendPasswordResetEmail($email, $nome, $token)) {
                    $db->execute(
                        "INSERT INTO email_log (destinatario, assunto, tipo, status) VALUES (?, ?, 'reset_senha', 'enviado')",
                        [$email, 'Redefinição de senha - CapivaraLearn']
                    );
                    log_sistema("Email de recuperação enviado para $email", 'SUCCESS');
                } else {
                    $mailError = $mail->getLastError();
                    $db->execute(
                        "INSERT INTO email_log (destinatario, assunto, tipo, status, erro_detalhes) VALUES (?, ?, 'reset_senha', 'erro', ?)",
                        [$email, 'Redefinição de senha - CapivaraLearn', $mailError]
                    );
                    log_sistema("ERRO ao enviar email de recuperação para $email: $mailError", 'ERROR');
                }
            } else {
                log_sistema("Solicitação de recuperação para email não encontrado: $email", 'INFO');
            }
        } catch (Throwable $e) {
            log_sistema("Falha no fluxo de recuperação de senha para $email: " . $e->getMessage(), 'ERROR');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - CapivaraLearn</title>
    <link rel="icon" type="image/png" href="public/assets/images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            gap: 30px;
        }

        .card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .card-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 50px 40px;
            text-align: center;
        }

        .card-header h1 { font-size: 2.2em; font-weight: 600; letter-spacing: -1px; }
        .card-header p  { opacity: 0.9; font-size: 1em; margin-top: 8px; }

        .card-body { padding: 40px; background: linear-gradient(to bottom, #fff, #f8f9fa); }

        .description {
            color: #555;
            font-size: 0.95em;
            line-height: 1.6;
            margin-bottom: 28px;
            text-align: center;
        }

        .form-group { margin-bottom: 22px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: #2c3e50;
            font-size: 0.95em;
        }
        .form-group input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #ecf0f1;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 4px rgba(52,152,219,0.1);
        }

        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            margin-bottom: 15px;
        }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(52,152,219,0.4); }

        .back-link {
            display: block;
            text-align: center;
            color: #7f8c8d;
            font-size: 0.9em;
            text-decoration: none;
            margin-top: 10px;
        }
        .back-link:hover { color: #3498db; }

        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 22px;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.5;
        }
        .alert-error   { background: #fdf2f2; color: #721c24; border-left: 4px solid #e74c3c; }
        .alert-success { background: #f1f8e9; color: #155724; border-left: 4px solid #27ae60; }

        @media (max-width: 480px) {
            .card { border-radius: 20px; }
            .card-header { padding: 35px 25px; }
            .card-body    { padding: 30px 25px; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <img src="public/assets/images/logo.png" alt="" style="width:70px;margin-bottom:15px;" onerror="this.style.display='none';">
            <h1>CapivaraLearn</h1>
            <p>Recuperação de Senha</p>
        </div>

        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">📧 <?= htmlspecialchars($success) ?></div>
                <a href="login.php" class="back-link">← Voltar para o login</a>
            <?php else: ?>
                <p class="description">
                    Informe o e-mail cadastrado na sua conta e enviaremos um link para você criar uma nova senha.
                </p>

                <form method="POST">
                    <div class="form-group">
                        <label for="email">📧 E-mail cadastrado</label>
                        <input type="email" id="email" name="email" required
                               placeholder="seu@email.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        🔑 Enviar link de recuperação
                    </button>
                </form>

                <a href="login.php" class="back-link">← Voltar para o login</a>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
