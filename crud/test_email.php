<?php
/**
 * CapivaraLearn - Testador de Envio de Email
 * 
 * Página administrativa para testar a configuração SMTP.
 * Permite enviar um email de teste e exibe diagnóstico detalhado.
 * 
 * Acesso restrito a administradores autenticados.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../Medoo.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Medoo\Medoo;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ===== CONTROLE DE ACESSO =====
if (!isset($_SESSION['user_id'])) {
    redirectTo('login.php');
}
if (($_SESSION['user_role'] ?? 'user') !== 'admin') {
    redirectTo('dashboard.php?erro=acesso_negado');
}

$database = new Medoo([
    'type' => 'mysql',
    'host' => DB_HOST,
    'database' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'charset' => 'utf8mb4'
]);

function recordTestEmailLog(Medoo $database, string $destinatario, string $assunto, string $status, ?string $erroDetalhes = null): void {
    try {
        $payload = [
            'destinatario' => $destinatario,
            'assunto' => $assunto,
            'tipo' => 'notificacao',
            'status' => $status
        ];

        if ($erroDetalhes !== null && $erroDetalhes !== '') {
            $payload['erro_detalhes'] = $erroDetalhes;
        }

        $database->insert('email_log', $payload);
    } catch (Throwable $throwable) {
        error_log('Falha ao registrar log do email de teste: ' . $throwable->getMessage());
    }
}

$resultado = null;
$debugLog = [];
$configInfo = [];

// ===== VERIFICAR CONFIGURAÇÃO =====
$configOk = defined('MAIL_HOST') && MAIL_HOST !== ''
          && defined('MAIL_USERNAME') && MAIL_USERNAME !== ''
          && defined('MAIL_PASSWORD') && MAIL_PASSWORD !== '';

$configInfo = [
    'MAIL_HOST'       => defined('MAIL_HOST') ? MAIL_HOST : '❌ NÃO DEFINIDO',
    'MAIL_PORT'       => defined('MAIL_PORT') ? MAIL_PORT : '❌ NÃO DEFINIDO',
    'MAIL_USERNAME'   => defined('MAIL_USERNAME') ? MAIL_USERNAME : '❌ NÃO DEFINIDO',
    'MAIL_PASSWORD'   => defined('MAIL_PASSWORD') && MAIL_PASSWORD !== '' ? '✅ Configurada (oculta)' : '❌ VAZIA',
    'MAIL_FROM_NAME'  => defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : '❌ NÃO DEFINIDO',
    'MAIL_FROM_EMAIL' => defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : '❌ NÃO DEFINIDO',
    'MAIL_SECURE'     => defined('MAIL_SECURE') ? MAIL_SECURE : '❌ NÃO DEFINIDO',
    'MAIL_AUTH'       => defined('MAIL_AUTH') ? (MAIL_AUTH ? 'true' : 'false') : '❌ NÃO DEFINIDO',
];

// ===== PROCESSAR ENVIO DE TESTE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $resultado = ['sucesso' => false, 'mensagem' => 'Token CSRF inválido. Recarregue a página.'];
    } else {
        $emailDestino = filter_var(trim($_POST['email_destino'] ?? ''), FILTER_VALIDATE_EMAIL);
        
        if (!$emailDestino) {
            $resultado = ['sucesso' => false, 'mensagem' => 'Email de destino inválido.'];
        } elseif (!$configOk) {
            $resultado = ['sucesso' => false, 'mensagem' => 'Configuração SMTP incompleta. Verifique o environment.ini.'];
        } else {
            $assunto = '🧪 Teste de Email - CapivaraLearn';

            // Tentar enviar
            try {
                $mail = new PHPMailer(true);
                
                $mail->isSMTP();
                $mail->Host       = MAIL_HOST;
                $mail->SMTPAuth   = MAIL_AUTH;
                $mail->Username   = MAIL_USERNAME;
                $mail->Password   = MAIL_PASSWORD;
                $mail->Port       = MAIL_PORT;
                
                if (MAIL_PORT == 465) {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } else {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }
                
                $mail->Timeout = 30;
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true
                    ]
                ];
                
                // Capturar debug SMTP
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $mail->Debugoutput = function($str, $level) use (&$debugLog) {
                    $debugLog[] = "[SMTP $level] " . trim($str);
                };
                
                $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
                $mail->addReplyTo(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
                $mail->Sender = MAIL_FROM_EMAIL;
                $mail->addAddress($emailDestino);
                
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = $assunto;
                
                $dataHora = date('d/m/Y H:i:s');
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                        <h2>🦫 CapivaraLearn</h2>
                        <p>Email de Teste</p>
                    </div>
                    <div style='background: #f9f9f9; padding: 20px;'>
                        <p>✅ <strong>Este é um email de teste.</strong></p>
                        <p>Se você está lendo isto, a configuração SMTP está funcionando corretamente!</p>
                        <table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                            <tr><td style='padding: 5px; border-bottom: 1px solid #ddd;'><strong>Data/Hora:</strong></td><td style='padding: 5px; border-bottom: 1px solid #ddd;'>$dataHora</td></tr>
                            <tr><td style='padding: 5px; border-bottom: 1px solid #ddd;'><strong>Servidor:</strong></td><td style='padding: 5px; border-bottom: 1px solid #ddd;'>" . htmlspecialchars(MAIL_HOST, ENT_QUOTES, 'UTF-8') . "</td></tr>
                            <tr><td style='padding: 5px; border-bottom: 1px solid #ddd;'><strong>Porta:</strong></td><td style='padding: 5px; border-bottom: 1px solid #ddd;'>" . (int)MAIL_PORT . "</td></tr>
                            <tr><td style='padding: 5px;'><strong>Remetente:</strong></td><td style='padding: 5px;'>" . htmlspecialchars(MAIL_FROM_EMAIL, ENT_QUOTES, 'UTF-8') . "</td></tr>
                        </table>
                    </div>
                    <div style='background: #ecf0f1; padding: 15px; text-align: center; font-size: 12px; color: #7f8c8d; border-radius: 0 0 10px 10px;'>
                        Email enviado automaticamente pelo testador do CapivaraLearn
                    </div>
                </div>";
                $mail->AltBody = "Teste de email - CapivaraLearn\nData: $dataHora\nSe voce esta lendo isto, o SMTP esta funcionando!";
                
                $inicio = microtime(true);
                $mail->send();
                $duracao = round(microtime(true) - $inicio, 2);

                recordTestEmailLog($database, $emailDestino, $assunto, 'enviado');
                
                $resultado = [
                    'sucesso'  => true,
                    'mensagem' => "Email enviado com sucesso para <strong>" . htmlspecialchars($emailDestino, ENT_QUOTES, 'UTF-8') . "</strong> em {$duracao}s"
                ];
                
            } catch (Exception $e) {
                $duracao = isset($inicio) ? round(microtime(true) - $inicio, 2) : 0;
                $detalhesErro = $e->getMessage();
                if (!empty($debugLog)) {
                    $detalhesErro .= " | SMTP: " . implode(" || ", array_slice($debugLog, -10));
                }

                recordTestEmailLog($database, $emailDestino, $assunto, 'erro', mb_substr($detalhesErro, 0, 65535));

                $resultado = [
                    'sucesso'  => false,
                    'mensagem' => 'Falha ao enviar: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . " ({$duracao}s)"
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Email - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .debug-log {
            background: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            padding: 15px;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .config-table td:first-child {
            font-weight: bold;
            white-space: nowrap;
            width: 160px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4" style="max-width: 800px;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>🧪 Testador de Email</h2>
                <small class="text-muted">Subopção de Configurações para diagnóstico SMTP do CapivaraLearn</small>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= htmlspecialchars(appPath('settings.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Configurações
                </a>
                <a href="<?= htmlspecialchars(appPath('crud/email_logs.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
                    📋 Ver Logs de Email
                </a>
            </div>
        </div>

        <!-- Configuração Atual -->
        <div class="card mb-4">
            <div class="card-header">
                <strong>⚙️ Configuração SMTP Atual</strong>
                <span class="badge bg-<?= $configOk ? 'success' : 'danger' ?> ms-2">
                    <?= $configOk ? '✅ Completa' : '❌ Incompleta' ?>
                </span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0 config-table">
                    <tbody>
                        <?php foreach ($configInfo as $chave => $valor): ?>
                        <tr>
                            <td class="ps-3"><?= htmlspecialchars($chave, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-muted small">
                📁 Para alterar, edite o arquivo <code>includes/environment.ini</code> no servidor
            </div>
        </div>

        <!-- Resultado do envio -->
        <?php if ($resultado): ?>
        <div class="alert alert-<?= $resultado['sucesso'] ? 'success' : 'danger' ?> alert-dismissible fade show">
            <strong><?= $resultado['sucesso'] ? '✅ Sucesso!' : '❌ Erro!' ?></strong>
            <?= $resultado['mensagem'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Formulário de teste -->
        <div class="card mb-4">
            <div class="card-header">
                <strong>📧 Enviar Email de Teste</strong>
            </div>
            <div class="card-body">
                <form method="POST" id="formTeste">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label for="email_destino" class="form-label">Email de destino</label>
                        <input type="email" 
                               class="form-control" 
                               id="email_destino" 
                               name="email_destino" 
                               value="<?= htmlspecialchars($_POST['email_destino'] ?? $_SESSION['user_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="seu@email.com"
                               required>
                        <div class="form-text">O email de teste será enviado para este endereço.</div>
                    </div>
                    <button type="submit" 
                            name="enviar_teste" 
                            value="1" 
                            class="btn btn-primary"
                            <?= !$configOk ? 'disabled' : '' ?>
                            id="btnEnviar">
                        📨 Enviar Email de Teste
                    </button>
                    <?php if (!$configOk): ?>
                    <div class="text-danger mt-2 small">
                        ⚠️ Configure o <code>environment.ini</code> antes de testar.
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Log SMTP Debug -->
        <?php if (!empty($debugLog)): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>🔍 Log de Diagnóstico SMTP</strong>
                <span class="badge bg-secondary"><?= count($debugLog) ?> linhas</span>
            </div>
            <div class="card-body p-0">
                <div class="debug-log"><?php
                    foreach ($debugLog as $linha) {
                        echo htmlspecialchars($linha, ENT_QUOTES, 'UTF-8') . "\n";
                    }
                ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Documentação rápida -->
        <div class="card mb-4">
            <div class="card-header">
                <strong>📖 Documentação</strong>
            </div>
            <div class="card-body small">
                <h6>Arquitetura do sistema de email</h6>
                <ul>
                    <li><code>includes/environment.ini</code> — Contém as credenciais SMTP. <strong>Não é versionado no Git.</strong></li>
                    <li><code>includes/config.php</code> — Lê o <code>environment.ini</code> e define as constantes <code>MAIL_*</code>.</li>
                    <li><code>includes/MailService.php</code> — Classe que usa as constantes para enviar emails via PHPMailer.</li>
                </ul>
                <h6>Solução de problemas</h6>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Erro</th><th>Possível causa</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Connection timed out</td><td>Porta 465/587 bloqueada no firewall do servidor</td></tr>
                        <tr><td>Authentication failed</td><td>Senha incorreta no <code>environment.ini</code></td></tr>
                        <tr><td>Certificate verify failed</td><td>Certificado SSL do servidor de email inválido</td></tr>
                        <tr><td>Constantes NÃO DEFINIDO</td><td>Arquivo <code>environment.ini</code> ausente ou mal formatado</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-center mb-4">
            <a href="<?= htmlspecialchars(appPath('dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">← Voltar ao Dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('formTeste')?.addEventListener('submit', function() {
            const btn = document.getElementById('btnEnviar');
            btn.disabled = true;
            btn.innerHTML = '⏳ Enviando...';
        });
    </script>
</body>
</html>
