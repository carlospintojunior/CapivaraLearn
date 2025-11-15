<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/logger_config.php';
require_once __DIR__ . '/includes/DatabaseConnection.php';

if (!class_exists('Database') && class_exists('CapivaraLearn\\DatabaseConnection')) {
    class_alias('CapivaraLearn\\DatabaseConnection', 'Database');
}

if (function_exists('requireLogin')) {
    requireLogin();
} else {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

$dbInstance = Database::getInstance();
$pdo = $dbInstance->getConnection();
$userId = $_SESSION['user_id'];

$alerts = [
    'errors' => [],
    'success' => []
];

function tableHasColumn(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $key = $table . '.' . $column;
    if (!array_key_exists($key, $cache)) {
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $quotedColumn = $pdo->quote($column);
        $sql = "SHOW COLUMNS FROM `{$tableName}` LIKE {$quotedColumn}";
        $stmt = $pdo->query($sql);
        $cache[$key] = $stmt ? (bool) $stmt->fetch() : false;
    }
    return $cache[$key];
}

function fetchCurrentUser(PDO $pdo, int $userId): ?array {
    $columns = 'id, nome, email, senha, email_verificado, ativo, data_criacao, data_atualizacao';
    if (tableHasColumn($pdo, 'usuarios', 'data_ultimo_acesso')) {
        $columns .= ', data_ultimo_acesso';
    }
    $stmt = $pdo->prepare("SELECT {$columns} FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function formatDateTime(?string $value): string {
    if (empty($value)) {
        return 'Não disponível';
    }
    try {
        $dt = new DateTime($value);
        return $dt->format('d/m/Y H:i');
    } catch (Exception $e) {
        return $value;
    }
}

function dispatchEmailConfirmation(PDO $pdo, int $userId, string $name, string $email): array {
    require_once __DIR__ . '/includes/MailService.php';

    $response = [
        'success' => false,
        'message' => ''
    ];

    try {
        $pdo->prepare("UPDATE email_tokens SET usado = TRUE WHERE usuario_id = ? AND tipo = 'confirmacao'")
            ->execute([$userId]);

        $token = generateToken();
        $expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $pdo->prepare("INSERT INTO email_tokens (usuario_id, token, tipo, data_expiracao, ip_address) VALUES (?, ?, 'confirmacao', ?, ?)")
            ->execute([$userId, $token, $expiration, $_SERVER['REMOTE_ADDR'] ?? null]);

        $mailService = MailService::getInstance();
        if ($mailService->sendConfirmationEmail($email, $name, $token)) {
            $response['success'] = true;
            $response['message'] = 'Enviamos um novo email de confirmação para ' . $email . '.';
        } else {
            $response['message'] = 'Não foi possível enviar o email de confirmação automaticamente. Detalhes: ' . ($mailService->getLastError() ?: 'verifique as configurações SMTP.');
        }
    } catch (Exception $e) {
        $response['message'] = 'Erro ao gerar um novo email de confirmação.';
        logError('Erro ao reenviar email de confirmação', [
            'error' => $e->getMessage(),
            'user_id' => $userId
        ]);
    }

    return $response;
}

$user = fetchCurrentUser($pdo, $userId);

if (!$user) {
    logError('Perfil: usuário não encontrado', ['user_id' => $userId]);
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_profile':
            $name = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if ($name === '' || mb_strlen($name) < 3) {
                $alerts['errors'][] = 'O nome deve ter pelo menos 3 caracteres.';
            }

            if (mb_strlen($name) > 100) {
                $alerts['errors'][] = 'O nome pode ter no máximo 100 caracteres.';
            }

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $alerts['errors'][] = 'Informe um endereço de email válido.';
            }

            $emailChanged = strcasecmp($email, $user['email']) !== 0;

            if ($emailChanged) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id <> ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->fetchColumn() > 0) {
                    $alerts['errors'][] = 'Já existe uma conta utilizando este email.';
                }
            }

            if (empty($alerts['errors'])) {
                $updateData = [
                    'nome' => $name,
                    'email' => $email
                ];

                if ($emailChanged) {
                    $updateData['email_verificado'] = 0;
                }

                $updated = $dbInstance->update('usuarios', $updateData, 'id = :id', [':id' => $userId]);

                if ($updated !== false) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;

                    $alerts['success'][] = 'Informações do perfil atualizadas com sucesso.';
                    logActivity($userId, 'profile_updated', 'Usuário atualizou dados do perfil', $pdo);

                    if ($emailChanged) {
                        $alerts['success'][] = 'O novo email precisa ser confirmado novamente. Veja sua caixa de entrada.';
                        $confirmation = dispatchEmailConfirmation($pdo, $userId, $name, $email);
                        if ($confirmation['success']) {
                            $alerts['success'][] = $confirmation['message'];
                        } else {
                            $alerts['errors'][] = $confirmation['message'];
                        }
                        logActivity($userId, 'email_changed', 'Usuário alterou o email do perfil', $pdo);
                    }
                } else {
                    $alerts['errors'][] = 'Não foi possível atualizar seus dados. Tente novamente mais tarde.';
                }
            }
            break;

        case 'update_password':
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                $alerts['errors'][] = 'Todos os campos de senha são obrigatórios.';
            }

            if (strlen($newPassword) < 8) {
                $alerts['errors'][] = 'A nova senha deve ter pelo menos 8 caracteres.';
            }

            if ($newPassword !== $confirmPassword) {
                $alerts['errors'][] = 'A confirmação da nova senha não confere.';
            }

            if (!password_verify($currentPassword, $user['senha'])) {
                $alerts['errors'][] = 'A senha atual informada está incorreta.';
            }

            if ($currentPassword !== '' && $currentPassword === $newPassword) {
                $alerts['errors'][] = 'A nova senha deve ser diferente da senha atual.';
            }

            if (empty($alerts['errors'])) {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updated = $dbInstance->update('usuarios', ['senha' => $newHash], 'id = :id', [':id' => $userId]);

                if ($updated !== false) {
                    $alerts['success'][] = 'Senha atualizada com sucesso.';
                    logActivity($userId, 'password_updated', 'Usuário alterou a própria senha', $pdo);
                } else {
                    $alerts['errors'][] = 'Não foi possível atualizar sua senha. Tente novamente mais tarde.';
                }
            }
            break;

        case 'resend_confirmation':
            if (!empty($user['email_verificado'])) {
                $alerts['success'][] = 'Seu email já está confirmado.';
            } else {
                $confirmation = dispatchEmailConfirmation($pdo, $userId, $user['nome'], $user['email']);
                if ($confirmation['success']) {
                    $alerts['success'][] = $confirmation['message'];
                    logActivity($userId, 'email_confirmation_resent', 'Usuário solicitou reenvio de confirmação de email', $pdo);
                } else {
                    $alerts['errors'][] = $confirmation['message'];
                }
            }
            break;

        default:
            $alerts['errors'][] = 'Ação inválida recebida. Atualize a página e tente novamente.';
            break;
    }

    $user = fetchCurrentUser($pdo, $userId);
}

$verificationBadgeClass = !empty($user['email_verificado']) ? 'bg-success' : 'bg-warning text-dark';
$verificationBadgeText = !empty($user['email_verificado']) ? 'Email verificado' : 'Confirmação pendente';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            min-height: 100vh;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
        }
        .sidebar .nav-link {
            color: white;
            padding: 15px 20px;
            margin: 5px 0;
            border-radius: 10px;
        }
        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
        }
        .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.2);
        }
        .sidebar-divider {
            border-top: 1px solid rgba(255,255,255,0.2);
            margin: 0.5rem 0;
        }
        .sidebar-nav {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .nav-section-header {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .sidebar-footer {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.2);
            text-align: center;
        }
        .sidebar-version {
            background-color: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.9) !important;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .sidebar-footer .sidebar-copy {
            font-size: 0.7rem;
            color: rgba(255,255,255,0.65);
        }
        .sidebar-footer .contact-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            font-size: 0.7rem;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
        }
        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
        }
        .form-help {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .security-list li {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="text-center mb-4">
                    <h4 class="text-white">CapivaraLearn</h4>
                    <p class="text-white-50">Olá, <?php echo htmlspecialchars($user['nome']); ?>!</p>
                </div>
                <nav class="nav flex-column sidebar-nav">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="crud/universities_simple.php">
                        <i class="fas fa-university me-2"></i>Universidades
                    </a>
                    <a class="nav-link" href="crud/courses_simple.php">
                        <i class="fas fa-graduation-cap me-2"></i>Cursos
                    </a>
                    <a class="nav-link" href="crud/enrollments_simple.php">
                        <i class="fas fa-users me-2"></i>Matrículas
                    </a>
                    <a class="nav-link" href="crud/modules_simple.php">
                        <i class="fas fa-book me-2"></i>Disciplinas
                    </a>
                    <a class="nav-link" href="crud/topics_simple.php">
                        <i class="fas fa-list me-2"></i>Tópicos
                    </a>
                    <a class="nav-link" href="crud/learning_units_simple.php">
                        <i class="fas fa-play-circle me-2"></i>Unidades
                    </a>
                    <div class="sidebar-divider"></div>
                    <div class="nav-section-header text-white-50 px-3 py-2">
                        <small><i class="fas fa-tools me-2"></i>MANUTENÇÃO</small>
                    </div>
                    <a class="nav-link" href="backup_grade.php">
                        <i class="fas fa-download me-2"></i>Backup Grade
                    </a>
                    <a class="nav-link" href="import_grade.php">
                        <i class="fas fa-upload me-2"></i>Importar Grade
                    </a>
                    <a class="nav-link" href="backup_user_data.php">
                        <i class="fas fa-user-shield me-2"></i>Backup Dados
                    </a>
                    <a class="nav-link" href="restore_user_data.php">
                        <i class="fas fa-user-cog me-2"></i>Restaurar Dados
                    </a>
                    <a class="nav-link" href="changelog.php">
                        <i class="fas fa-history me-2"></i>Changelog
                    </a>
                    <div class="sidebar-divider"></div>
                    <a class="nav-link" href="#">
                        <i class="fas fa-cog me-2"></i>Configurações
                    </a>
                    <a class="nav-link active" href="profile.php">
                        <i class="fas fa-user me-2"></i>Minha Conta
                    </a>
                    <a class="nav-link" href="financial_dashboard.php">
                        <i class="fas fa-dollar-sign me-2"></i>Contribuições
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Sair
                    </a>
                </nav>
                <div class="sidebar-footer">
                    <small class="sidebar-version d-block mb-2">
                        <?php 
                        if (class_exists('AppVersion')) {
                            echo AppVersion::getSidebarText();
                        } else {
                            echo 'v1.1.0';
                        }
                        ?>
                    </small>
                    <div class="sidebar-divider opacity-50 my-2"></div>
                    <small class="sidebar-copy d-block">&copy; <?php echo date('Y'); ?> <strong>Carlos Pinto Jr</strong></small>
                    <a href="mailto:capivara@capivaralearn.com.br" class="contact-link mb-2">
                        <i class="fas fa-envelope"></i>
                        capivara@capivaralearn.com.br
                    </a>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="https://github.com/carlospintojunior/CapivaraLearn" target="_blank" class="text-white-50 text-decoration-none">
                            <i class="fab fa-github fa-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-9 col-lg-10 py-4 px-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
                    <div>
                        <h1 class="h3 mb-0">Minha Conta</h1>
                        <p class="text-muted mb-0">Gerencie suas informações pessoais e segurança</p>
                    </div>
                    <div class="text-lg-end">
                        <span class="badge <?php echo $verificationBadgeClass; ?> mb-2 mb-lg-0">
                            <i class="fas fa-shield-alt me-1"></i><?php echo htmlspecialchars($verificationBadgeText); ?>
                        </span>
                        <div class="text-muted small">
                            Última atualização: <?php echo htmlspecialchars(formatDateTime($user['data_atualizacao'] ?? null)); ?>
                        </div>
                    </div>
                </div>

                <?php foreach ($alerts['success'] as $message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>

                <?php foreach ($alerts['errors'] as $message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card card-custom h-100">
                            <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-0"><i class="fas fa-id-card me-2 text-primary"></i>Informações do Perfil</h5>
                                    <small class="text-muted">Nome e email principais usados em todo o sistema</small>
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="row g-3">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="col-12">
                                        <label for="nome" class="form-label">Nome completo</label>
                                        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($user['nome']); ?>" required minlength="3" maxlength="100">
                                        <div class="form-help">Este nome aparece em dashboards, relatórios e logs.</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="email" class="form-label">E-mail</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        <div class="form-help">Utilizado para login, notificações e confirmações.</div>
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Salvar alterações
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card card-custom mb-4">
                            <div class="card-header bg-white border-0">
                                <h6 class="mb-0"><i class="fas fa-circle-info me-2 text-secondary"></i>Status da Conta</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-3">
                                        <small class="text-muted d-block">Situação</small>
                                        <strong><?php echo !empty($user['ativo']) ? 'Ativa' : 'Inativa'; ?></strong>
                                    </li>
                                    <li class="mb-3">
                                        <small class="text-muted d-block">Cadastro</small>
                                        <strong><?php echo htmlspecialchars(formatDateTime($user['data_criacao'] ?? null)); ?></strong>
                                    </li>
                                    <li class="mb-3">
                                        <small class="text-muted d-block">Último acesso</small>
                                        <strong><?php echo htmlspecialchars(formatDateTime($user['data_ultimo_acesso'] ?? null)); ?></strong>
                                    </li>
                                    <li class="mb-0">
                                        <small class="text-muted d-block">Confirmação de email</small>
                                        <strong><?php echo htmlspecialchars($verificationBadgeText); ?></strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <?php if (empty($user['email_verificado'])): ?>
                            <div class="card card-custom">
                                <div class="card-body">
                                    <h6 class="mb-2"><i class="fas fa-envelope-open-text me-2 text-warning"></i>Confirmação pendente</h6>
                                    <p class="text-muted mb-3">Reenvie o link de confirmação caso não encontre o email na sua caixa de entrada.</p>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="resend_confirmation">
                                        <button type="submit" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-paper-plane me-1"></i>Reenviar confirmação
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-lg-8">
                        <div class="card card-custom">
                            <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-0"><i class="fas fa-lock me-2 text-success"></i>Segurança e Senha</h5>
                                    <small class="text-muted">Troque sua senha regularmente para manter a conta protegida</small>
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="row g-3">
                                    <input type="hidden" name="action" value="update_password">
                                    <div class="col-12">
                                        <label for="current_password" class="form-label">Senha atual</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="new_password" class="form-label">Nova senha</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                        <div class="form-help">Mínimo de 8 caracteres, com letras e números.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirmar nova senha</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-key me-1"></i>Atualizar senha
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card card-custom">
                            <div class="card-header bg-white border-0">
                                <h6 class="mb-0"><i class="fas fa-shield-halved me-2 text-success"></i>Dicas rápidas</h6>
                            </div>
                            <div class="card-body">
                                <ul class="security-list mb-0">
                                    <li><i class="fas fa-check-circle text-success me-2"></i>Use senhas únicas e fortes</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i>Mantenha seus dados de contato atualizados</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i>Habilite notificações de prazo para não perder prazos</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i>Entre em contato com o suporte se notar atividade suspeita</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
