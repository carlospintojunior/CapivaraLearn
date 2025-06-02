<?php
require_once 'includes/config.php';

// Adicionar exibição de erros diretamente na página em modo de desenvolvimento
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Se já estiver logado, redirecionar
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Processar login/registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = Database::getInstance();
    $mail = MailService::getInstance();
    
    if ($_POST['action'] === 'login') {
        try {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $error = 'E-mail e senha são obrigatórios';
            } else {
                $user = $db->select(
                    "SELECT id, nome, email, senha, ativo, email_verificado FROM usuarios WHERE email = ? AND ativo = 1",
                    [$email]
                );
                
                if (!$user) {
                    $error = 'E-mail não encontrado ou conta inativa';
                    error_log("Tentativa de login com email não encontrado: $email");
                } elseif (!password_verify($password, $user[0]['senha'])) {
                    $error = 'Senha incorreta';
                    error_log("Tentativa de login com senha incorreta para: $email");
                } elseif (!$user[0]['email_verificado']) {
                    $error = 'Você precisa confirmar seu email antes de fazer login. Verifique sua caixa de entrada.';
                    $success = '<div class="resend-container">
                        <p>Não recebeu o email? 
                        <a href="?resend_email=' . urlencode($email) . '" class="resend-link">
                        Clique aqui para reenviar</a></p>
                    </div>';
                } else {
                    $_SESSION['user_id'] = $user[0]['id'];
                    $_SESSION['user_name'] = $user[0]['nome'];
                    $_SESSION['user_email'] = $user[0]['email'];
                    $_SESSION['logged_in'] = true;
                    
                    $db->execute("UPDATE usuarios SET data_ultimo_acesso = NOW() WHERE id = ?", [$user[0]['id']]);
                    logActivity('user_login', "Login realizado: {$user[0]['email']}");
                    
                    header('Location: dashboard.php');
                    exit();
                }
            }
        } catch (Exception $e) {
            var_dump($e); // Inspecionar o objeto da exceção
            $error = (APP_ENV === 'development') ? 
                '⚠️ Erro no login: ' . $e->getMessage() : 
                '⚠️ Erro ao fazer login. Por favor, tente novamente mais tarde.';
            error_log("Erro no login: " . $e->getMessage());
        }
    } elseif ($_POST['action'] === 'register') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validações
        if (empty($nome) || empty($email) || empty($password)) {
            $error = 'Nome, e-mail e senha são obrigatórios';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'E-mail inválido';
        } elseif (strlen($password) < 6) {
            $error = 'A senha deve ter pelo menos 6 caracteres';
        } elseif ($password !== $confirmPassword) {
            $error = 'As senhas não coincidem';
        } else {
            // Verificar se e-mail já existe
            $existing = $db->select("SELECT id, email_verificado FROM usuarios WHERE email = ?", [$email]);
            
            if ($existing) {
                if ($existing[0]['email_verificado']) {
                    $error = 'E-mail já cadastrado e verificado. Tente fazer login.';
                } else {
                    $error = 'E-mail já cadastrado mas não verificado. Verifique sua caixa de entrada ou ';
                    $error .= '<a href="?resend_email=' . urlencode($email) . '" style="color: #3498db;">clique aqui para reenviar a confirmação</a>';
                }
            } else {
                // Criar usuário
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    $stmt = $db->getConnection()->prepare(
                        "INSERT INTO usuarios (nome, email, senha, email_verificado) VALUES (?, ?, ?, FALSE)"
                    );
                    
                    if ($stmt->execute([$nome, $email, $hashedPassword])) {
                        $userId = $db->getConnection()->lastInsertId();
                        
                        // Criar configurações padrão
                        $db->execute("INSERT INTO configuracoes_usuario (usuario_id) VALUES (?)", [$userId]);
                        
                        // Gerar token de confirmação
                        $token = generateToken();
                        $expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));
                        
                        $db->execute(
                            "INSERT INTO email_tokens (usuario_id, token, tipo, data_expiracao, ip_address) VALUES (?, ?, 'confirmacao', ?, ?)",
                            [$userId, $token, $expiration, $_SERVER['REMOTE_ADDR'] ?? null]
                        );
                        
                        // Tentar enviar email
                        if ($mail->sendConfirmationEmail($email, $nome, $token)) {
                            // Log do email enviado
                            $db->execute(
                                "INSERT INTO email_log (usuario_id, email_destino, assunto, tipo, status) VALUES (?, ?, ?, 'confirmacao', 'enviado')",
                                [$userId, $email, 'Confirme seu cadastro no CapivaraLearn']
                            );
                            
                            $success = 'Conta criada com sucesso! Verifique seu email para confirmar o cadastro.';
                            logActivity('user_registered', "Novo usuário registrado: $email");
                        } else {
                            // Log do erro de email
                            $db->execute(
                                "INSERT INTO email_log (usuario_id, email_destino, assunto, tipo, status, erro_detalhes) VALUES (?, ?, ?, 'confirmacao', 'falha', ?)",
                                [$userId, $email, 'Confirme seu cadastro no CapivaraLearn', 'Erro no envio SMTP']
                            );
                            
                            $error = 'Conta criada, mas houve um problema no envio do email. Entre em contato conosco.';
                        }
                    } else {
                        $error = 'Erro ao criar conta';
                    }
                } catch (Exception $e) {
                    $error = (APP_ENV === 'development') ? 
                        '⚠️ Erro: ' . $e->getMessage() : 
                        '⚠️ Erro interno. Por favor, tente novamente mais tarde.';
                    logActivity('registration_error', $e->getMessage());
                    error_log("Erro no registro: " . $e->getMessage());
                }
            }
        }
    }
}

// Reenviar email de confirmação
if (isset($_GET['resend_email'])) {
    $email = trim($_GET['resend_email']);
    $db = Database::getInstance();
    $mail = MailService::getInstance();

    $user = $db->select("SELECT id, nome, email_verificado FROM usuarios WHERE email = ?", [$email]);

    if ($user && !$user[0]['email_verificado']) {
        // Invalidar tokens antigos
        $db->execute("UPDATE email_tokens SET usado = TRUE WHERE usuario_id = ? AND tipo = 'confirmacao'", [$user[0]['id']]);

        // Criar novo token
        $token = generateToken();
        $expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $db->execute(
            "INSERT INTO email_tokens (usuario_id, token, tipo, data_expiracao, ip_address) VALUES (?, ?, 'confirmacao', ?, ?)",
            [$user[0]['id'], $token, $expiration, $_SERVER['REMOTE_ADDR'] ?? null]
        );

        // Tentar enviar email
        if ($mail->sendConfirmationEmail($email, $user[0]['nome'], $token)) {
            $success = 'Email de confirmação reenviado! Verifique sua caixa de entrada.';
        } else {
            $error = 'Erro ao reenviar email. Tente novamente mais tarde.';
            error_log("Erro ao enviar email: " . $mail->getLastError());
        }
    } else {
        $error = 'Email não encontrado ou já verificado.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CapivaraLearn</title>
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
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="0.5"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
            pointer-events: none;
        }

        .login-container {
            background: white;
            border-radius: 25px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            animation: slideUp 0.8s ease-out;
            position: relative;
            z-index: 1;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 50px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
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
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .environment-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(5px);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .environment-development {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }

        .environment-production {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }

        .login-header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 600;
            letter-spacing: -1px;
        }

        .login-header p {
            opacity: 0.95;
            font-size: 1.1em;
            font-weight: 300;
        }

        .login-form {
            padding: 50px 40px;
            background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
        }

        .tabs {
            display: flex;
            margin-bottom: 40px;
            border-bottom: 1px solid #ecf0f1;
            position: relative;
        }

        .tab {
            flex: 1;
            padding: 18px;
            text-align: center;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            font-weight: 700;
            font-size: 1.1em;
            position: relative;
        }

        .tab.active {
            color: #3498db;
            border-bottom-color: #3498db;
        }

        .tab:hover:not(.active) {
            color: #2980b9;
            background: rgba(52, 152, 219, 0.05);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 700;
            color: #2c3e50;
            font-size: 0.95em;
        }

        .form-group input {
            width: 100%;
            padding: 18px 25px;
            border: 2px solid #ecf0f1;
            border-radius: 15px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
            font-weight: 500;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
            transform: translateY(-2px);
        }

        .btn {
            width: 100%;
            padding: 18px;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-login {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            margin-bottom: 15px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.4);
        }

        .btn-register {
            background: linear-gradient(135deg, #27ae60, #219a52);
            color: white;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(39, 174, 96, 0.4);
        }

        .alert {
            padding: 18px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            line-height: 1.5;
        }

        .alert-error {
            background: linear-gradient(135deg, #fdf2f2 0%, #fef5f5 100%);
            color: #721c24;
            border-left: 4px solid #e74c3c;
        }

        .alert-success {
            background: linear-gradient(135deg, #f1f8e9 0%, #f4f9f0 100%);
            color: #155724;
            border-left: 4px solid #27ae60;
        }

        .demo-login {
            background: linear-gradient(135deg, #fff3cd 0%, #fefcf3 100%);
            border: 1px solid #ffeaa7;
            border-left: 4px solid #f39c12;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .demo-login h4 {
            color: #856404;
            margin-bottom: 12px;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .demo-login p {
            color: #856404;
            font-size: 14px;
            margin: 5px 0;
            font-weight: 600;
        }

        .demo-login code {
            background: rgba(255,255,255,0.8);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }

        .email-notice {
            background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 100%);
            border-left: 4px solid #2196f3;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .email-notice h5 {
            color: #1976d2;
            margin-bottom: 8px;
            font-size: 1em;
        }

        .email-notice p {
            color: #1565c0;
            margin: 0;
            line-height: 1.4;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
                border-radius: 20px;
            }
            
            .login-header {
                padding: 40px 30px;
            }
            
            .login-form {
                padding: 40px 30px;
            }
            
            .login-header h1 {
                font-size: 2em;
            }

            .logo-image {
                width: 60px;
                height: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="environment-indicator <?= APP_ENV === 'development' ? 'environment-development' : 'environment-production' ?>">
                🛠️ <?= strtoupper(APP_ENV) ?>
            </div>
            <div class="logo-container">
                <img src="public/assets/images/logo.png" alt="CapivaraLearn" class="logo-image" onerror="this.style.display='none';">
                <div>
                    <h1>CapivaraLearn</h1>
                    <p>Sistema de Organização de Estudos</p>
                </div>
            </div>
        </div>

        <div class="login-form">
            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= $success ?></div>
            <?php endif; ?>

            <!-- Email Notice -->
            <div class="email-notice">
                <h5>📧 Sistema de Confirmação por Email</h5>
                <p>Ao se cadastrar, você receberá um email de confirmação. Verifique também a pasta de spam/lixo eletrônico.</p>
            </div>

            <!-- Demo Login Info -->
            <div class="demo-login">
                <h4>🧪 Login de Demonstração</h4>
                <p><strong>E-mail:</strong> <code>teste@capivaralearn.com</code></p>
                <p><strong>Senha:</strong> <code>123456</code></p>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <div class="tab active" onclick="switchTab('login')">Entrar</div>
                <div class="tab" onclick="switchTab('register')">Cadastrar</div>
            </div>

            <!-- Login Tab -->
            <div class="tab-content active" id="login-tab">
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="email">📧 E-mail</label>
                        <input type="email" id="email" name="email" required 
                               placeholder="seu@email.com" value="teste@capivaralearn.com">
                    </div>

                    <div class="form-group">
                        <label for="password">🔒 Senha</label>
                        <input type="password" id="password" name="password" required 
                               placeholder="Sua senha" value="123456">
                    </div>

                    <button type="submit" class="btn btn-login">
                        🚀 Entrar no Sistema
                    </button>
                </form>
            </div>

            <!-- Register Tab -->
            <div class="tab-content" id="register-tab">
                <form method="POST">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-group">
                        <label for="reg_nome">👤 Nome Completo</label>
                        <input type="text" id="reg_nome" name="nome" required 
                               placeholder="Seu nome completo">
                    </div>

                    <div class="form-group">
                        <label for="reg_email">📧 E-mail</label>
                        <input type="email" id="reg_email" name="email" required 
                               placeholder="seu@email.com">
                    </div>

                    <div class="form-group">
                        <label for="reg_password">🔒 Senha</label>
                        <input type="password" id="reg_password" name="password" required 
                               placeholder="Mínimo 6 caracteres">
                    </div>

                    <div class="form-group">
                        <label for="reg_confirm_password">🔒 Confirmar Senha</label>
                        <input type="password" id="reg_confirm_password" name="confirm_password" required 
                               placeholder="Digite a senha novamente">
                    </div>

                    <button type="submit" class="btn btn-register">
                        ✨ Criar Conta Gratuita
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Remove active class from all tabs and content
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to selected tab and content
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Clear demo data when switching to register
            if (tabName === 'register') {
                document.getElementById('email').value = '';
                document.getElementById('password').value = '';
            } else {
                // Restore demo data for login
                document.getElementById('email').value = 'teste@capivaralearn.com';
                document.getElementById('password').value = '123456';
            }
        }

        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });

        // Add floating animation to logo on hover
        document.querySelector('.logo-image')?.addEventListener('mouseenter', function() {
            this.style.animation = 'bounce 0.6s ease-in-out';
        });

        document.querySelector('.logo-image')?.addEventListener('animationend', function() {
            this.style.animation = 'bounce 2s ease-in-out infinite';
        });
    </script>
</body>
</html>