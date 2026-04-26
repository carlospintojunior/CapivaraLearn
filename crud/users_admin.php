<?php
/**
 * CapivaraLearn - Administração de Usuários
 * Listagem e gerenciamento de usuários cadastrados
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../Medoo.php';

use Medoo\Medoo;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    redirectTo('login.php');
}

// Apenas administradores podem acessar
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

$message = '';
$messageType = '';

// ===== PROCESSAR AÇÕES POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $target_id = intval($_POST['user_id'] ?? 0);

    // Impedir auto-modificação de role/ativo
    if ($target_id === intval($_SESSION['user_id']) && in_array($action, ['toggle_role', 'toggle_ativo'])) {
        $message = 'Você não pode alterar seu próprio status ou papel.';
        $messageType = 'danger';
    } else {
        try {
            switch ($action) {
                case 'toggle_role':
                    $user = $database->get('usuarios', ['role'], ['id' => $target_id]);
                    if ($user) {
                        $newRole = ($user['role'] === 'admin') ? 'user' : 'admin';
                        $database->update('usuarios', ['role' => $newRole], ['id' => $target_id]);
                        $message = "Papel alterado para '{$newRole}' com sucesso.";
                        $messageType = 'success';
                    }
                    break;

                case 'toggle_ativo':
                    $user = $database->get('usuarios', ['ativo'], ['id' => $target_id]);
                    if ($user) {
                        $newStatus = $user['ativo'] ? 0 : 1;
                        $database->update('usuarios', ['ativo' => $newStatus], ['id' => $target_id]);
                        $message = $newStatus ? 'Usuário ativado.' : 'Usuário desativado.';
                        $messageType = 'success';
                    }
                    break;

                case 'verify_email':
                    $user = $database->get('usuarios', ['email_verificado', 'email'], ['id' => $target_id]);
                    if ($user) {
                        if ($user['email_verificado']) {
                            $message = 'Email já está verificado.';
                            $messageType = 'warning';
                        } else {
                            $database->update('usuarios', ['email_verificado' => 1], ['id' => $target_id]);
                            $message = 'Email do usuário verificado manualmente com sucesso.';
                            $messageType = 'success';
                            require_once __DIR__ . '/../includes/log_sistema.php';
                            log_sistema("Admin (ID: {$_SESSION['user_id']}) verificou manualmente o email do usuário ID: {$target_id} ({$user['email']})", 'INFO');
                        }
                    }
                    break;

                case 'resend_email':
                    $user = $database->get('usuarios', ['id', 'nome', 'email', 'email_verificado'], ['id' => $target_id]);
                    if ($user) {
                        if ($user['email_verificado']) {
                            $message = 'Email já está verificado. Não é necessário reenviar.';
                            $messageType = 'warning';
                        } else {
                            require_once __DIR__ . '/../includes/log_sistema.php';
                            require_once __DIR__ . '/../includes/DatabaseConnection.php';
                            if (!class_exists('Database') && class_exists('CapivaraLearn\\DatabaseConnection')) {
                                class_alias('CapivaraLearn\\DatabaseConnection', 'Database');
                            }
                            if (!class_exists('MailService')) {
                                require_once __DIR__ . '/../includes/MailService.php';
                            }
                            if (!function_exists('generateToken')) {
                                function generateToken() {
                                    return bin2hex(random_bytes(32));
                                }
                            }

                            $db = Database::getInstance();
                            $mail = MailService::getInstance();

                            // Invalidar tokens antigos
                            $db->execute("UPDATE email_tokens SET usado = TRUE WHERE usuario_id = ? AND tipo = 'confirmacao'", [$target_id]);

                            // Gerar novo token
                            $token = generateToken();
                            $expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));
                            $db->execute(
                                "INSERT INTO email_tokens (usuario_id, token, tipo, data_expiracao, ip_address) VALUES (?, ?, 'confirmacao', ?, ?)",
                                [$target_id, $token, $expiration, $_SERVER['REMOTE_ADDR'] ?? null]
                            );

                            if ($mail->sendConfirmationEmail($user['email'], $user['nome'], $token)) {
                                $db->execute(
                                    "INSERT INTO email_log (destinatario, assunto, tipo, status) VALUES (?, ?, 'confirmacao', 'enviado')",
                                    [$user['email'], 'Confirme seu cadastro no CapivaraLearn']
                                );
                                $message = "Email de confirmação reenviado para {$user['email']}.";
                                $messageType = 'success';
                                log_sistema("Admin (ID: {$_SESSION['user_id']}) reenviou email de confirmação para usuário ID: {$target_id} ({$user['email']})", 'INFO');
                            } else {
                                $mailError = $mail->getLastError();
                                $db->execute(
                                    "INSERT INTO email_log (destinatario, assunto, tipo, status, erro_detalhes) VALUES (?, ?, 'confirmacao', 'erro', ?)",
                                    [$user['email'], 'Confirme seu cadastro no CapivaraLearn', $mailError]
                                );
                                $message = "Falha ao reenviar email: " . htmlspecialchars($mailError);
                                $messageType = 'danger';
                                log_sistema("Admin (ID: {$_SESSION['user_id']}) tentou reenviar email para usuário ID: {$target_id} ({$user['email']}) - ERRO: {$mailError}", 'ERROR');
                            }
                        }
                    }
                    break;

                case 'send_password_reset':
                    $user = $database->get('usuarios', ['id', 'nome', 'email', 'ativo'], ['id' => $target_id]);
                    if ($user) {
                        require_once __DIR__ . '/../includes/log_sistema.php';
                        require_once __DIR__ . '/../includes/DatabaseConnection.php';
                        if (!class_exists('Database') && class_exists('CapivaraLearn\\DatabaseConnection')) {
                            class_alias('CapivaraLearn\\DatabaseConnection', 'Database');
                        }
                        if (!class_exists('MailService')) {
                            require_once __DIR__ . '/../includes/MailService.php';
                        }
                        if (!function_exists('generateToken')) {
                            function generateToken() {
                                return bin2hex(random_bytes(32));
                            }
                        }

                        $db = Database::getInstance();
                        $mail = MailService::getInstance();

                        // Invalidar tokens antigos de recuperação pendentes
                        $db->execute(
                            "UPDATE email_tokens SET usado = TRUE WHERE usuario_id = ? AND tipo = 'reset_senha' AND usado = FALSE",
                            [$target_id]
                        );

                        $token = generateToken();
                        $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

                        $db->execute(
                            "INSERT INTO email_tokens (usuario_id, token, tipo, data_expiracao, ip_address) VALUES (?, ?, 'reset_senha', ?, ?)",
                            [$target_id, $token, $expiration, $_SERVER['REMOTE_ADDR'] ?? null]
                        );

                        if ($mail->sendPasswordResetEmail($user['email'], $user['nome'], $token)) {
                            $db->execute(
                                "INSERT INTO email_log (destinatario, assunto, tipo, status) VALUES (?, ?, 'reset_senha', 'enviado')",
                                [$user['email'], 'Redefinição de senha - CapivaraLearn']
                            );
                            $message = "Link de recuperação enviado para {$user['email']}.";
                            $messageType = 'success';
                            log_sistema("Admin (ID: {$_SESSION['user_id']}) enviou link de recuperação para usuário ID: {$target_id} ({$user['email']})", 'INFO');
                        } else {
                            $mailError = $mail->getLastError();
                            $db->execute(
                                "INSERT INTO email_log (destinatario, assunto, tipo, status, erro_detalhes) VALUES (?, ?, 'reset_senha', 'erro', ?)",
                                [$user['email'], 'Redefinição de senha - CapivaraLearn', $mailError]
                            );
                            $message = "Falha ao enviar link de recuperação: " . htmlspecialchars($mailError);
                            $messageType = 'danger';
                            log_sistema("Admin (ID: {$_SESSION['user_id']}) tentou enviar link de recuperação para usuário ID: {$target_id} ({$user['email']}) - ERRO: {$mailError}", 'ERROR');
                        }

                        if (!$user['ativo']) {
                            $message .= ' Atenção: o usuário está inativo.';
                            if ($messageType === 'success') {
                                $messageType = 'warning';
                            }
                        }
                    }
                    break;
            }
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// ===== CARREGAR DADOS =====
$orderBy = $_GET['order'] ?? 'nome';
$orderDir = $_GET['dir'] ?? 'ASC';

$allowedOrders = ['id', 'nome', 'email', 'role', 'ativo', 'data_criacao'];
if (!in_array($orderBy, $allowedOrders)) $orderBy = 'nome';
if (!in_array(strtoupper($orderDir), ['ASC', 'DESC'])) $orderDir = 'ASC';

$usuarios = $database->select('usuarios', [
    'id', 'nome', 'email', 'ativo', 'role', 'email_verificado',
    'termos_aceitos', 'data_criacao', 'data_atualizacao'
], [
    'ORDER' => [$orderBy => $orderDir]
]);

$totalUsers = count($usuarios);
$totalAdmin = count(array_filter($usuarios, fn($u) => $u['role'] === 'admin'));
$totalAtivos = count(array_filter($usuarios, fn($u) => $u['ativo']));

// Helper para gerar link de ordenação
function sortLink(string $col, string $label, string $currentOrder, string $currentDir): string {
    $newDir = ($currentOrder === $col && $currentDir === 'ASC') ? 'DESC' : 'ASC';
    $icon = '';
    if ($currentOrder === $col) {
        $icon = $currentDir === 'ASC'
            ? ' <i class="fas fa-sort-up"></i>'
            : ' <i class="fas fa-sort-down"></i>';
    }
    return '<a href="?order=' . $col . '&dir=' . $newDir . '" class="text-decoration-none text-white">'
         . htmlspecialchars($label) . $icon . '</a>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-header { border-radius: 10px 10px 0 0 !important; }
        .table th a { white-space: nowrap; }
        .stat-card { border-left: 4px solid; }
        .stat-card.total { border-left-color: #0d6efd; }
        .stat-card.admin { border-left-color: #dc3545; }
        .stat-card.ativos { border-left-color: #198754; }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-users-cog me-2"></i>Administração de Usuários</h2>
                    <p class="text-muted mb-0">Gerencie os usuários cadastrados no sistema</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="email_logs.php" class="btn btn-outline-info">
                        <i class="fas fa-envelope-open-text me-1"></i>Logs de Email
                    </a>
                    <a href="../dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= in_array($messageType, ['success', 'danger', 'warning', 'info'], true) ? $messageType : 'danger' ?> alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-4 mb-2">
            <div class="card stat-card total">
                <div class="card-body py-2 d-flex align-items-center">
                    <i class="fas fa-users fa-2x text-primary me-3"></i>
                    <div>
                        <div class="text-muted small">Total de Usuários</div>
                        <div class="fw-bold fs-4"><?= $totalUsers ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card stat-card admin">
                <div class="card-body py-2 d-flex align-items-center">
                    <i class="fas fa-user-shield fa-2x text-danger me-3"></i>
                    <div>
                        <div class="text-muted small">Administradores</div>
                        <div class="fw-bold fs-4"><?= $totalAdmin ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card stat-card ativos">
                <div class="card-body py-2 d-flex align-items-center">
                    <i class="fas fa-user-check fa-2x text-success me-3"></i>
                    <div>
                        <div class="text-muted small">Ativos</div>
                        <div class="fw-bold fs-4"><?= $totalAtivos ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Usuários -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <i class="fas fa-list me-2"></i>Usuários Cadastrados
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th><?= sortLink('id', '#', $orderBy, $orderDir) ?></th>
                            <th><?= sortLink('nome', 'Nome', $orderBy, $orderDir) ?></th>
                            <th><?= sortLink('email', 'E-mail', $orderBy, $orderDir) ?></th>
                            <th><?= sortLink('role', 'Papel', $orderBy, $orderDir) ?></th>
                            <th class="text-center"><?= sortLink('ativo', 'Ativo', $orderBy, $orderDir) ?></th>
                            <th class="text-center">E-mail Verificado</th>
                            <th><?= sortLink('data_criacao', 'Cadastro', $orderBy, $orderDir) ?></th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Nenhum usuário encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td><?= intval($u['id']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($u['nome']) ?>
                                        <?php if (intval($u['id']) === intval($_SESSION['user_id'])): ?>
                                            <span class="badge bg-info ms-1">Você</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="badge bg-danger"><i class="fas fa-shield-alt me-1"></i>Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Usuário</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($u['ativo']): ?>
                                            <span class="badge bg-success">Sim</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($u['email_verificado']): ?>
                                            <i class="fas fa-check-circle text-success" title="Verificado"></i>
                                        <?php else: ?>
                                            <span class="d-flex align-items-center justify-content-center gap-1">
                                                <i class="fas fa-times-circle text-danger" title="Não verificado"></i>
                                                <?php if (intval($u['id']) !== intval($_SESSION['user_id'])): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Verificar manualmente o email deste usuário?')">
                                                    <input type="hidden" name="action" value="verify_email">
                                                    <input type="hidden" name="user_id" value="<?= intval($u['id']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success p-0 px-1" title="Verificar email manualmente">
                                                        <i class="fas fa-check fa-xs"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Reenviar email de confirmação para este usuário?')">
                                                    <input type="hidden" name="action" value="resend_email">
                                                    <input type="hidden" name="user_id" value="<?= intval($u['id']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-info p-0 px-1" title="Reenviar email de confirmação">
                                                        <i class="fas fa-paper-plane fa-xs"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($u['data_criacao']): ?>
                                            <?= date('d/m/Y H:i', strtotime($u['data_criacao'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (intval($u['id']) !== intval($_SESSION['user_id'])): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Alterar papel deste usuário?')">
                                                <input type="hidden" name="action" value="toggle_role">
                                                <input type="hidden" name="user_id" value="<?= intval($u['id']) ?>">
                                                <button type="submit" class="btn btn-sm <?= $u['role'] === 'admin' ? 'btn-outline-danger' : 'btn-outline-primary' ?>" title="Alternar papel">
                                                    <i class="fas fa-user-tag"></i>
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('<?= $u['ativo'] ? 'Desativar' : 'Ativar' ?> este usuário?')">
                                                <input type="hidden" name="action" value="toggle_ativo">
                                                <input type="hidden" name="user_id" value="<?= intval($u['id']) ?>">
                                                <button type="submit" class="btn btn-sm <?= $u['ativo'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>">
                                                    <i class="fas <?= $u['ativo'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Enviar link de recuperação de senha para este usuário?')">
                                                <input type="hidden" name="action" value="send_password_reset">
                                                <input type="hidden" name="user_id" value="<?= intval($u['id']) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Enviar link de recuperação de senha">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-dismiss alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(a => {
            new bootstrap.Alert(a).close();
        });
    }, 5000);
</script>
</body>
</html>
