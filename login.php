<?php
// Incluir a lógica de processamento
require_once 'login_handler.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CapivaraLearn</title>
    <link rel="icon" type="image/png" href="public/assets/images/logo.png">
    <link rel="stylesheet" href="public/assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <!-- Header -->
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

        <!-- Formulário -->
        <div class="login-form">
            <!-- Mensagens de erro/sucesso -->
            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= $success ?></div>
            <?php endif; ?>

            <!-- Avisos informativos -->
            <div class="email-notice">
                <h5>📧 Sistema de Confirmação por Email</h5>
                <p>Ao se cadastrar, você receberá um email de confirmação. Verifique também a pasta de spam/lixo eletrônico.</p>
            </div>

            <div class="demo-login">
                <h4>🧪 Login de Demonstração</h4>
                <p><strong>E-mail:</strong> <code>teste@capivaralearn.com</code></p>
                <p><strong>Senha:</strong> <code>123456</code></p>
            </div>

            <!-- Abas -->
            <div class="tabs">
                <div class="tab active" onclick="switchTab('login')">Entrar</div>
                <div class="tab" onclick="switchTab('register')">Cadastrar</div>
            </div>

            <!-- Aba de Login -->
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

            <!-- Aba de Registro -->
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

    <script src="public/assets/js/login.js"></script>
</body>
</html>