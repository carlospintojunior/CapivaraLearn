<?php
// Iniciar sessão explicitamente antes de qualquer coisa
if (session_status() === PHP_SESSION_NONE) {
    // Configurar sessão para desenvolvimento local
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // HTTP local
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Carregar configurações e sistema de logs
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/logger_config.php'; // Novo sistema de logs
require_once __DIR__ . '/includes/log_sistema.php';

// Log do carregamento da página
logInfo('Página de login acessada', [
    'session_id' => session_id(),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
]);

// Garantir fallback para conexão de banco
require_once __DIR__ . '/includes/DatabaseConnection.php';
if (!class_exists('Database') && class_exists('CapivaraLearn\\DatabaseConnection')) {
    class_alias('CapivaraLearn\\DatabaseConnection', 'Database');
}
// Fallback para generateToken()
if (!function_exists('generateToken')) {
    function generateToken() {
        try { return bin2hex(random_bytes(32)); }
        catch (Exception $e) { return md5(uniqid('', true)); }
    }
}
// Configurar envio de email
require_once __DIR__ . '/includes/MailService.php';

// Load Financial Service for user registration
require_once __DIR__ . '/includes/services/FinancialService.php';

// Registrar acesso à página de login
log_sistema('Tela de login carregada', 'INFO');
// Debug de sessão e cookies para entender persistência
log_sistema('Login page load: session_id=' . session_id() . ' | session=' . json_encode($_SESSION) . ' | cookies=' . json_encode($_COOKIE), 'DEBUG');

// Sempre produção: suprimir erros na tela
ini_set('display_errors', 0);
error_reporting(0);

// Registrar manipuladores globais de erros e exceções para capture de fatal errors
set_exception_handler(function (\Throwable $e) {
    log_sistema("Exceção não capturada em login.php: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine(), 'ERROR');
});
set_error_handler(function ($severity, $message, $file, $line) {
    log_sistema("Erro [" . $severity . "] " . $message . " em " . $file . ":" . $line, 'ERROR');
    return false; // permite execução do handler interno do PHP
});
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        log_sistema("Fatal shutdown error: " . $error['message'] . " em " . $error['file'] . ":" . $error['line'], 'ERROR');
    }
});

// Se já estiver logado, redirecionar
log_sistema('Verificando se já está logado: user_id=' . ($_SESSION['user_id'] ?? 'não definido') . ' | isset=' . (isset($_SESSION['user_id']) ? 'true' : 'false'), 'DEBUG');
if (isset($_SESSION['user_id'])) {
    log_sistema('Usuário já autenticado, redirecionando para dashboard: user_id=' . $_SESSION['user_id'], 'INFO');
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Processar login/registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Log da requisição POST
    log_sistema("REQUEST_METHOD=POST, action={$_POST['action']}", 'DEBUG');
    // Usar classe Database definida em config.php
    $db = Database::getInstance();
    $mail = MailService::getInstance();
    
    if ($_POST['action'] === 'login') {
        // Tentativa de login: registrar email
        $attemptEmail = trim($_POST['email'] ?? '');
        log_sistema("Tentativa de login para email: {$attemptEmail}", 'INFO');
        try {
            // Extrair credenciais
            $email = $attemptEmail;
            $password = $_POST['password'] ?? '';
            log_sistema("Credenciais recebidas - email: {$email}, senha_length: " . strlen($password), 'DEBUG');
            
            if (empty($email) || empty($password)) {
                log_sistema("Login falhou - email ou senha vazios", 'WARNING');
                $error = 'E-mail e senha são obrigatórios';
            } else {
                // Consultar usuário no banco
                log_sistema("Buscando usuário no banco: {$email}", 'DEBUG');
                $user = $db->select(
                    "SELECT id, nome, email, senha, ativo, email_verificado FROM usuarios WHERE email = ? AND ativo = 1",
                    [$email]
                );
                log_sistema("Resultado do select usuário: " . var_export($user, true), 'DEBUG');
                
                if (!$user) {
                    log_sistema("Login falhou - usuário não encontrado ou inativo: {$email}", 'WARNING');
                    logWarning('Login falhou - usuário não encontrado', ['email' => $email]);
                    $error = 'E-mail não encontrado ou conta inativa';
                    error_log("Tentativa de login com email não encontrado: $email");
                } elseif (!password_verify($password, $user[0]['senha'])) {
                    log_sistema("Login falhou - senha incorreta para: {$email}", 'WARNING');
                    logWarning('Login falhou - senha incorreta', ['email' => $email]);
                    $error = 'Senha incorreta';
                    error_log("Tentativa de login com senha incorreta para: $email");
                } elseif (!$user[0]['email_verificado']) {
                    log_sistema("Login falhou - email não verificado para: {$email}", 'INFO');
                    logInfo('Login falhou - email não verificado', ['email' => $email]);
                    $error = 'Você precisa confirmar seu email antes de fazer login. Verifique sua caixa de entrada.';
                    $success = '<div class="resend-container">
                        <p>Não recebeu o email? 
                        <a href="?resend_email=' . urlencode($email) . '" class="resend-link">
                        Clique aqui para reenviar</a></p>
                    </div>';
                } else {
                    log_sistema("Login bem-sucedido para: {$email}", 'SUCCESS');
                    logInfo('Login bem-sucedido', [
                        'email' => $email,
                        'user_id' => $user[0]['id'],
                        'user_name' => $user[0]['nome']
                    ]);
                    
                    // DEBUG antes do redirect: conferir sessão e envio de headers
                    log_sistema("Session before redirect: id=" . session_id() . " | session=" . json_encode($_SESSION), 'DEBUG');
                    log_sistema("Headers sent: " . (headers_sent() ? 'yes' : 'no'), 'DEBUG');
                    $_SESSION['user_id'] = $user[0]['id'];
                    $_SESSION['user_name'] = $user[0]['nome'];
                    $_SESSION['user_email'] = $user[0]['email'];
                    $_SESSION['logged_in'] = true;
                    
                    $db->execute("UPDATE usuarios SET data_ultimo_acesso = NOW() WHERE id = ?", [$user[0]['id']]);
                    logActivity($user[0]['id'], 'user_login', "Login realizado: {$user[0]['email']}", $pdo ?? null);
                    
                    // Redirect to dashboard href
                    header('Location: dashboard.php');
                    exit();
                }
            }
        } catch (Exception $e) {
            log_sistema("Exceção no login: " . $e->getMessage(), 'ERROR');
            var_dump($e); // Inspecionar o objeto da exceção
            $error = '⚠️ Erro ao fazer login. Por favor, tente novamente mais tarde.';
            error_log("Erro no login: " . $e->getMessage());
        }
    } elseif ($_POST['action'] === 'register') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $termsAgreement = isset($_POST['terms_agreement']) ? $_POST['terms_agreement'] : '';
        
        // Validações
        if (empty($nome) || empty($email) || empty($password)) {
            $error = 'Nome, e-mail e senha são obrigatórios';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'E-mail inválido';
        } elseif (strlen($password) < 6) {
            $error = 'A senha deve ter pelo menos 6 caracteres';
        } elseif ($password !== $confirmPassword) {
            $error = 'As senhas não coincidem';
        } elseif (empty($termsAgreement)) {
            $error = 'Você deve concordar com os Termos de Uso para criar uma conta';
        } else {
            log_sistema("Tentativa de registro de nova conta para email: $email", 'INFO');
            // Verificar se e-mail já existe
            $existing = $db->select("SELECT id, email_verificado FROM usuarios WHERE email = ?", [$email]);
            
            if ($existing) {
                if ($existing[0]['email_verificado']) {
                    $error = 'E-mail já cadastrado e verificado. Tente fazer login.';
                    log_sistema("Tentativa de registro com email já verificado: $email", 'WARNING');
                } else {
                    $error = 'E-mail já cadastrado mas não verificado. Verifique sua caixa de entrada ou ';
                    $error .= '<a href="?resend_email=' . urlencode($email) . '" style="color: #3498db;">clique aqui para reenviar a confirmação</a>';
                    log_sistema("Tentativa de registro com email já cadastrado mas não verificado: $email", 'WARNING');
                }
            } else {
                // Criar usuário
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    $stmt = $db->getConnection()->prepare(
                        "INSERT INTO usuarios (nome, email, senha, email_verificado, termos_aceitos, data_aceitacao_termos) VALUES (?, ?, ?, FALSE, 1, NOW())"
                    );
                    
                    if ($stmt->execute([$nome, $email, $hashedPassword])) {
                        $userId = $db->getConnection()->lastInsertId();
                        log_sistema("Nova conta criada com sucesso - ID: $userId, Email: $email, Nome: $nome, Termos aceitos: SIM", 'SUCCESS');
                        
                        // Criar configurações padrão
                        $db->execute("INSERT INTO configuracoes_usuario (usuario_id) VALUES (?)", [$userId]);
                        
                        // Initialize community tracking for new user
                        try {
                            $financialService = new FinancialService($db);
                            $trackingResult = $financialService->initializeUserContribution($userId);
                            
                            if ($trackingResult['success']) {
                                log_sistema("Community tracking initialized for user ID: $userId", 'SUCCESS');
                            } else {
                                log_sistema("Failed to initialize community tracking for user ID: $userId - " . ($trackingResult['message'] ?? 'Unknown error'), 'WARNING');
                            }
                        } catch (Exception $e) {
                            log_sistema("Exception initializing community tracking for user ID: $userId - " . $e->getMessage(), 'ERROR');
                        }
                        
                        // Gerar token de confirmação
                        $token = generateToken();
                        $expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));
                        
                        $db->execute(
                            "INSERT INTO email_tokens (usuario_id, token, tipo, data_expiracao, ip_address) VALUES (?, ?, 'confirmacao', ?, ?)",
                            [$userId, $token, $expiration, $_SERVER['REMOTE_ADDR'] ?? null]
                        );
                        
                        log_sistema("Token de confirmação gerado para usuário ID: $userId, Email: $email", 'INFO');
                        
                        // Tentar enviar email
                        if ($mail->sendConfirmationEmail($email, $nome, $token)) {
                            // Log do email enviado
                            $db->execute(
                                "INSERT INTO email_log (destinatario, assunto, tipo, status) VALUES (?, ?, 'confirmacao', 'enviado')",
                                [$email, 'Confirme seu cadastro no CapivaraLearn']
                            );
                            
                            log_sistema("Email de confirmação enviado com sucesso para: $email (Usuario ID: $userId)", 'SUCCESS');
                            
                            $success = 'Conta criada com sucesso! Verifique seu email para confirmar o cadastro.';
                            logActivity($userId, 'user_registered', "Novo usuário registrado: $email", $pdo ?? null);
                        } else {
                            // Capturar erro específico do MailService
                            $mailError = $mail->getLastError();
                            $errorDetails = "Erro SMTP: " . $mailError;
                            
                            log_sistema("ERRO ao enviar email de confirmação para: $email (Usuario ID: $userId) - Erro: $mailError", 'ERROR');
                            
                            // Log do erro de email
                            $db->execute(
                                "INSERT INTO email_log (destinatario, assunto, tipo, status, erro) VALUES (?, ?, 'confirmacao', 'falhou', ?)",
                                [$email, 'Confirme seu cadastro no CapivaraLearn', $errorDetails]
                            );
                            
                            // Log detalhado no arquivo
                            error_log("=== FALHA NO ENVIO DE EMAIL ===");
                            error_log("Email: $email");
                            error_log("Erro: $mailError");
                            error_log("Config SMTP: " . json_encode($mail->getConfig()));
                            
                            // Mostrar erro específico para debug
                            $error = '⚠️ Conta criada, mas houve um problema no envio do email.<br>';
                            $error .= '<strong>Detalhes do erro:</strong> ' . htmlspecialchars($mailError) . '<br>';
                            $error .= '<small>Este erro foi registrado nos logs do sistema.</small>';
                        }
                    } else {
                        $error = 'Erro ao criar conta';
                        log_sistema("ERRO ao criar conta no banco de dados para email: $email", 'ERROR');
                    }
                } catch (Exception $e) {
                    $error = (APP_ENV === 'development') ? 
                        '⚠️ Erro: ' . $e->getMessage() : 
                        '⚠️ Erro interno. Por favor, tente novamente mais tarde.';
                    logActivity(null, 'registration_error', $e->getMessage(), $pdo ?? null);
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

    log_sistema("Solicitação de reenvio de email de confirmação para: $email", 'INFO');

    $user = $db->select("SELECT id, nome, email_verificado FROM usuarios WHERE email = ?", [$email]);

    if ($user && !$user[0]['email_verificado']) {
        log_sistema("Reenvio autorizado para usuário ID: {$user[0]['id']}, Email: $email", 'INFO');
        
        // Invalidar tokens antigos
        $db->execute("UPDATE email_tokens SET usado = TRUE WHERE usuario_id = ? AND tipo = 'confirmacao'", [$user[0]['id']]);

        // Criar novo token
        $token = generateToken();
        $expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $db->execute(
            "INSERT INTO email_tokens (usuario_id, token, tipo, data_expiracao, ip_address) VALUES (?, ?, 'confirmacao', ?, ?)",
            [$user[0]['id'], $token, $expiration, $_SERVER['REMOTE_ADDR'] ?? null]
        );

        log_sistema("Novo token de confirmação gerado para reenvio - Usuario ID: {$user[0]['id']}, Email: $email", 'INFO');

        // Tentar enviar email
        error_log("DEBUG: Iniciando reenvio de email para: $email");
        $start_time = microtime(true);
        
        if ($mail->sendConfirmationEmail($email, $user[0]['nome'], $token)) {
            $end_time = microtime(true);
            error_log("DEBUG: Email reenviado com sucesso em " . round($end_time - $start_time, 2) . " segundos");
            log_sistema("Email de confirmação reenviado com sucesso para: $email (Usuario ID: {$user[0]['id']})", 'SUCCESS');
            $success = 'Email de confirmação reenviado! Verifique sua caixa de entrada.';
        } else {
            $end_time = microtime(true);
            $mailError = $mail->getLastError();
            
            log_sistema("ERRO ao reenviar email de confirmação para: $email (Usuario ID: {$user[0]['id']}) - Erro: $mailError", 'ERROR');
            
            error_log("=== FALHA NO REENVIO DE EMAIL ===");
            error_log("Email: $email");
            error_log("Tempo: " . round($end_time - $start_time, 2) . " segundos");
            error_log("Erro: $mailError");
            error_log("Config SMTP: " . json_encode($mail->getConfig()));
            
            $error = '⚠️ Erro ao reenviar email.<br>';
            $error .= '<strong>Detalhes:</strong> ' . htmlspecialchars($mailError) . '<br>';
            $error .= '<small>Tente novamente em alguns minutos.</small>';
        }
    } else {
        $error = 'Email não encontrado ou já verificado.';
        log_sistema("Tentativa de reenvio negada - Email não encontrado ou já verificado: $email", 'WARNING');
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

        /* Estilo para concordância com termos */
        .terms-agreement {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 15px;
            background: rgba(52, 152, 219, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(52, 152, 219, 0.2);
            margin-bottom: 10px;
        }

        .terms-agreement input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            cursor: pointer;
            accent-color: #3498db;
            flex-shrink: 0;
        }

        .terms-agreement label {
            font-size: 14px;
            line-height: 1.4;
            color: #2c3e50;
            cursor: pointer;
            margin: 0;
        }

        .terms-agreement a {
            color: #3498db;
            text-decoration: underline;
            font-weight: 500;
        }

        .terms-agreement a:hover {
            color: #2980b9;
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

        /* Loading Popup Styles */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-content h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.5em;
        }

        .loading-content p {
            color: #7f8c8d;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .loading-steps {
            text-align: left;
        }

        .step {
            padding: 8px 0;
            color: #bdc3c7;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .step.active {
            color: #27ae60;
            font-weight: bold;
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
            <div class="logo-container">
                <img src="public/assets/images/logo.png" alt="CapivaraLearn" class="logo-image" onerror="this.style.display='none';">
                <div>
                    <h1>CapivaraLearn</h1>
                    <p>Sistema de Organização de Estudos</p>
                    <small style="color: #27ae60; font-weight: 600; margin-top: 0.2rem; display: block;">
                        🌱 100% Gratuito • Sustentável • Sem Anúncios
                    </small>
                </div>
            </div>
        </div>

        <div class="login-form">
            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= $success ?></div>
                <!-- Email Notice - Apenas após sucesso no cadastro -->
                <div class="email-notice">
                    <h5>📧 Sistema de Confirmação por Email</h5>
                    <p>Ao se cadastrar, você receberá um email de confirmação. Verifique também a pasta de spam/lixo eletrônico.</p>
                </div>
            <?php endif; ?>

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
                               placeholder="seu@email.com">
                    </div>

                    <div class="form-group">
                        <label for="password">🔒 Senha</label>
                        <input type="password" id="password" name="password" required 
                               placeholder="Sua senha">
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

                    <!-- Community Support Notice -->
                    <div class="form-group">
                        <div class="contribution-notice">
                            <div style="background: linear-gradient(135deg, #a8e6cf 0%, #dcedc1 100%); padding: 1rem; border-radius: 10px; color: #2c3e50; margin-bottom: 1rem; border-left: 4px solid #27ae60;">
                                <h6 style="margin: 0 0 0.5rem 0; font-weight: bold; color: #27ae60;">
                                    🌱 Sistema 100% Gratuito e Sustentável
                                </h6>
                                <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem; line-height: 1.4;">
                                    • <strong>Sempre gratuito</strong> - Sem anúncios, sem mensalidades<br>
                                    • <strong>Sem limitações</strong> - Acesso completo a todas as funcionalidades<br>
                                    • <strong>Sustentabilidade comunitária</strong> - Após 1 ano, você pode contribuir voluntariamente
                                </p>
                                
                                <!-- Filosofia da contribuição -->
                                <div style="background: rgba(255, 255, 255, 0.4); padding: 0.8rem; border-radius: 8px; margin: 0.8rem 0;">
                                    <p style="margin: 0 0 0.3rem 0; font-size: 0.9rem; font-weight: 600; color: #27ae60;">
                                        � Contribuição voluntária equivale a:
                                    </p>
                                    <p style="margin: 0; font-size: 0.85rem; line-height: 1.4;">
                                        ☕ Um café • 🥤 Uma coca-cola • 🚌 Uma passagem de ônibus<br>
                                        <span style="font-weight: 600; color: #27ae60;">Ajude a manter o sistema funcionando para todos!</span>
                                    </p>
                                </div>
                                
                                <p style="margin: 0; font-size: 0.85rem; opacity: 0.8; font-style: italic;">
                                    💫 Use sem pressa, contribua se quiser - juntos construímos educação gratuita! 🦫
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="terms-agreement">
                            <input type="checkbox" id="terms_agreement" name="terms_agreement" required>
                            <label for="terms_agreement">
                                📋 Concordo com os 
                                <a href="termos_uso.html" target="_blank" style="color: #3498db; text-decoration: underline;">
                                    Termos de Uso
                                </a>
                                e autorizo o tratamento dos meus dados conforme descrito
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-register" onclick="showRegistrationLoader(event)">
                        🌱 Criar Conta Gratuita
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Loading Popup -->
    <div id="loading-popup" style="display: none;">
        <div class="loading-overlay">
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <h3>🦫 Criando sua conta...</h3>
                <p>Aguarde enquanto processamos seu cadastro e enviamos o email de confirmação.</p>
                <div class="loading-steps">
                    <div class="step active" id="step1">✓ Validando dados</div>
                    <div class="step" id="step2">📧 Enviando email</div>
                    <div class="step" id="step3">🎉 Finalizando</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showRegistrationLoader(event) {
            // Verificar se é o formulário de registro
            const form = event.target.closest('form');
            const action = form.querySelector('input[name="action"]').value;
            
            if (action === 'register') {
                // Validação básica antes de mostrar o loader
                const nome = form.querySelector('input[name="nome"]').value.trim();
                const email = form.querySelector('input[name="email"]').value.trim();
                const password = form.querySelector('input[name="password"]').value;
                const confirmPassword = form.querySelector('input[name="confirm_password"]').value;
                const termsAgreement = form.querySelector('input[name="terms_agreement"]').checked;
                
                // Validações
                if (!nome || !email || !password) {
                    alert('Por favor, preencha todos os campos obrigatórios.');
                    event.preventDefault();
                    return false;
                }
                
                if (password.length < 6) {
                    alert('A senha deve ter pelo menos 6 caracteres.');
                    event.preventDefault();
                    return false;
                }
                
                if (password !== confirmPassword) {
                    alert('As senhas não coincidem.');
                    event.preventDefault();
                    return false;
                }
                
                if (!termsAgreement) {
                    alert('Você deve concordar com os Termos de Uso para criar uma conta.');
                    event.preventDefault();
                    return false;
                }
                
                if (!nome || !email || !password || password !== confirmPassword) {
                    return true; // Permite o submit normal para mostrar erros de validação
                }
                
                // Mostrar popup de carregamento
                document.getElementById('loading-popup').style.display = 'block';
                
                // Simular progressão dos steps
                setTimeout(() => {
                    document.getElementById('step2').classList.add('active');
                }, 1000);
                
                setTimeout(() => {
                    document.getElementById('step3').classList.add('active');
                }, 3000);
            }
            
            return true; // Permite o submit do formulário
        }
        
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