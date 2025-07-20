<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Load dependencies
require_once 'Medoo.php';
require_once __DIR__ . '/includes/version.php';
require_once __DIR__ . '/includes/services/FinancialServiceSimple.php';

// Database configuration
$database = new Medoo\Medoo([
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'capivaralearn',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
]);

$user_id = $_SESSION['user_id'];

// Initialize Financial Service
$financialService = new FinancialService($database);

// Update subscription status
$financialService->updateSubscriptionStatus($user_id);

// Get current subscription data
$subscription = $financialService->getUserSubscription($user_id);

// Initialize subscription if not exists
if (!$subscription) {
    $result = $financialService->initializeUserSubscription($user_id);
    if ($result['success']) {
        $subscription = $result['subscription'];
        // Reload subscription data with plan details
        $subscription = $financialService->getUserSubscription($user_id);
    }
}

// Get grace period days remaining
$graceDaysRemaining = $financialService->getGracePeriodDaysRemaining($user_id);

// Get payment history
$paymentHistory = $financialService->getPaymentHistory($user_id);

// Calculate dates for display
$today = new DateTime();
$registrationDate = new DateTime($subscription['registration_date']);
$gracePeriodEnd = new DateTime($subscription['grace_period_end']);
$daysUsed = $today->diff($registrationDate)->days;

// Status information
$statusInfo = [
    'grace_period' => [
        'class' => 'success',
        'icon' => 'fas fa-gift',
        'title' => 'Período de Graça Ativo',
        'description' => 'Você está no período gratuito de 365 dias'
    ],
    'payment_due' => [
        'class' => 'warning',
        'icon' => 'fas fa-clock',
        'title' => 'Contribuição Pendente',
        'description' => 'Sua contribuição anual está vencida'
    ],
    'overdue' => [
        'class' => 'danger',
        'icon' => 'fas fa-exclamation-triangle',
        'title' => 'Contribuição em Atraso',
        'description' => 'Sua conta pode ser suspensa em breve'
    ],
    'suspended' => [
        'class' => 'danger',
        'icon' => 'fas fa-ban',
        'title' => 'Conta Suspensa',
        'description' => 'Realize o pagamento para reativar sua conta'
    ],
    'active' => [
        'class' => 'success',
        'icon' => 'fas fa-check-circle',
        'title' => 'Conta Ativa',
        'description' => 'Suas contribuições estão em dia'
    ]
];

$currentStatus = $statusInfo[$subscription['status']] ?? $statusInfo['grace_period'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contribuições Financeiras - CapivaraLearn</title>
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
                            <i class="fas fa-dollar-sign me-3"></i>
                            Contribuições Financeiras
                        </h1>
                        <p class="lead mb-0">Acompanhe sua contribuição para manter o CapivaraLearn sustentável</p>
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
                        <li class="breadcrumb-item active">Contribuições Financeiras</li>
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
                            Status da Conta
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="text-<?php echo $currentStatus['class']; ?>">
                                    <?php echo $currentStatus['title']; ?>
                                </h5>
                                <p class="mb-3"><?php echo $currentStatus['description']; ?></p>
                                
                                <?php if ($subscription['status'] === 'grace_period' && $graceDaysRemaining !== null): ?>
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-clock me-2"></i>Tempo Restante no Período Gratuito</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong><?php echo $graceDaysRemaining; ?> dias restantes</strong><br>
                                                <small>Período gratuito até <?php echo $gracePeriodEnd->format('d/m/Y'); ?></small>
                                            </div>
                                            <div class="col-md-6">
                                                <strong><?php echo $daysUsed; ?> dias utilizados</strong><br>
                                                <small>Desde <?php echo $registrationDate->format('d/m/Y'); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (in_array($subscription['status'], ['payment_due', 'overdue'])): ?>
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Ação Necessária</h6>
                                        <p class="mb-2">Contribuição anual de <strong>USD <?php echo number_format($subscription['amount_due_usd'], 2); ?></strong> está pendente.</p>
                                        <p class="mb-0">Vencimento: <strong><?php echo date('d/m/Y', strtotime($subscription['next_payment_due'])); ?></strong></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-center">
                                <?php if ($subscription['status'] === 'grace_period'): ?>
                                    <!-- Progress Circle -->
                                    <?php 
                                    $progressPercent = min(100, ($daysUsed / 365) * 100);
                                    $circumference = 2 * 3.14159 * 45;
                                    $strokeDasharray = $circumference;
                                    $strokeDashoffset = $circumference - ($progressPercent / 100) * $circumference;
                                    ?>
                                    <svg class="progress-ring" width="120" height="120">
                                        <circle class="progress-ring-circle" 
                                                stroke="#e9ecef" 
                                                stroke-width="8" 
                                                fill="transparent" 
                                                r="45" 
                                                cx="60" 
                                                cy="60"/>
                                        <circle class="progress-ring-circle" 
                                                stroke="#28a745" 
                                                stroke-width="8" 
                                                fill="transparent" 
                                                r="45" 
                                                cx="60" 
                                                cy="60"
                                                stroke-dasharray="<?php echo $strokeDasharray; ?>"
                                                stroke-dashoffset="<?php echo $strokeDashoffset; ?>"/>
                                    </svg>
                                    <div class="mt-2">
                                        <strong><?php echo round($progressPercent); ?>%</strong><br>
                                        <small class="text-muted">do período gratuito usado</small>
                                    </div>
                                <?php else: ?>
                                    <div class="display-1 text-<?php echo $currentStatus['class']; ?>">
                                        <i class="<?php echo $currentStatus['icon']; ?>"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contribution Information -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="contribution-info">
                    <h3 class="mb-4">
                        <i class="fas fa-heart me-3"></i>
                        Por que Contribuir?
                    </h3>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h5><i class="fas fa-server me-2"></i>Infraestrutura</h5>
                            <p class="mb-0">Manutenção de servidores, backup e segurança dos seus dados acadêmicos.</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h5><i class="fas fa-code me-2"></i>Desenvolvimento</h5>
                            <p class="mb-0">Novas funcionalidades, correções e melhorias contínuas na plataforma.</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h5><i class="fas fa-headset me-2"></i>Suporte</h5>
                            <p class="mb-0">Atendimento aos usuários e resolução de problemas técnicos.</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h5><i class="fas fa-leaf me-2"></i>Sustentabilidade</h5>
                            <p class="mb-0">Manter o projeto independente e focado nas necessidades educacionais.</p>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4 p-3 bg-white bg-opacity-25 rounded">
                        <h4 class="mb-2">💝 Apenas USD 1,00 por ano</h4>
                        <p class="mb-0">Menos que uma garrafinha de refrigerante para manter seus estudos organizados!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-timeline me-2"></i>Cronologia da Conta</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline-item completed">
                            <h6 class="mb-1">Conta Criada</h6>
                            <p class="text-muted mb-0"><?php echo $registrationDate->format('d/m/Y'); ?> - Início do período gratuito de 365 dias</p>
                        </div>
                        
                        <?php if ($subscription['status'] === 'grace_period'): ?>
                            <div class="timeline-item current">
                                <h6 class="mb-1">Período Gratuito (Atual)</h6>
                                <p class="text-muted mb-0"><?php echo $graceDaysRemaining; ?> dias restantes até <?php echo $gracePeriodEnd->format('d/m/Y'); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="timeline-item completed">
                                <h6 class="mb-1">Período Gratuito Finalizado</h6>
                                <p class="text-muted mb-0"><?php echo $gracePeriodEnd->format('d/m/Y'); ?> - 365 dias de uso gratuito completados</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="timeline-item <?php echo in_array($subscription['status'], ['payment_due', 'overdue', 'suspended']) ? 'current' : ''; ?>">
                            <h6 class="mb-1">Primeira Contribuição</h6>
                            <p class="text-muted mb-0">
                                <?php if ($subscription['status'] === 'grace_period'): ?>
                                    Prevista para <?php echo $gracePeriodEnd->format('d/m/Y'); ?> - USD 1,00
                                <?php elseif ($subscription['last_payment_date']): ?>
                                    Realizada em <?php echo date('d/m/Y', strtotime($subscription['last_payment_date'])); ?>
                                <?php else: ?>
                                    Pendente desde <?php echo date('d/m/Y', strtotime($subscription['next_payment_due'])); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <?php if (!empty($paymentHistory)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Histórico de Pagamentos</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Tipo</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                        <th>Método</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paymentHistory as $payment): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($payment['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo ucfirst($payment['transaction_type']); ?>
                                            </span>
                                        </td>
                                        <td>USD <?php echo number_format($payment['amount_usd'], 2); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'completed' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                'cancelled' => 'secondary'
                                            ];
                                            $class = $statusClass[$payment['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $payment['payment_method'] ? ucfirst($payment['payment_method']) : '-'; ?></td>
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

        <!-- Actions -->
        <?php if (in_array($subscription['status'], ['payment_due', 'overdue'])): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-custom border-warning">
                    <div class="card-body text-center">
                        <h5 class="text-warning mb-3">
                            <i class="fas fa-credit-card me-2"></i>
                            Realizar Contribuição
                        </h5>
                        <p class="mb-3">Sua contribuição anual de USD 1,00 está pendente.</p>
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <button class="btn btn-warning btn-lg w-100 mb-2" disabled>
                                    <i class="fas fa-credit-card me-2"></i>
                                    Pagar com Cartão (Em Breve)
                                </button>
                                <button class="btn btn-info btn-lg w-100 mb-2" disabled>
                                    <i class="fab fa-paypal me-2"></i>
                                    Pagar com PayPal (Em Breve)
                                </button>
                                <small class="text-muted d-block mt-2">
                                    Os métodos de pagamento serão habilitados em breve
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
                        <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Precisa de Ajuda?</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>💸 Dificuldades Financeiras?</h6>
                                <p class="mb-3">Se você tem dificuldades financeiras, entre em contato conosco para discutir opções de isenção ou desconto.</p>
                            </div>
                            <div class="col-md-6">
                                <h6>❓ Dúvidas sobre Pagamento?</h6>
                                <p class="mb-3">Ficou com alguma dúvida sobre o processo de contribuição? Estamos aqui para ajudar!</p>
                            </div>
                        </div>
                        <div class="text-center">
                            <a href="mailto:support@capivaralearn.com" class="btn btn-outline-info">
                                <i class="fas fa-envelope me-2"></i>
                                Entrar em Contato
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
