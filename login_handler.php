<?php
require_once 'includes/config.php';

// Incluir o MailService se não estiver incluído no config
if (!class_exists('MailService')) {
    require_once 'includes/MailService.php';
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
        // LÓGICA DE LOGIN
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'E-mail e senha são obrigatórios';
        } else {
            $user = $db->select(
                "SELECT id, nome, email, senha, ativo, email_verificado FROM usuarios WHERE email = ? AND ativo = 1",
                [$email]
            );
            
            if ($user && password_verify($password, $user[0]['senha'])) {
                // Verificar se email foi confirmado
                if (!$user[0]['email_verificado']) {
                    $error = 'Você precisa confirmar seu email antes de fazer login. Verifique sua caixa de entrada.';
                    $success = '<div class="resend-container">
                        <p>Não recebeu o email? 
                        <a href="?resend_email=' . urlencode($email) . '" class="resend-link">
                        Clique aqui para reenviar</a></p>
                    </div>';
                } else {
                    // Login bem-sucedido
                    $_SESSION['user_id'] = $user[0]['id'];
                    $_SESSION['user_name'] = $user[0]['nome'];
                    $_SESSION['user_email'] = $user[0]['email'];
                    $_SESSION['logged_in'] = true;
                    
                    // Atualizar último acesso
                    $db->execute("UPDATE usuarios SET data_ultimo_acesso = NOW() WHERE id = ?", [$user[0]['id']]);
                    
                    // Log da atividade
                    logActivity('user_login', "Login realizado: {$user[0]['email']}");
                    
                    header('Location: dashboard.php');
                    exit();
                }
            } else {
                $error = 'E-mail ou senha incorretos';
            }
        }
        
    } elseif ($_POST['action'] === 'register') {
        // LÓGICA DE REGISTRO
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Log para debug
        error_log("=== TENTATIVA DE REGISTRO ===");
        error_log("Nome: $nome");
        error_log("Email: $email");
        error_log("Senha length: " . strlen($password));
        
        // Validações
        if (empty($nome) || empty($email) || empty($password)) {
            $error = 'Nome, e-mail e senha são obrigatórios';
            error_log("Erro de validação: campos obrigatórios");
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'E-mail inválido';
            error_log("Erro de validação: email inválido");
        } elseif (strlen($password) < 6) {
            $error = 'A senha deve ter pelo menos 6 caracteres';
            error_log("Erro de validação: senha muito curta");
        } elseif ($password !== $confirmPassword) {
            $error = 'As senhas não coincidem';
            error_log("Erro de validação: senhas não coincidem");
        } else {
            error_log("Validações OK, verificando se email existe...");
            
            // Verificar se e-mail já existe
            $existing = $db->select("SELECT id, email_verificado FROM usuarios WHERE email = ?", [$email]);
            
            if ($existing) {
                error_log("Email já existe: " . print_r($existing, true));
                if ($existing[0]['email_verificado']) {
                    $error = 'E-mail já cadastrado e verificado. Tente fazer login.';
                } else {
                    $error = 'E-mail já cadastrado mas não verificado. Verifique sua caixa de entrada ou ';
                    $error .= '<a href="?resend_email=' . urlencode($email) . '" class="resend-link">clique aqui para reenviar a confirmação</a>';
                }
            } else {
                error_log("Email não existe, criando usuário...");
                
                // Criar usuário
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    // Inserir usuário sem created_at
                    $stmt = $db->getConnection()->prepare(
                        "INSERT INTO usuarios (nome, email, senha, email_verificado) VALUES (?, ?, ?, FALSE)"
                    );
                    
                    if ($stmt->execute([$nome, $email, $hashedPassword])) {
                        $userId = $db->getConnection()->lastInsertId();
                        error_log("Usuário criado com ID: $userId");
                        
                        // Criar configurações padrão (se tabela existir)
                        try {
                            $db->execute("INSERT INTO configuracoes_usuario (usuario_id) VALUES (?)", [$userId]);
                            error_log("Configurações padrão criadas");
                        } catch (Exception $e) {
                            error_log("Aviso: Não foi possível criar configurações padrão: " . $e->getMessage());
                        }
                        
                        // Gerar token de confirmação
                        $token = generateToken();
                        $expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));
                        
                        error_log("Token gerado: $token");
                        
                        // Inserir token sem created_at
                        $tokenInserted = $db->execute(
                            "INSERT INTO email_tokens (usuario_id, token, tipo, data_expiracao, ip_address) VALUES (?, ?, 'confirmacao', ?, ?)",
                            [$userId, $token, $expiration, $_SERVER['REMOTE_ADDR'] ?? null]
                        );
                        
                        if ($tokenInserted) {
                            error_log("Token inserido no banco");
                        } else {
                            error_log("ERRO: Falha ao inserir token no banco");
                        }
                        
                        // Tentar enviar email
                        error_log("Tentando enviar email...");
                        $emailSent = $mail->sendConfirmationEmail($email, $nome, $token);
                        
                        if ($emailSent) {
                            error_log("✅ Email enviado com sucesso!");
                            
                            // Log do email enviado (sem created_at)
                            try {
                                $db->execute(
                                    "INSERT INTO email_log (usuario_id, email_destino, assunto, tipo, status) VALUES (?, ?, ?, 'confirmacao', 'enviado')",
                                    [$userId, $email, 'Confirme seu cadastro no CapivaraLearn']
                                );
                            } catch (Exception $e) {
                                error_log("Aviso: Não foi possível salvar log de email: " . $e->getMessage());
                            }
                            
                            $success = '🎉 Conta criada com sucesso! 📧 Verifique seu email para confirmar o cadastro. (Não esqueça de verificar a pasta de spam!)';
                            logActivity('user_registered', "Novo usuário registrado: $email");
                            
                        } else {
                            error_log("❌ Falha no envio do email: " . $mail->getLastError());
                            
                            // Log do erro de email (sem created_at)
                            try {
                                $db->execute(
                                    "INSERT INTO email_log (usuario_id, email_destino, assunto, tipo, status, erro_detalhes) VALUES (?, ?, ?, 'confirmacao', 'falha', ?)",
                                    [$userId, $email, 'Confirme seu cadastro no CapivaraLearn', $mail->getLastError()]
                                );
                            } catch (Exception $e) {
                                error_log("Aviso: Não foi possível salvar log de erro: " . $e->getMessage());
                            }
                            
                            $success = '✅ Conta criada com sucesso!';
                            $error = '⚠️ Porém, houve um problema no envio do email de confirmação. Entre em contato conosco para ativar sua conta.';
                        }
                    } else {
                        error_log("❌ Erro ao executar INSERT de usuário");
                        $error = 'Erro ao criar conta no banco de dados';
                    }
                } catch (Exception $e) {
                    error_log("❌ Exception ao criar usuário: " . $e->getMessage());
                    $error = 'Erro interno: ' . $e->getMessage();
                    logActivity('registration_error', $e->getMessage());
                }
            }
        }
    }
}

// REENVIAR EMAIL DE CONFIRMAÇÃO
if (isset($_GET['resend_email'])) {
    $email = trim($_GET['resend_email']);
    $db = Database::getInstance();
    $mail = MailService::getInstance();
    
    error_log("=== REENVIO DE EMAIL ===");
    error_log("Email: $email");

    $user = $db->select("SELECT id, nome, email_verificado FROM usuarios WHERE email = ?", [$email]);

    if ($user && !$user[0]['email_verificado']) {
        error_log("Usuário encontrado e não verificado, reenviando...");
        
        // Invalidar tokens antigos
        $db->execute("UPDATE email_tokens SET usado = TRUE WHERE usuario_id = ? AND tipo = 'confirmacao'", [$user[0]['id']]);

        // Criar novo token
        $token = generateToken();
        $expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        error_log("Novo token gerado: $token");

        $tokenInserted = $db->execute(
            "INSERT INTO email_tokens (usuario_id, token, tipo, data_expiracao, ip_address) VALUES (?, ?, 'confirmacao', ?, ?)",
            [$user[0]['id'], $token, $expiration, $_SERVER['REMOTE_ADDR'] ?? null]
        );

        // Tentar enviar email
        if ($tokenInserted && $mail->sendConfirmationEmail($email, $user[0]['nome'], $token)) {
            error_log("✅ Email reenviado com sucesso!");
            $success = '📧 Email de confirmação reenviado com sucesso! Verifique sua caixa de entrada e pasta de spam.';
        } else {
            error_log("❌ Erro ao reenviar email: " . $mail->getLastError());
            $error = 'Erro ao reenviar email. Tente novamente mais tarde ou entre em contato conosco.';
        }
    } else {
        error_log("Email não encontrado ou já verificado");
        $error = 'Email não encontrado ou já verificado.';
    }
    
    error_log("=== FIM REENVIO ===");
}
?>