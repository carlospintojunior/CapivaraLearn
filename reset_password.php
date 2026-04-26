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

ini_set('display_errors', 0);
error_reporting(0);

// Redirecionar se já autenticado
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$token     = trim($_GET['token'] ?? '');
$error     = '';
$success   = '';
$tokenData = null;

if (empty($token)) {
    $error = 'Link inválido. Solicite uma nova recuperação de senha.';
} else {
    $db = Database::getInstance();

    $rows = $db->select(
        "SELECT et.id, et.usuario_id, u.nome, u.email
         FROM email_tokens et
         JOIN usuarios u ON et.usuario_id = u.id
         WHERE et.token = ?
           AND et.tipo = 'recuperacao'
           AND et.usado = FALSE
           AND et.data_expiracao > NOW()",
        [$token]
    );

    if (empty($rows)) {
        $error = 'Este link é inválido ou expirou. Solicite uma nova recuperação de senha.';
        log_sistema("Token de recuperação inválido/expirado: $token", 'WARNING');
    } else {
        $tokenData = $rows[0];
    }
}

// Processar nova senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenData) {
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($password !== $confirmPassword) {
        $error = 'As senhas não coincidem.';
    } else {
        $db = Database::getInstance();

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $updated = $db->execute(
            "UPDATE usuarios SET senha = ? WHERE id = ?",
            [$hashed, $tokenData['usuario_id']]
        );

        $db->execute(
            "UPDATE email_tokens SET usado = TRUE WHERE id = ?",
            [$tokenData['id']]
        );

        if ($updated) {
            $success = 'Senha redefinida com sucesso! Você já pode fazer login com a nova senha.';
            log_sistema("Senha redefinida com sucesso para usuario_id={$tokenData['usuario_id']}, email={$tokenData['email']}", 'SUCCESS');
            $tokenData = null; // Ocultar formulário após sucesso
        } else {
            $error = 'Erro ao salvar a nova senha. Tente novamente.';
            log_sistema("Erro ao redefinir senha para usuario_id={$tokenData['usuario_id']}", 'ERROR');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - CapivaraLearn</title>
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

        .user-info {
            background: #e8f4fd;
            border-left: 4px solid #3498db;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.92em;
            color: #2c3e50;
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

        .password-hint {
            display: block;
            font-size: 0.82em;
            color: #7f8c8d;
            margin-top: 6px;
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
            background: linear-gradient(135deg, #27ae60, #219a52);
            color: white;
            margin-bottom: 15px;
        }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(39,174,96,0.4); }

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
            <p>Redefinir Senha</p>
        </div>

        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
                <a href="forgot_password.php" class="back-link">← Solicitar novo link</a>

            <?php elseif ($success): ?>
                <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
                <a href="login.php" class="back-link">🚀 Ir para o login</a>

            <?php elseif ($tokenData): ?>
                <div class="user-info">
                    🔑 Redefinindo senha para: <strong><?= htmlspecialchars($tokenData['email']) ?></strong>
                </div>

                <form method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="form-group">
                        <label for="password">🔒 Nova senha</label>
                        <input type="password" id="password" name="password" required
                               placeholder="Mínimo 6 caracteres" autocomplete="new-password">
                        <span class="password-hint">Use pelo menos 6 caracteres.</span>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">🔒 Confirmar nova senha</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               placeholder="Digite a senha novamente" autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        ✅ Salvar nova senha
                    </button>
                </form>

                <a href="login.php" class="back-link">← Voltar para o login</a>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>

    <script>
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const pwd     = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            if (pwd.length < 6) {
                e.preventDefault();
                alert('A senha deve ter pelo menos 6 caracteres.');
                return;
            }
            if (pwd !== confirm) {
                e.preventDefault();
                alert('As senhas não coincidem.');
            }
        });
    </script>
</body>
</html>
