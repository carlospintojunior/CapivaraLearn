<?php
session_start();

// Include configuration
require_once __DIR__ . '/includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Load dependencies
require_once 'Medoo.php';
require_once __DIR__ . '/includes/version.php';
require_once __DIR__ . '/includes/services/FinancialService.php';
require_once __DIR__ . '/includes/log_sistema.php';

// Database configuration
$database = new Medoo\Medoo([
    'type' => 'mysql',
    'host' => DB_HOST,
    'database' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'charset' => 'utf8mb4'
]);

$user_id = $_SESSION['user_id'];

log_sistema("Financial Dashboard acessado por usuário ID: $user_id", 'INFO');

// Initialize Financial Service
$financialService = new FinancialService($database);

// Get current community tracking data
$communityStatus = $financialService->getUserCommunityStatus($user_id);

// Initialize community tracking if not exists
if (!$communityStatus) {
    log_sistema("Inicializando tracking comunitário para usuário ID: $user_id", 'INFO');
    $result = $financialService->initializeUserContribution($user_id);
    if ($result['success']) {
        $communityStatus = $financialService->getUserCommunityStatus($user_id);
        log_sistema("Community tracking inicializado com sucesso para usuário ID: $user_id", 'SUCCESS');
    } else {
        log_sistema("Erro ao inicializar community tracking para usuário ID: $user_id - " . ($result['message'] ?? 'Unknown error'), 'ERROR');
    }
}

// Check if should show contribution request
$shouldShowRequest = $financialService->shouldShowContributionRequest($user_id);

// Get contribution history
$contributionHistory = $financialService->getContributionHistory($user_id);

// Calculate dates for display
$today = new DateTime();
$registrationDate = new DateTime($communityStatus['registration_date']);
$daysUsed = $today->diff($registrationDate)->days;
$yearsPassed = floor($daysUsed / 365);

// Status information for new community model
$statusInfo = [
    'free_access' => [
        'class' => 'success',
        'icon' => 'fas fa-gift',
        'title' => 'Acesso Gratuito Ativo',
        'description' => 'Você está usando o sistema gratuitamente'
    ],
    'contribution_eligible' => [
        'class' => 'info',
        'icon' => 'fas fa-heart',
        'title' => 'Elegível para Contribuir',
        'description' => 'Você pode contribuir voluntariamente para manter o sistema'
    ],
    'active_contributor' => [
        'class' => 'success',
        'icon' => 'fas fa-star',
        'title' => 'Contribuidor Ativo',
        'description' => 'Obrigado por ajudar a manter o sistema funcionando!'
    ]
];

$currentStatus = $statusInfo[$communityStatus['status']] ?? $statusInfo['free_access'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidade e Sustentabilidade - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .status-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .contribution-info {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            border-radius: 15px;
            padding: 2rem;
            color: white;
        }
        .timeline-item {
            border-left: 3px solid #e9ecef;
            padding-left: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #007bff;
        }
        .timeline-item.completed::before {
            background: #28a745;
        }
        .timeline-item.current::before {
            background: #ffc107;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
        }
        .progress-ring {
            transform: rotate(-90deg);
        }
        .progress-ring-circle {
            transition: stroke-dasharray 0.35s;
            transform-origin: 50% 50%;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <div class="card card-custom">
                    <div class="card-body text-center status-card">
                        <h1 class="display-6 mb-3">
                            <i class="fas fa-heart me-3"></i>
                            Comunidade e Sustentabilidade
                        </h1>
                        <p class="lead mb-0">🌱 Sistema 100% Gratuito • Sustentável pela Comunidade • Sem Anúncios</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-white p-3 rounded shadow-sm">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php" class="text-decoration-none">
                                <i class="fas fa-home me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="breadcrumb-item active">Comunidade e Sustentabilidade</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Current Status -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header bg-<?php echo $currentStatus['class']; ?> text-white">
                        <h5 class="mb-0">
                            <i class="<?php echo $currentStatus['icon']; ?> me-2"></i>
                            Status da Sua Participação
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="text-<?php echo $currentStatus['class']; ?>">
                                    <?php echo $currentStatus['title']; ?>
                                </h5>
                                <p class="mb-3"><?php echo $currentStatus['description']; ?></p>
                                
                                <div class="alert alert-success">
                                    <h6><i class="fas fa-calendar me-2"></i>Seu Tempo no CapivaraLearn</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong><?php echo $daysUsed; ?> dias de uso</strong><br>
                                            <small>Desde <?php echo $registrationDate->format('d/m/Y'); ?></small>
                                        </div>
                                        <div class="col-md-6">
                                            <strong><?php echo $yearsPassed; ?> ano(s) completo(s)</strong><br>
                                            <small><?php echo $shouldShowRequest ? 'Elegível para contribuir voluntariamente' : 'Ainda no primeiro ano gratuito'; ?></small>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($shouldShowRequest): ?>
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-heart me-2"></i>Convite para Contribuir</h6>
                                        <p class="mb-2">Você já usa o sistema há mais de um ano! Se quiser e puder, considere fazer uma contribuição voluntária para ajudar a manter o projeto.</p>
                                        <p class="mb-0"><strong>Lembre-se:</strong> A contribuição é completamente opcional. O sistema continuará gratuito independentemente da sua escolha.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="display-1 text-<?php echo $currentStatus['class']; ?>">
                                    <i class="<?php echo $currentStatus['icon']; ?>"></i>
                                </div>
                                <div class="mt-2">
                                    <strong>🦫 CapivaraLearn</strong><br>
                                    <small class="text-muted">Sempre gratuito!</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Community Philosophy -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="contribution-info">
                    <h3 class="mb-4">
                        <i class="fas fa-seedling me-3"></i>
                        Nossa Filosofia de Sustentabilidade
                    </h3>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h5><i class="fas fa-gift me-2"></i>100% Gratuito</h5>
                            <p class="mb-0">O CapivaraLearn sempre será gratuito para todos. Sem limitações, sem anúncios, sem cobrança obrigatória.</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h5><i class="fas fa-users me-2"></i>Sustentado pela Comunidade</h5>
                            <p class="mb-0">Quem pode e quer contribuir voluntariamente ajuda a manter o projeto funcionando para todos.</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h5><i class="fas fa-heart me-2"></i>Contribuição Voluntária</h5>
                            <p class="mb-0">Após 1 ano de uso, você pode escolher contribuir com qualquer valor que considerar justo.</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h5><i class="fas fa-balance-scale me-2"></i>Sem Pressão</h5>
                            <p class="mb-0">Sua experiência no sistema é a mesma, independentemente de contribuir ou não.</p>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4 p-3 bg-white bg-opacity-25 rounded">
                        <h4 class="mb-2">� "Aprender é um direito. Sustentar é um ato de amor."</h4>
                        <p class="mb-0">Uma contribuição equivale a: ☕ Um café • 🥤 Uma coca-cola • 🚌 Uma passagem de ônibus</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-timeline me-2"></i>Sua Jornada no CapivaraLearn</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline-item completed">
                            <h6 class="mb-1">🎉 Conta Criada</h6>
                            <p class="text-muted mb-0"><?php echo $registrationDate->format('d/m/Y'); ?> - Bem-vindo(a) ao CapivaraLearn!</p>
                        </div>
                        
                        <?php if ($yearsPassed < 1): ?>
                            <div class="timeline-item current">
                                <h6 class="mb-1">🌱 Primeiro Ano Gratuito (Atual)</h6>
                                <p class="text-muted mb-0">Aproveitando o sistema sem pressa - você tem <?php echo 365 - $daysUsed; ?> dias até completar 1 ano</p>
                            </div>
                        <?php else: ?>
                            <div class="timeline-item completed">
                                <h6 class="mb-1">🎂 Primeiro Ano Completado</h6>
                                <p class="text-muted mb-0"><?php echo date('d/m/Y', strtotime($registrationDate->format('Y-m-d') . ' +1 year')); ?> - Obrigado por usar o CapivaraLearn!</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($shouldShowRequest): ?>
                            <div class="timeline-item current">
                                <h6 class="mb-1">💝 Elegível para Contribuir Voluntariamente</h6>
                                <p class="text-muted mb-0">
                                    Você pode agora escolher contribuir voluntariamente se quiser ajudar a sustentar o projeto
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="timeline-item">
                                <h6 class="mb-1">💝 Futuras Contribuições Voluntárias</h6>
                                <p class="text-muted mb-0">
                                    Após 1 ano de uso, você poderá escolher contribuir voluntariamente (sem obrigação)
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contribution History -->
        <?php if (!empty($contributionHistory)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-heart me-2"></i>Histórico de Contribuições Voluntárias</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                        <th>Método</th>
                                        <th>Mensagem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contributionHistory as $contribution): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($contribution['created_at'])); ?></td>
                                        <td>USD <?php echo number_format($contribution['amount_usd'], 2); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'completed' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                'cancelled' => 'secondary'
                                            ];
                                            $class = $statusClass[$contribution['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>">
                                                <?php echo ucfirst($contribution['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $contribution['payment_method'] ? ucfirst($contribution['payment_method']) : '-'; ?></td>
                                        <td><?php echo $contribution['message'] ?? 'Contribuição voluntária'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Voluntary Contribution Actions -->
        <?php if ($shouldShowRequest): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-custom border-success">
                    <div class="card-body text-center">
                        <h5 class="text-success mb-3">
                            <i class="fas fa-heart me-2"></i>
                            Contribuição Voluntária
                        </h5>
                        <p class="mb-3">Se você quiser e puder, uma contribuição voluntária ajuda a manter o CapivaraLearn funcionando para toda a comunidade.</p>
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="alert alert-info mb-3">
                                    <small>
                                        <i class="fas fa-info-circle me-1"></i>
                                        <strong>Lembre-se:</strong> A contribuição é totalmente voluntária. 
                                        O sistema continuará gratuito para você independentemente da sua escolha.
                                    </small>
                                </div>
                                <button class="btn btn-success btn-lg w-100 mb-2" disabled>
                                    <i class="fas fa-heart me-2"></i>
                                    Contribuir Voluntariamente (Em Breve)
                                </button>
                                <small class="text-muted d-block mt-2">
                                    Os métodos de contribuição serão habilitados em breve
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Support -->
        <div class="row">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Dúvidas ou Sugestões?</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>🌱 Sobre a Sustentabilidade</h6>
                                <p class="mb-3">Quer entender melhor como funciona nosso modelo de sustentabilidade comunitária? Estamos aqui para esclarecer!</p>
                            </div>
                            <div class="col-md-6">
                                <h6>💡 Sugestões e Ideias</h6>
                                <p class="mb-3">Tem alguma ideia para melhorar o CapivaraLearn? Adoramos ouvir feedback da nossa comunidade!</p>
                            </div>
                        </div>
                        <div class="text-center">
                            <a href="mailto:support@capivaralearn.com" class="btn btn-outline-info">
                                <i class="fas fa-envelope me-2"></i>
                                Entrar em Contato
                            </a>
                        </div>
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                🦫 <strong>CapivaraLearn</strong> - Educação gratuita e sustentável para todos
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
