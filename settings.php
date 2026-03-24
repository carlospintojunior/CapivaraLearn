<?php
require_once __DIR__ . '/includes/config.php';

requireLogin();

if (($_SESSION['user_role'] ?? 'user') !== 'admin') {
    redirectTo('dashboard.php?erro=acesso_negado');
}

$cards = [
    [
        'title' => 'Rotina de Backup',
        'description' => 'Gerencie backup do banco, do codigo da aplicacao e dos anexos enviados.',
        'icon' => 'fa-database',
        'href' => appPath('settings_backups.php'),
        'button' => 'Abrir Backups',
        'theme' => 'primary'
    ],
    [
        'title' => 'Teste de Email',
        'description' => 'Teste a configuracao SMTP e valide rapidamente o envio administrativo.',
        'icon' => 'fa-envelope-open-text',
        'href' => appPath('crud/test_email.php'),
        'button' => 'Abrir Teste de Email',
        'theme' => 'success'
    ],
    [
        'title' => 'Logs de Email',
        'description' => 'Acompanhe entregas, erros e diagnosticos registrados pelo sistema.',
        'icon' => 'fa-list-check',
        'href' => appPath('crud/email_logs.php'),
        'button' => 'Abrir Logs',
        'theme' => 'secondary'
    ],
    [
        'title' => 'Backup de Dados do Usuario',
        'description' => 'Exporte o conteudo academico do usuario em JSON para guarda individual.',
        'icon' => 'fa-user-shield',
        'href' => appPath('backup_user_data.php'),
        'button' => 'Abrir Backup de Usuario',
        'theme' => 'info'
    ]
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuracoes - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .hero {
            background: linear-gradient(135deg, #1f4c73, #2a7ab9);
            color: #fff;
            border-radius: 18px;
            padding: 2rem;
            box-shadow: 0 18px 48px rgba(31, 76, 115, 0.18);
        }
        .settings-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(31, 41, 55, 0.08);
            height: 100%;
        }
        .settings-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }
    </style>
</head>
<body>
    <div class="container py-4 py-lg-5">
        <div class="hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
                <div>
                    <h1 class="h3 mb-2"><i class="fas fa-cog me-2"></i>Configuracoes</h1>
                    <p class="mb-0 text-white-50">Centralize manutencao administrativa, email e rotina de backup em um unico menu.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= htmlspecialchars(appPath('dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($cards as $card): ?>
            <div class="col-md-6">
                <div class="card settings-card">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <span class="settings-icon bg-<?= htmlspecialchars($card['theme'], ENT_QUOTES, 'UTF-8') ?> bg-opacity-10 text-<?= htmlspecialchars($card['theme'], ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fas <?= htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                            </span>
                            <div>
                                <h2 class="h5 mb-1"><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="text-muted mb-0"><?= htmlspecialchars($card['description'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                        <a href="<?= htmlspecialchars($card['href'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-<?= htmlspecialchars($card['theme'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($card['button'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>