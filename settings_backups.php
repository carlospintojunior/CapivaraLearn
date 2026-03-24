<?php
require_once __DIR__ . '/includes/config.php';

requireLogin();

if (($_SESSION['user_role'] ?? 'user') !== 'admin') {
    redirectTo('dashboard.php?erro=acesso_negado');
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$backupDir = __DIR__ . '/backup/system_backups';
$backupScript = __DIR__ . '/scripts/create_system_backup.sh';
$cronFileCandidates = [
    '/etc/cron.d/capivaralearn-system-backup',
    '/etc/cron.d/capivaralearn-backup'
];
$flash = null;
$scriptOutput = [];

function formatBackupBytes(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $size = $bytes;
    $unitIndex = 0;

    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }

    return number_format($size, $unitIndex === 0 ? 0 : 2, ',', '.') . ' ' . $units[$unitIndex];
}

function getBackupArchives(string $backupDir): array {
    if (!is_dir($backupDir)) {
        return [];
    }

    $files = glob($backupDir . '/capivaralearn_system_backup_*.tar.gz') ?: [];
    rsort($files, SORT_STRING);

    return array_map(static function (string $file): array {
        return [
            'name' => basename($file),
            'path' => $file,
            'size' => filesize($file) ?: 0,
            'modified_at' => filemtime($file) ?: 0,
        ];
    }, $files);
}

function getReadableCronFile(array $candidates): ?string {
    foreach ($candidates as $candidate) {
        if (is_readable($candidate)) {
            return $candidate;
        }
    }

    return null;
}

if (isset($_GET['download'])) {
    $filename = basename((string) $_GET['download']);
    $archivePath = realpath($backupDir . '/' . $filename);
    $backupDirRealPath = realpath($backupDir);

    if ($archivePath === false || $backupDirRealPath === false || strpos($archivePath, $backupDirRealPath . DIRECTORY_SEPARATOR) !== 0 || !is_file($archivePath)) {
        http_response_code(404);
        exit('Arquivo de backup nao encontrado.');
    }

    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . basename($archivePath) . '"');
    header('Content-Length: ' . filesize($archivePath));
    readfile($archivePath);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $flash = ['type' => 'danger', 'message' => 'Token CSRF invalido. Recarregue a pagina.'];
    } elseif (($_POST['action'] ?? '') === 'create_backup') {
        if (!is_file($backupScript)) {
            $flash = ['type' => 'danger', 'message' => 'Script de backup nao encontrado em scripts/create_system_backup.sh.'];
        } else {
            $command = '/bin/bash ' . escapeshellarg($backupScript) . ' 2>&1';
            exec($command, $scriptOutput, $exitCode);

            if ($exitCode === 0) {
                $flash = ['type' => 'success', 'message' => 'Backup do sistema gerado com sucesso.'];
            } else {
                $flash = ['type' => 'danger', 'message' => 'Falha ao gerar backup do sistema. Revise a saida abaixo.'];
            }
        }
    }
}

$archives = getBackupArchives($backupDir);
$latestArchive = $archives[0] ?? null;
$cronFile = getReadableCronFile($cronFileCandidates);
$cronContents = $cronFile ? trim((string) file_get_contents($cronFile)) : null;
$cronCommand = '0 2 * * * www-data /bin/bash ' . __DIR__ . '/scripts/create_system_backup.sh >> ' . __DIR__ . '/logs/backup_routine.log 2>&1';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rotina de Backup - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb; }
        .panel {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }
        .metric {
            border-radius: 16px;
            padding: 1.25rem;
            background: #fff;
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.18);
            height: 100%;
        }
        pre.command-box {
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            white-space: pre-wrap;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <div class="container py-4 py-lg-5">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="fas fa-database me-2"></i>Rotina de Backup</h1>
                <p class="text-muted mb-0">Proteja banco, codigo e anexos enviados em um pacote unico versionado por data.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= htmlspecialchars(appPath('settings.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Configuracoes
                </a>
                <a href="<?= htmlspecialchars(appPath('dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-dark">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="metric">
                    <div class="text-muted small mb-2">Ultimo backup</div>
                    <div class="fw-semibold fs-5"><?= $latestArchive ? date('d/m/Y H:i:s', $latestArchive['modified_at']) : 'Nenhum backup ainda' ?></div>
                    <div class="text-muted small mt-2"><?= $latestArchive ? htmlspecialchars($latestArchive['name'], ENT_QUOTES, 'UTF-8') : 'Execute a rotina manual para gerar o primeiro pacote.' ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric">
                    <div class="text-muted small mb-2">Pacotes disponiveis</div>
                    <div class="fw-semibold fs-5"><?= count($archives) ?></div>
                    <div class="text-muted small mt-2">Retencao automatica no script: 30 dias e ate 15 arquivos recentes.</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric">
                    <div class="text-muted small mb-2">Rotina automatica</div>
                    <div class="fw-semibold fs-5"><?= $cronFile ? 'Configurada' : 'Pendente' ?></div>
                    <div class="text-muted small mt-2"><?= $cronFile ? htmlspecialchars($cronFile, ENT_QUOTES, 'UTF-8') : 'Instale a entrada de cron sugerida abaixo.' ?></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card panel">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3"><i class="fas fa-play-circle me-2"></i>Executar Backup Agora</h2>
                        <p class="text-muted">O pacote gerado inclui dump SQL do banco, snapshot do codigo da aplicacao e anexos enviados em testes especiais.</p>
                        <form method="POST" class="d-flex gap-2 flex-wrap">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="create_backup">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-box-archive me-2"></i>Gerar Backup Completo
                            </button>
                            <?php if ($latestArchive): ?>
                            <a href="?download=<?= rawurlencode($latestArchive['name']) ?>" class="btn btn-outline-success">
                                <i class="fas fa-download me-2"></i>Baixar Ultimo Backup
                            </a>
                            <?php endif; ?>
                        </form>
                        <?php if (!empty($scriptOutput)): ?>
                        <div class="mt-3">
                            <label class="form-label fw-semibold">Saida da execucao</label>
                            <pre class="command-box mb-0"><?= htmlspecialchars(implode(PHP_EOL, $scriptOutput), ENT_QUOTES, 'UTF-8') ?></pre>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card panel h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3"><i class="fas fa-clock me-2"></i>Rotina Automatica</h2>
                        <p class="text-muted">Use esta entrada no cron do servidor para gerar backup diario as 02:00 com log proprio.</p>
                        <pre class="command-box"><?= htmlspecialchars($cronCommand, ENT_QUOTES, 'UTF-8') ?></pre>
                        <?php if ($cronContents): ?>
                        <div class="mt-3">
                            <div class="small text-muted mb-2">Configuracao detectada</div>
                            <pre class="command-box mb-0"><?= htmlspecialchars($cronContents, ENT_QUOTES, 'UTF-8') ?></pre>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card panel mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3"><i class="fas fa-shield-halved me-2"></i>Escopo do Backup</h2>
                <div class="row g-3">
                    <div class="col-md-4"><div class="metric"><strong>Banco</strong><div class="text-muted small mt-2">Dump SQL completo via mysqldump usando as credenciais do environment.ini.</div></div></div>
                    <div class="col-md-4"><div class="metric"><strong>Codigo</strong><div class="text-muted small mt-2">Snapshot do projeto com exclusao do proprio historico de backups, logs e cache.</div></div></div>
                    <div class="col-md-4"><div class="metric"><strong>Anexos</strong><div class="text-muted small mt-2">Midias de testes especiais em public/assets/videos e public/assets/images.</div></div></div>
                </div>
            </div>
        </div>

        <div class="card panel">
            <div class="card-body p-0">
                <div class="p-4 border-bottom">
                    <h2 class="h5 mb-1"><i class="fas fa-folder-open me-2"></i>Pacotes Gerados</h2>
                    <p class="text-muted mb-0">Arquivos salvos em backup/system_backups.</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Arquivo</th>
                                <th>Tamanho</th>
                                <th>Gerado em</th>
                                <th class="text-end">Acao</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($archives)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Nenhum backup completo foi gerado ainda.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($archives, 0, 15) as $archive): ?>
                                <tr>
                                    <td><?= htmlspecialchars($archive['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(formatBackupBytes($archive['size']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= date('d/m/Y H:i:s', $archive['modified_at']) ?></td>
                                    <td class="text-end">
                                        <a href="?download=<?= rawurlencode($archive['name']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download me-1"></i>Baixar
                                        </a>
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
</body>
</html>