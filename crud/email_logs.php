<?php
/**
 * CapivaraLearn - Logs de Email
 * Visualização de logs de envio de email para administradores
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

$database->pdo->exec("SET time_zone = '-03:00'");

function formatEmailLogDateTime(?string $value): ?string {
    if (empty($value)) {
        return null;
    }

    $dateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value, new DateTimeZone(TIMEZONE));

    if ($dateTime === false) {
        return null;
    }

    return $dateTime->format('d/m/Y H:i:s');
}

// ===== FILTROS =====
$filterStatus = $_GET['status'] ?? '';
$filterTipo = $_GET['tipo'] ?? '';
$filterEmail = trim($_GET['email'] ?? '');

$where = [];
if ($filterStatus !== '' && in_array($filterStatus, ['enviado', 'erro', 'pendente'])) {
    $where['status'] = $filterStatus;
}
if ($filterTipo !== '' && in_array($filterTipo, ['confirmacao', 'reset_senha', 'notificacao'])) {
    $where['tipo'] = $filterTipo;
}
if ($filterEmail !== '') {
    $where['destinatario[~]'] = $filterEmail;
}

$where['ORDER'] = ['data_envio' => 'DESC'];
$where['LIMIT'] = 100;

$logs = $database->select('email_log', [
    'id', 'destinatario', 'assunto', 'tipo', 'status', 'erro_detalhes', 'data_envio'
], $where);

// Estatísticas
$totalEnviados = $database->count('email_log', ['status' => 'enviado']);
$totalErros = $database->count('email_log', ['status' => 'erro']);
$totalPendentes = $database->count('email_log', ['status' => 'pendente']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Email - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-header { border-radius: 10px 10px 0 0 !important; }
        .stat-card { border-left: 4px solid; }
        .stat-card.enviados { border-left-color: #198754; }
        .stat-card.erros { border-left-color: #dc3545; }
        .stat-card.pendentes { border-left-color: #ffc107; }
        .erro-details { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; }
        .erro-details:hover { white-space: normal; overflow: visible; }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-envelope-open-text me-2"></i>Logs de Email</h2>
                    <p class="text-muted mb-0">Monitore o envio de emails do sistema</p>
                    <p class="text-muted small mb-0">Horários exibidos em GMT-3 (America/Sao_Paulo)</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= htmlspecialchars(appPath('settings.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-cog me-1"></i>Configurações
                    </a>
                    <a href="users_admin.php" class="btn btn-outline-primary">
                        <i class="fas fa-users-cog me-1"></i>Usuários
                    </a>
                    <a href="../dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-4 mb-2">
            <div class="card stat-card enviados">
                <div class="card-body py-2 d-flex align-items-center">
                    <i class="fas fa-check-circle fa-2x text-success me-3"></i>
                    <div>
                        <div class="text-muted small">Enviados</div>
                        <div class="fw-bold fs-4"><?= $totalEnviados ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card stat-card erros">
                <div class="card-body py-2 d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger me-3"></i>
                    <div>
                        <div class="text-muted small">Erros</div>
                        <div class="fw-bold fs-4"><?= $totalErros ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card stat-card pendentes">
                <div class="card-body py-2 d-flex align-items-center">
                    <i class="fas fa-clock fa-2x text-warning me-3"></i>
                    <div>
                        <div class="text-muted small">Pendentes</div>
                        <div class="fw-bold fs-4"><?= $totalPendentes ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="enviado" <?= $filterStatus === 'enviado' ? 'selected' : '' ?>>Enviado</option>
                        <option value="erro" <?= $filterStatus === 'erro' ? 'selected' : '' ?>>Erro</option>
                        <option value="pendente" <?= $filterStatus === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="confirmacao" <?= $filterTipo === 'confirmacao' ? 'selected' : '' ?>>Confirmação</option>
                        <option value="reset_senha" <?= $filterTipo === 'reset_senha' ? 'selected' : '' ?>>Reset de Senha</option>
                        <option value="notificacao" <?= $filterTipo === 'notificacao' ? 'selected' : '' ?>>Notificação</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Email</label>
                    <input type="text" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($filterEmail) ?>" placeholder="Buscar por email...">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filtrar</button>
                    <a href="email_logs.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times me-1"></i>Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Logs -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <i class="fas fa-list me-2"></i>Registros de Email (últimos 100)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Destinatário</th>
                            <th>Assunto</th>
                            <th>Tipo</th>
                            <th class="text-center">Status</th>
                            <th>Detalhes do Erro</th>
                            <th>Data/Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Nenhum registro encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= intval($log['id']) ?></td>
                                    <td><?= htmlspecialchars($log['destinatario']) ?></td>
                                    <td><?= htmlspecialchars($log['assunto'] ?? '—') ?></td>
                                    <td>
                                        <?php
                                        $tipoBadge = match($log['tipo']) {
                                            'confirmacao' => 'bg-info',
                                            'reset_senha' => 'bg-warning text-dark',
                                            'notificacao' => 'bg-secondary',
                                            default => 'bg-secondary'
                                        };
                                        $tipoLabel = match($log['tipo']) {
                                            'confirmacao' => 'Confirmação',
                                            'reset_senha' => 'Reset Senha',
                                            'notificacao' => 'Notificação',
                                            default => $log['tipo']
                                        };
                                        ?>
                                        <span class="badge <?= $tipoBadge ?>"><?= $tipoLabel ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $statusBadge = match($log['status']) {
                                            'enviado' => 'bg-success',
                                            'erro' => 'bg-danger',
                                            'pendente' => 'bg-warning text-dark',
                                            default => 'bg-secondary'
                                        };
                                        $statusLabel = match($log['status']) {
                                            'enviado' => 'Enviado',
                                            'erro' => 'Erro',
                                            'pendente' => 'Pendente',
                                            default => $log['status']
                                        };
                                        ?>
                                        <span class="badge <?= $statusBadge ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['erro_detalhes'])): ?>
                                            <span class="erro-details text-danger" title="<?= htmlspecialchars($log['erro_detalhes']) ?>">
                                                <i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($log['erro_detalhes']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($log['data_envio']): ?>
                                            <?= htmlspecialchars(formatEmailLogDateTime($log['data_envio']) ?? '—', ENT_QUOTES, 'UTF-8') ?>
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
</body>
</html>
