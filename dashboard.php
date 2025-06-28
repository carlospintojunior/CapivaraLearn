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

// Carregar configurações e sistema de log
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/log_sistema.php';
// Incluir DatabaseConnection fallback
require_once __DIR__ . '/includes/DatabaseConnection.php';
if (!class_exists('Database') && class_exists('CapivaraLearn\\DatabaseConnection')) {
    class_alias('CapivaraLearn\\DatabaseConnection', 'Database');
}
// Fallback para funções auxiliares se não estiverem definidas
if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('logActivity')) {
    function logActivity($action, $description = '', $userId = null) {
        log_sistema("[logActivity fallback] {$action} | {$description}", 'INFO');
    }
}
// Global error handlers
set_exception_handler(function (\Throwable $e) {
    log_sistema("Exceção não capturada em dashboard.php: " . $e->getMessage(), 'ERROR');
});
set_error_handler(function ($severity, $message, $file, $line) {
    log_sistema("Erro [" . $severity . "] " . $message . " em " . $file . ":" . $line, 'ERROR');
    return false;
});
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        log_sistema("Fatal shutdown em dashboard.php: " . $error['message'], 'ERROR');
    }
});
// Log inicial de dashboard
log_sistema('Dashboard carregado', 'INFO');
// Debug de sessão e cookies
log_sistema('Dashboard load: session_id=' . session_id() . ' | session=' . json_encode($_SESSION) . ' | cookies=' . json_encode($_COOKIE), 'DEBUG');
// Verificar se está logado
log_sistema('Verificando autenticação: user_id=' . ($_SESSION['user_id'] ?? 'não definido') . ' | isset=' . (isset($_SESSION['user_id']) ? 'true' : 'false'), 'DEBUG');
if (!isset($_SESSION['user_id'])) {
    log_sistema('Usuário não autenticado, redirecionando para login', 'WARNING');
    header('Location: login.php');
    exit();
} else {
    log_sistema('Usuário autenticado, continuando no dashboard: user_id=' . $_SESSION['user_id'], 'INFO');
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];    // Buscar dados do usuário e suas matrículas
    try {
        log_sistema('Buscando dados do usuário no banco: user_id=' . $userId, 'DEBUG');
        // Consulta simplificada - apenas tabela usuarios (configuracoes_usuario pode não existir)
        $user = $db->select(
            "SELECT u.* FROM usuarios u WHERE u.id = ?", 
            [$userId]
        );
        log_sistema('Resultado consulta usuário: ' . json_encode($user), 'DEBUG');

        // Buscar universidades e cursos do usuário (pode retornar vazio se tabelas não existem)
        $matriculas = [];
        try {
            $matriculas = $db->select(
                "SELECT 
                    ucu.*, 
                    u.nome as universidade_nome, 
                    u.sigla as universidade_sigla,
                    c.nome as curso_nome,
                    c.nivel as curso_nivel
                 FROM usuario_curso_universidade ucu
                 JOIN universidades u ON ucu.universidade_id = u.id
                 JOIN cursos c ON ucu.curso_id = c.id
                 WHERE ucu.usuario_id = ?
                 ORDER BY ucu.data_inicio DESC",
                [$userId]
            );
        } catch (Exception $e) {
            log_sistema('Erro ao buscar matrículas (tabelas podem não existir): ' . $e->getMessage(), 'WARNING');
            $matriculas = [];
        }

    if (!$user) {
        log_sistema('ERRO: Usuário não encontrado no banco mesmo com sessão válida. user_id=' . $userId, 'ERROR');
        header('Location: login.php');
        exit();
    } else {
        log_sistema('Usuário encontrado no banco: ' . $user[0]['nome'], 'INFO');
    }

    $user = $user[0];
    
    // Definir configurações padrão se não existirem
    $user['tema'] = $user['tema'] ?? 'claro';
    $user['notificacoes_email'] = $user['notificacoes_email'] ?? 1;

    // Buscar estatísticas do usuário (protegido contra tabelas inexistentes)
    $stats = [];
    try {
        $stats = $db->select(
            "SELECT 
                COUNT(DISTINCT m.id) as total_modulos,
                COUNT(DISTINCT t.id) as total_topicos,
                COUNT(DISTINCT CASE WHEN t.concluido = 1 THEN t.id END) as topicos_concluidos,
                COUNT(DISTINCT CASE WHEN t.data_fim < CURDATE() AND t.concluido = 0 THEN t.id END) as topicos_atrasados,
                COUNT(DISTINCT CASE WHEN t.data_inicio <= CURDATE() AND t.data_fim >= CURDATE() AND t.concluido = 0 THEN t.id END) as topicos_ativos
             FROM modulos m
             LEFT JOIN topicos t ON m.id = t.modulo_id
             WHERE m.usuario_id = ? AND m.ativo = 1",
            [$userId]
        );
    } catch (Exception $e) {
        log_sistema('Erro ao buscar estatísticas (tabelas podem não existir): ' . $e->getMessage(), 'WARNING');
    }

    $stats = $stats[0] ?? [
        'total_modulos' => 0,
        'total_topicos' => 0,
        'topicos_concluidos' => 0,
        'topicos_atrasados' => 0,
        'topicos_ativos' => 0
    ];

    // Buscar módulos do usuário (protegido contra tabelas inexistentes)
    $modulos = [];
    try {
        $modulos = $db->select(
            "SELECT m.*, 
                    COUNT(t.id) as total_topicos,
                    COUNT(CASE WHEN t.concluido = 1 THEN 1 END) as topicos_concluidos,
                    COUNT(CASE WHEN t.data_fim < CURDATE() AND t.concluido = 0 THEN 1 END) as topicos_atrasados
             FROM modulos m
             LEFT JOIN topicos t ON m.id = t.modulo_id
             WHERE m.usuario_id = ? AND m.ativo = 1
             GROUP BY m.id, m.nome, m.data_inicio, m.data_fim
             ORDER BY m.data_inicio DESC",
            [$userId]
        );
    } catch (Exception $e) {
        log_sistema('Erro ao buscar módulos (tabelas podem não existir): ' . $e->getMessage(), 'WARNING');
        $modulos = [];
    }

    // Buscar tópicos ativos/próximos com arquivos (protegido contra tabelas inexistentes)
    $topicos_proximos = [];
    try {
        $topicos_proximos = $db->select(
            "SELECT 
                t.*, 
                m.nome as modulo_nome, 
                m.codigo as modulo_codigo,
                COUNT(DISTINCT ta.arquivo_id) as total_arquivos
             FROM topicos t
             JOIN modulos m ON t.modulo_id = m.id
             LEFT JOIN topico_arquivo ta ON t.id = ta.topico_id
             WHERE m.usuario_id = ? AND m.ativo = 1 
             AND (t.data_fim >= CURDATE() OR (t.data_fim < CURDATE() AND t.concluido = 0))
             GROUP BY t.id, t.nome, m.nome, m.codigo
             ORDER BY t.data_fim ASC
             LIMIT 5",
            [$userId]
        );
    } catch (Exception $e) {
        log_sistema('Erro ao buscar tópicos próximos (tabelas podem não existir): ' . $e->getMessage(), 'WARNING');
        $topicos_proximos = [];
    }

} catch (Exception $e) {
    log_sistema("Erro ao carregar dashboard: " . $e->getMessage(), 'ERROR');
    // Em vez de die(), vamos continuar com dados vazios
    $user = ['nome' => 'Usuário', 'email' => $_SESSION['user_email'] ?? 'email@exemplo.com'];
    $matriculas = [];
    $stats = [
        'total_modulos' => 0,
        'total_topicos' => 0,
        'topicos_concluidos' => 0,
        'topicos_atrasados' => 0,
        'topicos_ativos' => 0
    ];
    $modulos = [];
    $topicos_proximos = [];
}
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
        }

        .nav-pills .nav-link {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            margin: 0 5px;
        }

        .nav-pills .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .nav-pills .nav-link.active {
            background-color: #fff;
            color: #764ba2;
        }

        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .progress {
            height: 10px;
            border-radius: 5px;
        }

        .status-badge {
            font-size: 0.8em;
            padding: 5px 10px;
            border-radius: 12px;
        }

        .status-cursando {
            background-color: #28a745;
            color: white;
        }

        .status-trancado {
            background-color: #ffc107;
            color: black;
        }

        .status-concluido {
            background-color: #17a2b8;
            color: white;
        }

        .status-abandonado {
            background-color: #dc3545;
            color: white;
        }

        .enrollment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .enrollment-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .enrollment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        .university-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .university-info h3 {
            font-size: 1.2em;
            color: #2c3e50;
            margin: 0;
        }

        .university-code {
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            color: #6c757d;
            font-size: 0.9em;
        }

        .course-info {
            margin-bottom: 15px;
        }

        .course-info h4 {
            font-size: 1.1em;
            color: #3498db;
            margin: 0 0 5px 0;
        }

        .course-level {
            font-size: 0.9em;
            color: #7f8c8d;
        }

        .enrollment-status {
            margin-bottom: 15px;
        }

        .enrollment-period {
            display: flex;
            flex-direction: column;
            gap: 5px;
            font-size: 0.9em;
            color: #7f8c8d;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.8em;
            font-weight: 300;
        }

        .logo img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .logo-text {
            font-weight: 600;
            letter-spacing: -0.5px;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .dropdown {
            position: relative;
        }

        .dropdown-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 18px;
        }

        .dropdown-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-1px);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            min-width: 200px;
            z-index: 1000;
            display: none;
            margin-top: 10px;
            overflow: hidden;
        }

        .dropdown-menu.show {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dropdown-item {
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.3s;
            font-weight: 500;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome-banner {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .welcome-banner img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .welcome-content h2 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.8em;
        }

        .welcome-content p {
            color: #7f8c8d;
            font-size: 1.1em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 3em;
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--card-color);
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.95em;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .stat-card.modules { --card-color: #3498db; }
        .stat-card.topics { --card-color: #9b59b6; }
        .stat-card.completed { --card-color: #27ae60; }
        .stat-card.overdue { --card-color: #e74c3c; }
        .stat-card.active { --card-color: #f39c12; }

        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.5em;
            margin-bottom: 25px;
            color: #2c3e50;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.4);
        }

        .btn-logout {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .module-grid {
            display: grid;
            gap: 20px;
        }

        .module-card {
            border: 1px solid #ecf0f1;
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }

        .module-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transform: translateY(-3px);
            border-color: #3498db;
        }

        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
        }

        .module-title {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 1.1em;
        }

        .module-period {
            font-size: 0.9em;
            color: #7f8c8d;
            font-weight: 500;
        }

        .module-progress {
            display: flex;
            gap: 20px;
            font-size: 0.9em;
            margin-top: 15px;
        }

        .progress-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .topic-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .topic-item {
            padding: 20px;
            border-left: 4px solid #3498db;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .topic-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .topic-item.overdue {
            border-left-color: #e74c3c;
            background: linear-gradient(135deg, #fdf2f2 0%, #fff 100%);
        }

        .topic-item.active {
            border-left-color: #f39c12;
            background: linear-gradient(135deg, #fff8e1 0%, #fff 100%);
        }

        .topic-item.completed {
            border-left-color: #27ae60;
            background: linear-gradient(135deg, #f1f8e9 0%, #fff 100%);
        }

        .topic-title {
            font-weight: 700;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .topic-module {
            font-size: 0.85em;
            color: #7f8c8d;
            margin-bottom: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .topic-dates {
            font-size: 0.9em;
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .topic-files {
            margin-top: 8px;
        }

        .file-count {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.85em;
            color: #3498db;
            background: rgba(52, 152, 219, 0.1);
            padding: 4px 8px;
            border-radius: 12px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 700;
            text-transform: uppercase;
            margin-left: 10px;
            letter-spacing: 0.5px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }

        .empty-state h3 {
            margin-bottom: 15px;
            font-size: 1.5em;
            color: #95a5a6;
        }

        .quick-actions {
            display: grid;
            gap: 15px;
        }

        .management-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 2px solid #e9ecef;
            border-radius: 15px;
            text-decoration: none;
            color: #2c3e50;
            transition: all 0.3s ease;
            text-align: center;
        }

        .management-btn:hover {
            color: #3498db;
            border-color: #3498db;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.2);
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }

        .management-btn i {
            font-size: 1.5em;
            margin-bottom: 5px;
        }

        .management-btn span {
            font-weight: 600;
            font-size: 1em;
        }

        .management-btn small {
            font-size: 0.8em;
            color: #7f8c8d;
            text-align: center;
            line-height: 1.2;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .welcome-banner {
                flex-direction: column;
                text-align: center;
            }

            .main-content {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .logo {
                font-size: 1.5em;
            }

            .logo img {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <img src="public/assets/images/logo.png" alt="CapivaraLearn" onerror="this.style.display='none'; this.nextElementSibling.innerHTML='🦫 CapivaraLearn';">
                <span class="logo-text">CapivaraLearn</span>
            </div>
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr(h($user['nome']), 0, 1)) ?>
                    </div>
                    <span>Olá, <?= h($user['nome']) ?>!</span>
                </div>
                <div class="dropdown">
                    <button class="dropdown-btn" onclick="toggleDropdown()">⚙️</button>
                    <div class="dropdown-menu" id="userDropdown">
                        <a href="crud/universities_simple.php" class="dropdown-item">🏛️ Universidades</a>
                        <a href="crud/courses_simple.php" class="dropdown-item">🎓 Cursos</a>
                        <a href="crud/enrollments_simple.php" class="dropdown-item">🎯 Matrículas</a>
                        <a href="crud/modules_simple.php" class="dropdown-item">� Disciplinas</a>
                        <a href="crud/topics_simple.php" class="dropdown-item">📝 Tópicos</a>
                        <a href="crud/learning_units_simple.php" class="dropdown-item">🧩 Unidades de Aprendizagem</a>
                        <a href="#" class="dropdown-item">👤 Meu Perfil</a>
                        <a href="#" class="dropdown-item">⚙️ Configurações</a>
                        <a href="logout.php" class="dropdown-item">🚪 Sair</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET['welcome'])): ?>
        <div class="welcome-banner">
            <img src="public/assets/images/logo.png" alt="CapivaraLearn" onerror="this.innerHTML='🦫';">
            <div class="welcome-content">
                <h2>🎉 Bem-vindo ao CapivaraLearn!</h2>
                <p>Sua conta foi criada com sucesso. Comece organizando seus estudos criando seu primeiro módulo.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Universidades e Cursos -->
        <div class="section mb-4">
            <div class="section-title">
                🎓 Minhas Matrículas
                <a href="manage_enrollments.php" class="btn btn-primary">+ Nova Matrícula</a>
            </div>

            <?php if (empty($matriculas)): ?>
                <div class="empty-state">
                    <h3>Nenhuma matrícula encontrada</h3>
                    <p>Clique em "Nova Matrícula" para começar!</p>
                </div>
            <?php else: ?>
                <div class="enrollment-grid">
                    <?php foreach ($matriculas as $matricula): ?>
                        <div class="enrollment-card">
                            <div class="university-info">
                                <h3><?= h($matricula['universidade_nome']) ?></h3>
                                <span class="university-code"><?= h($matricula['universidade_sigla']) ?></span>
                            </div>
                            <div class="course-info">
                                <h4><?= h($matricula['curso_nome']) ?></h4>
                                <span class="course-level"><?= ucfirst(str_replace('_', ' ', $matricula['curso_nivel'])) ?></span>
                            </div>
                            <div class="enrollment-status">
                                <span class="status-badge status-<?= $matricula['situacao'] ?>">
                                    <?= ucfirst($matricula['situacao']) ?>
                                </span>
                            </div>
                            <div class="enrollment-period">
                                <span>Início: <?= formatDate($matricula['data_inicio']) ?></span>
                                <?php if ($matricula['data_fim']): ?>
                                    <span>Término: <?= formatDate($matricula['data_fim']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card modules">
                <div class="stat-number"><?= $stats['total_modulos'] ?></div>
                <div class="stat-label">Módulos</div>
            </div>
            <div class="stat-card topics">
                <div class="stat-number"><?= $stats['total_topicos'] ?></div>
                <div class="stat-label">Tópicos</div>
            </div>
            <div class="stat-card completed">
                <div class="stat-number"><?= $stats['topicos_concluidos'] ?></div>
                <div class="stat-label">Concluídos</div>
            </div>
            <div class="stat-card active">
                <div class="stat-number"><?= $stats['topicos_ativos'] ?></div>
                <div class="stat-label">Ativos</div>
            </div>
            <div class="stat-card overdue">
                <div class="stat-number"><?= $stats['topicos_atrasados'] ?></div>
                <div class="stat-label">Atrasados</div>
            </div>
        </div>

        <!-- Seção de Gerenciamento -->
        <div class="section mb-4">
            <div class="section-title">
                ⚙️ Gerenciamento
            </div>
            <div class="management-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <a href="crud/universities_simple.php" class="management-btn">
                    <i class="bi bi-building"></i>
                    <span>Universidades</span>
                    <small>Cadastrar e gerenciar universidades</small>
                </a>
                <a href="manage_courses.php" class="management-btn">
                    <i class="bi bi-mortarboard"></i>
                    <span>Cursos</span>
                    <small>Cadastrar e gerenciar cursos</small>
                </a>
                <a href="manage_modules.php" class="management-btn">
                    <i class="bi bi-journal-bookmark"></i>
                    <span>Módulos</span>
                    <small>Cadastrar e gerenciar módulos</small>
                </a>
                <a href="manage_topics.php" class="management-btn">
                    <i class="bi bi-card-checklist"></i>
                    <span>Tópicos</span>
                    <small>Cadastrar e gerenciar tópicos</small>
                </a>
                <a href="manage_enrollments.php" class="management-btn">
                    <i class="bi bi-person-plus"></i>
                    <span>Matrículas</span>
                    <small>Gerenciar suas matrículas</small>
                </a>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="main-content">
            <!-- Módulos -->
            <div class="section">
                <div class="section-title">
                    📚 Meus Módulos
                    <a href="manage_modules.php" class="btn btn-primary">+ Novo Módulo</a>
                </div>

                <?php if (empty($modulos)): ?>
                    <div class="empty-state">
                        <h3>Nenhum módulo cadastrado</h3>
                        <p>Clique em "Novo Módulo" para começar a organizar seus estudos!</p>
                    </div>
                <?php else: ?>
                    <div class="module-grid">
                        <?php foreach ($modulos as $modulo): ?>
                            <div class="module-card">
                                <div class="module-header">
                                    <div>
                                        <div class="module-title"><?= h($modulo['nome']) ?></div>
                                        <div class="module-period">
                                            📅 <?= formatDate($modulo['data_inicio']) ?> - <?= formatDate($modulo['data_fim']) ?>
                                        </div>
                                    </div>
                                    <div>
                                        <a href="#" class="btn btn-primary" onclick="alert('Ver módulo - em desenvolvimento')">Ver Detalhes</a>
                                    </div>
                                </div>
                                
                                <?php if (!empty($modulo['descricao'])): ?>
                                    <p style="margin-bottom: 15px; color: #7f8c8d; font-style: italic;"><?= h($modulo['descricao']) ?></p>
                                <?php endif; ?>

                                <div class="module-progress">
                                    <div class="progress-item">
                                        <span>📋</span>
                                        <span><?= $modulo['total_topicos'] ?> tópicos</span>
                                    </div>
                                    <div class="progress-item">
                                        <span>✅</span>
                                        <span><?= $modulo['topicos_concluidos'] ?> concluídos</span>
                                    </div>
                                    <?php if ($modulo['topicos_atrasados'] > 0): ?>
                                        <div class="progress-item" style="color: #e74c3c;">
                                            <span>⚠️</span>
                                            <span><?= $modulo['topicos_atrasados'] ?> atrasados</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tópicos Próximos -->
            <div class="section">
                <div class="section-title">
                    📅 Próximos Tópicos
                </div>

                <?php if (empty($topicos_proximos)): ?>
                    <div class="empty-state">
                        <h4>Nenhum tópico próximo</h4>
                        <p>Seus estudos estão em dia!</p>
                    </div>
                <?php else: ?>
                    <div class="topic-list">
                        <?php foreach ($topicos_proximos as $topico): ?>
                            <?php $status = getModuleStatus($topico['data_inicio'], $topico['data_fim'], $topico['concluido']); ?>
                            <div class="topic-item <?= $status['class'] ?>">
                                <div class="topic-title"><?= h($topico['nome']) ?></div>
                                <div class="topic-module"><?= h($topico['modulo_codigo']) ?></div>
                                <div class="topic-dates">
                                    📅 <?= formatDate($topico['data_inicio']) ?> - <?= formatDate($topico['data_fim']) ?>
                                    <span class="status-badge" style="background: <?= $status['color'] ?>; color: white;">
                                        <?= $status['text'] ?>
                                    </span>
                                </div>
                                <?php if ($topico['total_arquivos'] > 0): ?>
                                    <div class="topic-files">
                                        <span class="file-count">
                                            📎 <?= $topico['total_arquivos'] ?> <?= $topico['total_arquivos'] > 1 ? 'anexos' : 'anexo' ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status do Sistema -->
        <div class="section">
            <div class="section-title">📊 Status do Sistema</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-database"></i> Seus Dados</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            try {
                                require_once __DIR__ . '/Medoo.php';
                                
                                $db = new Medoo\Medoo([
                                    'type' => 'mysql',
                                    'host' => 'localhost',
                                    'database' => 'capivaralearn',
                                    'username' => 'root',
                                    'password' => '',
                                    'charset' => 'utf8mb4'
                                ]);
                                
                                $user_id = $_SESSION['user_id'];
                                $stats = [
                                    '🏛️ Universidades' => $db->count("universidades", ["usuario_id" => $user_id]),
                                    '🎓 Cursos' => $db->count("cursos", ["usuario_id" => $user_id]),
                                    '📚 Disciplinas' => $db->count("disciplinas", ["usuario_id" => $user_id]),
                                    '📝 Tópicos' => $db->count("topicos", ["usuario_id" => $user_id]),
                                    '🧩 Unidades de Aprendizagem' => $db->count("unidades_aprendizagem", ["usuario_id" => $user_id]),
                                    '🎯 Matrículas' => $db->count("inscricoes", ["usuario_id" => $user_id])
                                ];
                                
                                foreach ($stats as $label => $count) {
                                    echo "<div class='d-flex justify-content-between'>";
                                    echo "<span>$label:</span>";
                                    echo "<span class='badge bg-primary'>$count</span>";
                                    echo "</div>";
                                }
                            } catch (Exception $e) {
                                echo "<p class='text-danger'><small>Erro ao carregar estatísticas</small></p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-tools"></i> Links Úteis</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <a href="crud/universities_simple.php" class="list-group-item list-group-item-action">🏛️ Gerenciar Universidades</a>
                                <a href="crud/courses_simple.php" class="list-group-item list-group-item-action">🎓 Gerenciar Cursos</a>
                                <a href="crud/modules_simple.php" class="list-group-item list-group-item-action">📚 Gerenciar Disciplinas</a>
                                <a href="crud/topics_simple.php" class="list-group-item list-group-item-action">📝 Gerenciar Tópicos</a>
                                <a href="crud/learning_units_simple.php" class="list-group-item list-group-item-action">🧩 Gerenciar Unidades de Aprendizagem</a>
                                <a href="crud/enrollments_simple.php" class="list-group-item list-group-item-action">🎯 Gerenciar Matrículas</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="section">
            <div class="section-title">⚡ Ações Rápidas</div>
            <div class="quick-actions">
                <a href="crud/universities_simple.php" class="btn" style="background: linear-gradient(135deg, #27ae60, #219a52); color: white;">🏛️ Nova Universidade</a>
                <a href="crud/courses_simple.php" class="btn btn-primary">🎓 Novo Curso</a>
                <a href="crud/modules_simple.php" class="btn btn-info">📚 Nova Disciplina</a>
                <a href="crud/topics_simple.php" class="btn" style="background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white;">📝 Novo Tópico</a>
                <a href="crud/learning_units_simple.php" class="btn btn-success">🧩 Nova Unidade de Aprendizagem</a>
                <a href="crud/enrollments_simple.php" class="btn btn-warning">🎯 Nova Matrícula</a>
                <a href="logout.php" class="btn btn-logout">🚪 Sair do Sistema</a>
            </div>
        </div>
    </div>

    <script>
        // Dropdown do usuário
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(e) {
            if (!e.target.matches('.dropdown-btn')) {
                const dropdown = document.getElementById('userDropdown');
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });
    </script>
</body>
</html>