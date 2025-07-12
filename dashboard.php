<?php
// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Carregar sistema de logs
require_once __DIR__ . '/includes/logger_config.php';

// Log do acesso ao dashboard
logInfo('Dashboard acessado', [
    'user_id' => $_SESSION['user_id'],
    'user_name' => $_SESSION['user_name'] ?? 'unknown',
    'session_id' => session_id()
]);

// Configuração do Medoo
require_once 'Medoo.php';
use Medoo\Medoo;

$database = new Medoo([
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'capivaralearn',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
]);

$user_id = $_SESSION['user_id'];

// Função para calcular dias até prazo
function diasAtePrazo($data_prazo) {
    if (!$data_prazo) return null;
    $hoje = new DateTime();
    $prazo = new DateTime($data_prazo);
    $diff = $hoje->diff($prazo);
    return $prazo < $hoje ? -$diff->days : $diff->days;
}

// Função para status do prazo
function statusPrazo($dias) {
    if ($dias === null) return ['class' => 'sem-prazo', 'texto' => 'Sem prazo'];
    if ($dias < 0) return ['class' => 'atrasado', 'texto' => abs($dias) . ' dias atrasado'];
    if ($dias == 0) return ['class' => 'hoje', 'texto' => 'Vence hoje!'];
    if ($dias <= 3) return ['class' => 'urgente', 'texto' => $dias . ' dias'];
    if ($dias <= 7) return ['class' => 'atencao', 'texto' => $dias . ' dias'];
    return ['class' => 'normal', 'texto' => $dias . ' dias'];
}

// Função para formatar data
function formatarData($data) {
    if (!$data) return 'Não definido';
    return date('d/m/Y', strtotime($data));
}

// Buscar dados do usuário
$user = $database->get("usuarios", "*", ["id" => $user_id]);

// Estatísticas gerais
$stats = [
    'universidades' => $database->count("universidades", ["usuario_id" => $user_id]),
    'cursos' => $database->count("cursos", ["usuario_id" => $user_id]),
    'disciplinas' => $database->count("disciplinas", ["usuario_id" => $user_id]),
    'topicos' => $database->count("topicos", ["usuario_id" => $user_id]),
    'unidades' => $database->count("unidades_aprendizagem", ["usuario_id" => $user_id]),
    'matriculas' => $database->count("inscricoes", ["usuario_id" => $user_id])
];

// Tópicos com prazos próximos (próximos 7 dias)
$topicos_urgentes = $database->select("topicos", [
    "[>]disciplinas" => ["disciplina_id" => "id"]
], [
    "topicos.id",
    "topicos.nome",
    "topicos.data_prazo",
    "disciplinas.nome(disciplina_nome)"
], [
    "topicos.usuario_id" => $user_id,
    "topicos.data_prazo[!]" => null,
    "topicos.data_prazo[<=]" => date('Y-m-d', strtotime('+7 days')),
    "ORDER" => ["topicos.data_prazo" => "ASC"]
]);

// Tópicos atrasados
$topicos_atrasados = $database->select("topicos", [
    "[>]disciplinas" => ["disciplina_id" => "id"]
], [
    "topicos.id",
    "topicos.nome", 
    "topicos.data_prazo",
    "disciplinas.nome(disciplina_nome)"
], [
    "topicos.usuario_id" => $user_id,
    "topicos.data_prazo[<]" => date('Y-m-d'),
    "ORDER" => ["topicos.data_prazo" => "ASC"]
]);

// Progresso das disciplinas - Corrigido para evitar error COUNT
$disciplinas_progresso = $database->select("disciplinas", [
    "id",
    "nome"
], [
    "usuario_id" => $user_id,
    "ORDER" => "nome"
]);

// Para cada disciplina, contar tópicos separadamente
foreach ($disciplinas_progresso as &$disciplina) {
    $total_topicos = $database->count("topicos", [
        "disciplina_id" => $disciplina['id'],
        "usuario_id" => $user_id
    ]);
    $disciplina['total_topicos'] = $total_topicos;
}

// Unidades de aprendizagem com melhores notas
$melhores_unidades = $database->select("unidades_aprendizagem", [
    "[>]topicos" => ["topico_id" => "id"],
    "[>]disciplinas" => ["topicos.disciplina_id" => "id"]
], [
    "unidades_aprendizagem.nome",
    "unidades_aprendizagem.nota",
    "topicos.nome(topico_nome)",
    "disciplinas.nome(disciplina_nome)"
], [
    "unidades_aprendizagem.usuario_id" => $user_id,
    "unidades_aprendizagem.nota[>]" => 0,
    "ORDER" => ["unidades_aprendizagem.nota" => "DESC"],
    "LIMIT" => 5
]);

// Atividades recentes
$atividades_recentes = $database->select("unidades_aprendizagem", [
    "[>]topicos" => ["topico_id" => "id"],
    "[>]disciplinas" => ["topicos.disciplina_id" => "id"]
], [
    "unidades_aprendizagem.nome",
    "unidades_aprendizagem.nota",
    "unidades_aprendizagem.data_atualizacao",
    "topicos.nome(topico_nome)",
    "disciplinas.nome(disciplina_nome)"
], [
    "unidades_aprendizagem.usuario_id" => $user_id,
    "ORDER" => ["unidades_aprendizagem.data_atualizacao" => "DESC"],
    "LIMIT" => 5
]);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CapivaraLearn</title>
    <link rel="icon" type="image/png" href="public/assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
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
            padding: 0;
            transition: all 0.3s ease;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            font-weight: bold;
            font-size: 1.2em;
        }

        .sidebar .logo img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
        }

        .logo-text {
            transition: opacity 0.3s ease;
        }

        .sidebar.collapsed .logo-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 5px;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .sidebar-toggle span {
            width: 18px;
            height: 2px;
            background: white;
            transition: all 0.3s ease;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1rem 0;
        }

        .nav-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-right: 3px solid #667eea;
        }

        .nav-icon {
            font-size: 1.3em;
            margin-right: 1rem;
            min-width: 25px;
            text-align: center;
        }

        .nav-text {
            transition: opacity 0.3s ease;
        }

        .sidebar.collapsed .nav-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        .sidebar.collapsed .nav-link {
            padding: 1rem 1.2rem;
            justify-content: center;
        }

        .sidebar.collapsed .nav-icon {
            margin-right: 0;
        }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-footer .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
        }

        .sidebar-footer .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2em;
            flex-shrink: 0;
        }

        .user-details {
            flex: 1;
            transition: opacity 0.3s ease;
        }

        .user-name {
            display: block;
            font-weight: bold;
            font-size: 0.9em;
        }

        .user-role {
            display: block;
            font-size: 0.8em;
            color: rgba(255, 255, 255, 0.7);
        }

        .sidebar.collapsed .user-details {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        .sidebar-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .action-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            padding: 0.5rem;
            border-radius: 50%;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 280px;
            padding: 0;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-left: 70px;
        }

        .main-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            color: white;
            font-size: 1.8em;
            font-weight: bold;
            margin: 0;
        }

        .welcome-text {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1em;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5em;
            cursor: pointer;
            padding: 0.5rem;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem;
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1em;
        }

        .card-body {
            padding: 1.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            border-color: #667eea;
            transform: scale(1.05);
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            display: block;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin-top: 0.5rem;
        }

        .prazo-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 12px;
            border-left: 4px solid transparent;
        }

        .prazo-item.atrasado {
            background: #fff5f5;
            border-left-color: #e53e3e;
        }

        .prazo-item.hoje {
            background: #fffbeb;
            border-left-color: #d69e2e;
        }

        .prazo-item.urgente {
            background: #fef5e7;
            border-left-color: #dd6b20;
        }

        .prazo-item.atencao {
            background: #f0fff4;
            border-left-color: #38a169;
        }

        .prazo-item.normal {
            background: #f7fafc;
            border-left-color: #4299e1;
        }

        .prazo-item.sem-prazo {
            background: #f8f9fa;
            border-left-color: #a0aec0;
        }

        .prazo-info h6 {
            margin: 0;
            color: #2d3748;
        }

        .prazo-info small {
            color: #718096;
        }

        .prazo-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-align: center;
            min-width: 80px;
        }

        .prazo-status.atrasado {
            background: #fed7d7;
            color: #c53030;
        }

        .prazo-status.hoje {
            background: #fef5e7;
            color: #d69e2e;
        }

        .prazo-status.urgente {
            background: #fbd38d;
            color: #c05621;
        }

        .prazo-status.atencao {
            background: #c6f6d5;
            color: #2f855a;
        }

        .prazo-status.normal {
            background: #bee3f8;
            color: #2b6cb0;
        }

        .prazo-status.sem-prazo {
            background: #e2e8f0;
            color: #4a5568;
        }

        .progress-container {
            margin-bottom: 1rem;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .progress {
            height: 12px;
            border-radius: 6px;
            background: #e2e8f0;
        }

        .progress-bar {
            border-radius: 6px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .nota-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            color: white;
        }

        .nota-excelente {
            background: linear-gradient(135deg, #48bb78, #38a169);
        }

        .nota-boa {
            background: linear-gradient(135deg, #4299e1, #3182ce);
        }

        .nota-regular {
            background: linear-gradient(135deg, #ed8936, #dd6b20);
        }

        .nota-baixa {
            background: linear-gradient(135deg, #f56565, #e53e3e);
        }

        .atividade-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }

        .atividade-info h6 {
            margin: 0;
            font-size: 0.9em;
        }

        .atividade-info small {
            color: #666;
        }

        .atividade-data {
            font-size: 0.8em;
            color: #666;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3em;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .quick-action {
            flex: 1;
            min-width: 120px;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s ease;
            font-weight: bold;
        }

        .quick-action:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 280px;
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .main-content.expanded {
                margin-left: 0;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quick-actions {
                flex-direction: column;
            }
            
            .container {
                padding: 0 1rem;
            }
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Overlay para mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Menu Lateral -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <img src="public/assets/images/logo.png" alt="CapivaraLearn" onerror="this.style.display='none';">
                <span class="logo-text">CapivaraLearn</span>
            </div>
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="nav-icon">📊</i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="crud/universities_simple.php" class="nav-link">
                        <i class="nav-icon">🏛️</i>
                        <span class="nav-text">Universidades</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="crud/courses_simple.php" class="nav-link">
                        <i class="nav-icon">🎓</i>
                        <span class="nav-text">Cursos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="crud/enrollments_simple.php" class="nav-link">
                        <i class="nav-icon">🎯</i>
                        <span class="nav-text">Matrículas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="crud/modules_simple.php" class="nav-link">
                        <i class="nav-icon">📚</i>
                        <span class="nav-text">Disciplinas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="crud/topics_simple.php" class="nav-link">
                        <i class="nav-icon">📝</i>
                        <span class="nav-text">Tópicos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="crud/learning_units_simple.php" class="nav-link">
                        <i class="nav-icon">🧩</i>
                        <span class="nav-text">Unidades</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($user['nome'], 0, 1)) ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?= htmlspecialchars($user['nome']) ?></span>
                    <span class="user-role">Usuário</span>
                </div>
            </div>
            <div class="sidebar-actions">
                <a href="#" class="action-btn" title="Perfil">👤</a>
                <a href="#" class="action-btn" title="Configurações">⚙️</a>
                <a href="logout.php" class="action-btn" title="Sair">🚪</a>
            </div>
        </div>
    </div>

    <!-- Header Principal -->
    <div class="main-header">
        <div class="header-content">
            <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
            <h1 class="page-title">Dashboard</h1>
            <div class="header-actions">
                <span class="welcome-text">Bem-vindo, <?= htmlspecialchars($user['nome']) ?>!</span>
            </div>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="main-content" id="mainContent">
        <div class="container">
        <!-- Estatísticas Gerais -->
        <div class="card mb-4">
            <div class="card-header">
                📊 Visão Geral dos Estudos
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number"><?= $stats['universidades'] ?></span>
                        <div class="stat-label">Universidades</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= $stats['cursos'] ?></span>
                        <div class="stat-label">Cursos</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= $stats['disciplinas'] ?></span>
                        <div class="stat-label">Disciplinas</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= $stats['topicos'] ?></span>
                        <div class="stat-label">Tópicos</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= $stats['unidades'] ?></span>
                        <div class="stat-label">Unidades</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= $stats['matriculas'] ?></span>
                        <div class="stat-label">Matrículas</div>
                    </div>
                </div>
                
                <div class="quick-actions">
                    <a href="crud/topics_simple.php" class="quick-action">+ Novo Tópico</a>
                    <a href="crud/learning_units_simple.php" class="quick-action">+ Nova Unidade</a>
                    <a href="crud/enrollments_simple.php" class="quick-action">+ Nova Matrícula</a>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Tópicos Atrasados -->
            <?php if (!empty($topicos_atrasados)): ?>
            <div class="card">
                <div class="card-header">
                    ⚠️ Tópicos Atrasados (<?= count($topicos_atrasados) ?>)
                </div>
                <div class="card-body">
                    <?php foreach ($topicos_atrasados as $topico): ?>
                        <?php 
                        $dias = diasAtePrazo($topico['data_prazo']);
                        $status = statusPrazo($dias);
                        ?>
                        <div class="prazo-item <?= $status['class'] ?>">
                            <div class="prazo-info">
                                <h6><?= htmlspecialchars($topico['nome']) ?></h6>
                                <small><?= htmlspecialchars($topico['disciplina_nome']) ?></small>
                            </div>
                            <div class="prazo-status <?= $status['class'] ?>">
                                <?= $status['texto'] ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Prazos Próximos -->
            <div class="card">
                <div class="card-header">
                    ⏰ Prazos Próximos (7 dias)
                </div>
                <div class="card-body">
                    <?php if (empty($topicos_urgentes)): ?>
                        <div class="empty-state">
                            <i class="bi bi-calendar-check"></i>
                            <p>Nenhum prazo próximo!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($topicos_urgentes as $topico): ?>
                            <?php 
                            $dias = diasAtePrazo($topico['data_prazo']);
                            $status = statusPrazo($dias);
                            ?>
                            <div class="prazo-item <?= $status['class'] ?>">
                                <div class="prazo-info">
                                    <h6><?= htmlspecialchars($topico['nome']) ?></h6>
                                    <small><?= htmlspecialchars($topico['disciplina_nome']) ?> • <?= formatarData($topico['data_prazo']) ?></small>
                                </div>
                                <div class="prazo-status <?= $status['class'] ?>">
                                    <?= $status['texto'] ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Melhores Notas -->
            <div class="card">
                <div class="card-header">
                    🏆 Melhores Notas
                </div>
                <div class="card-body">
                    <?php if (empty($melhores_unidades)): ?>
                        <div class="empty-state">
                            <i class="bi bi-trophy"></i>
                            <p>Nenhuma nota registrada ainda</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($melhores_unidades as $unidade): ?>
                            <?php
                            $nota_class = 'nota-baixa';
                            if ($unidade['nota'] >= 9) $nota_class = 'nota-excelente';
                            elseif ($unidade['nota'] >= 7) $nota_class = 'nota-boa';
                            elseif ($unidade['nota'] >= 6) $nota_class = 'nota-regular';
                            ?>
                            <div class="atividade-item">
                                <div class="atividade-info">
                                    <h6><?= htmlspecialchars($unidade['nome']) ?></h6>
                                    <small><?= htmlspecialchars($unidade['disciplina_nome']) ?> • <?= htmlspecialchars($unidade['topico_nome']) ?></small>
                                </div>
                                <span class="nota-badge <?= $nota_class ?>">
                                    <?= number_format($unidade['nota'], 1) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Atividades Recentes -->
            <div class="card">
                <div class="card-header">
                    📈 Atividades Recentes
                </div>
                <div class="card-body">
                    <?php if (empty($atividades_recentes)): ?>
                        <div class="empty-state">
                            <i class="bi bi-clock-history"></i>
                            <p>Nenhuma atividade recente</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($atividades_recentes as $atividade): ?>
                            <div class="atividade-item">
                                <div class="atividade-info">
                                    <h6><?= htmlspecialchars($atividade['nome']) ?></h6>
                                    <small><?= htmlspecialchars($atividade['disciplina_nome']) ?> • <?= htmlspecialchars($atividade['topico_nome']) ?></small>
                                </div>
                                <div class="atividade-data">
                                    <?= date('d/m', strtotime($atividade['data_atualizacao'])) ?>
                                    <?php if ($atividade['nota'] > 0): ?>
                                        <br><span class="nota-badge nota-boa"><?= number_format($atividade['nota'], 1) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div> <!-- Fim do container -->
    </div> <!-- Fim do main-content -->

    <script>
        let sidebarCollapsed = false;
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth <= 768) {
                // Mobile: show/hide sidebar
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            } else {
                // Desktop: collapse/expand sidebar
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                sidebarCollapsed = !sidebarCollapsed;
            }
        }
        
        // Fechar sidebar em mobile ao clicar em um link
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        const sidebar = document.getElementById('sidebar');
                        const overlay = document.getElementById('sidebarOverlay');
                        sidebar.classList.remove('show');
                        overlay.classList.remove('show');
                    }
                });
            });
        });

        // Ajustar layout em redimensionamento
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                
                if (sidebarCollapsed) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                }
            } else {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
            }
        });

        // Auto-refresh a cada 5 minutos
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
