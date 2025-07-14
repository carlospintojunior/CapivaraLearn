<?php
// ===== INÍCIO DO DASHBOARD COM LOGS EXTENSIVOS =====
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não mostrar erros na tela
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/sistema.log');

// Registrar manipuladores de erro
set_exception_handler(function ($e) {
    error_log("DASHBOARD EXCEPTION: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
});

set_error_handler(function ($severity, $message, $file, $line) {
    error_log("DASHBOARD ERROR [$severity]: $message em $file:$line");
});

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_log("DASHBOARD: Sessão iniciada - session_id: " . session_id());

// Verificar login
if (!isset($_SESSION['user_id'])) {
    error_log("DASHBOARD: Usuário não logado, redirecionando");
    header('Location: login.php');
    exit;
}

error_log("DASHBOARD: Usuário logado - ID: " . $_SESSION['user_id']);

// Carregar sistema de logs
try {
    require_once __DIR__ . '/includes/logger_config.php';
    error_log("DASHBOARD: Sistema de logs carregado");
    
    // Log do acesso ao dashboard
    logInfo('Dashboard acessado', [
        'user_id' => $_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'] ?? 'unknown',
        'session_id' => session_id()
    ]);
} catch (Exception $e) {
    error_log("DASHBOARD: ERRO ao carregar logs - " . $e->getMessage());
}

// Configuração do Medoo
try {
    require_once 'Medoo.php';
    
    $database = new Medoo\Medoo([
        'type' => 'mysql',
        'host' => 'localhost',
        'database' => 'capivaralearn',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ]);
    
    error_log("DASHBOARD: Medoo configurado com sucesso");
} catch (Exception $e) {
    error_log("DASHBOARD: ERRO CRÍTICO ao configurar Medoo - " . $e->getMessage());
    die("Erro interno do sistema. Verifique os logs.");
}

$user_id = $_SESSION['user_id'];
error_log("DASHBOARD: user_id definido como: $user_id");

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

// ===== BUSCAR DADOS DO USUÁRIO =====
try {
    error_log("DASHBOARD: Buscando dados do usuário $user_id");
    $user = $database->get("usuarios", "*", ["id" => $user_id]);
    
    if ($user) {
        error_log("DASHBOARD: Usuário encontrado - Nome: " . $user['nome']);
    } else {
        error_log("DASHBOARD: ERRO - Usuário não encontrado");
        $user = ['nome' => 'Usuário', 'email' => 'email@exemplo.com'];
    }
} catch (Exception $e) {
    error_log("DASHBOARD: ERRO ao buscar usuário - " . $e->getMessage());
    $user = ['nome' => 'Usuário', 'email' => 'email@exemplo.com'];
}

// ===== ESTATÍSTICAS GERAIS =====
try {
    error_log("DASHBOARD: Coletando estatísticas");
    
    $stats = [
        'universidades' => $database->count("universidades", ["usuario_id" => $user_id]),
        'cursos' => $database->count("cursos", ["usuario_id" => $user_id]),
        'disciplinas' => $database->count("disciplinas", ["usuario_id" => $user_id]),
        'topicos' => $database->count("topicos", ["usuario_id" => $user_id]),
        'unidades' => $database->count("unidades_aprendizagem", ["usuario_id" => $user_id]),
        'matriculas' => $database->count("matriculas", ["usuario_id" => $user_id])
    ];
    
    error_log("DASHBOARD: Estatísticas coletadas - " . json_encode($stats));
} catch (Exception $e) {
    error_log("DASHBOARD: ERRO ao coletar estatísticas - " . $e->getMessage());
    $stats = [
        'universidades' => 0,
        'cursos' => 0,
        'disciplinas' => 0,
        'topicos' => 0,
        'unidades' => 0,
        'matriculas' => 0
    ];
}

// ===== TÓPICOS URGENTES =====
try {
    error_log("DASHBOARD: Buscando tópicos urgentes");
    
    $topicos_urgentes = $database->select("topicos", [
        "[>]disciplinas" => ["disciplina_id" => "id"]
    ], [
        "topicos.id",
        "topicos.nome (titulo)",
        "topicos.data_prazo (prazo_final)",
        "topicos.concluido",
        "disciplinas.nome (disciplina_nome)"
    ], [
        "topicos.usuario_id" => $user_id,
        "topicos.concluido" => 0,
        "topicos.data_prazo[!]" => null,
        "ORDER" => ["topicos.data_prazo" => "ASC"],
        "LIMIT" => 10
    ]);
    
    error_log("DASHBOARD: Encontrados " . count($topicos_urgentes) . " tópicos urgentes");
} catch (Exception $e) {
    error_log("DASHBOARD: ERRO ao buscar tópicos urgentes - " . $e->getMessage());
    $topicos_urgentes = [];
}

// ===== PRÓXIMAS AULAS =====
try {
    error_log("DASHBOARD: Buscando próximas aulas");
    
    $proximas_aulas = $database->select("unidades_aprendizagem", [
        "[>]topicos" => ["topico_id" => "id"],
        "[>]disciplinas" => ["topicos.disciplina_id" => "id"]
    ], [
        "unidades_aprendizagem.id",
        "unidades_aprendizagem.nome (titulo)",
        "unidades_aprendizagem.data_prazo (data_aula)",
        "unidades_aprendizagem.tipo (horario)",
        "disciplinas.nome (disciplina_nome)"
    ], [
        "unidades_aprendizagem.usuario_id" => $user_id,
        "unidades_aprendizagem.data_prazo[>=]" => date('Y-m-d'),
        "ORDER" => ["unidades_aprendizagem.data_prazo" => "ASC"],
        "LIMIT" => 5
    ]);
    
    error_log("DASHBOARD: Encontradas " . count($proximas_aulas) . " próximas aulas");
} catch (Exception $e) {
    error_log("DASHBOARD: ERRO ao buscar próximas aulas - " . $e->getMessage());
    $proximas_aulas = [];
}

// ===== DISCIPLINAS ATIVAS =====
try {
    error_log("DASHBOARD: Buscando disciplinas ativas");
    
    $disciplinas_ativas = $database->select("disciplinas", [
        "[>]cursos" => ["curso_id" => "id"]
    ], [
        "disciplinas.id",
        "disciplinas.nome",
        "disciplinas.concluido (concluido)",
        "cursos.nome (curso_nome)"
    ], [
        "disciplinas.usuario_id" => $user_id,
        "disciplinas.concluido" => 0,
        "ORDER" => ["disciplinas.nome" => "ASC"]
    ]);
    
    error_log("DASHBOARD: Encontradas " . count($disciplinas_ativas) . " disciplinas");
} catch (Exception $e) {
    error_log("DASHBOARD: ERRO ao buscar disciplinas ativas - " . $e->getMessage());
    $disciplinas_ativas = [];
}

// ===== PROGRESSO GERAL =====
try {
    error_log("DASHBOARD: Calculando progresso geral");
    
    $total_topicos = $database->count("topicos", ["usuario_id" => $user_id]);
    $topicos_concluidos = $database->count("topicos", [
        "usuario_id" => $user_id,
        "concluido" => 1
    ]);
    
    $progresso_geral = $total_topicos > 0 ? round(($topicos_concluidos / $total_topicos) * 100) : 0;
    
    error_log("DASHBOARD: Progresso geral calculado - $progresso_geral% ($topicos_concluidos/$total_topicos)");
} catch (Exception $e) {
    error_log("DASHBOARD: ERRO ao calcular progresso - " . $e->getMessage());
    $progresso_geral = 0;
    $topicos_concluidos = 0;
    $total_topicos = 0;
}

error_log("DASHBOARD: Carregamento de dados completo, renderizando HTML");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 15px;
            transition: transform 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }
        .card-stats:hover {
            transform: translateY(-5px);
            text-decoration: none;
            color: white;
        }
        .card-stats a {
            color: white;
            text-decoration: none;
        }
        .card-stats a:hover {
            color: white;
            text-decoration: none;
        }
        .card-stats-link {
            text-decoration: none;
            color: inherit;
        }
        .card-stats-link:hover {
            text-decoration: none;
            color: inherit;
        }
        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .urgente { background-color: #ff6b6b; color: white; }
        .atencao { background-color: #ffa726; color: white; }
        .hoje { background-color: #42a5f5; color: white; }
        .normal { background-color: #66bb6a; color: white; }
        .atrasado { background-color: #ef5350; color: white; }
        .sem-prazo { background-color: #9e9e9e; color: white; }
        .progress-custom {
            height: 10px;
            border-radius: 10px;
        }
        .sidebar {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            min-height: 100vh;
            padding: 20px 0;
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
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
            padding: 20px;
        }
        .topic-item {
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 10px 0;
            background: white;
            border-radius: 0 10px 10px 0;
        }
        .aula-item {
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 10px 0;
            background: white;
            border-radius: 0 10px 10px 0;
        }
        .disciplina-item {
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin: 10px 0;
            background: white;
            border-radius: 0 10px 10px 0;
        }
        .sidebar-divider {
            border-top: 1px solid rgba(255,255,255,0.2);
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="text-center mb-4">
                    <h4 class="text-white">CapivaraLearn</h4>
                    <p class="text-white-50">Olá, <?php echo htmlspecialchars($user['nome'] ?? 'Usuário'); ?>!</p>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard.php">
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
                    <a class="nav-link" href="#">
                        <i class="fas fa-cog me-2"></i>Configurações
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-user me-2"></i>Minha Conta
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Sair
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Dashboard</h1>
                    <span class="text-muted"><?php echo date('d/m/Y H:i'); ?></span>
                </div>

                <!-- Cards de Estatísticas -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <a href="crud/universities_simple.php" class="card-stats-link">
                            <div class="card card-stats">
                                <div class="card-body text-center">
                                    <i class="fas fa-university fa-3x mb-3"></i>
                                    <h3><?php echo $stats['universidades']; ?></h3>
                                    <p class="mb-0">Universidades</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="crud/courses_simple.php" class="card-stats-link">
                            <div class="card card-stats">
                                <div class="card-body text-center">
                                    <i class="fas fa-graduation-cap fa-3x mb-3"></i>
                                    <h3><?php echo $stats['cursos']; ?></h3>
                                    <p class="mb-0">Cursos</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="crud/enrollments_simple.php" class="card-stats-link">
                            <div class="card card-stats">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-3x mb-3"></i>
                                    <h3><?php echo $stats['matriculas']; ?></h3>
                                    <p class="mb-0">Matrículas</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <a href="crud/modules_simple.php" class="card-stats-link">
                            <div class="card card-stats">
                                <div class="card-body text-center">
                                    <i class="fas fa-book fa-3x mb-3"></i>
                                    <h3><?php echo $stats['disciplinas']; ?></h3>
                                    <p class="mb-0">Disciplinas</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="crud/topics_simple.php" class="card-stats-link">
                            <div class="card card-stats">
                                <div class="card-body text-center">
                                    <i class="fas fa-list fa-3x mb-3"></i>
                                    <h3><?php echo $stats['topicos']; ?></h3>
                                    <p class="mb-0">Tópicos</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="crud/learning_units_simple.php" class="card-stats-link">
                            <div class="card card-stats">
                                <div class="card-body text-center">
                                    <i class="fas fa-play-circle fa-3x mb-3"></i>
                                    <h3><?php echo $stats['unidades']; ?></h3>
                                    <p class="mb-0">Unidades</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Progresso Geral -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card card-custom">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-line me-2"></i>Progresso Geral</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Tópicos Concluídos</span>
                                    <span><?php echo $topicos_concluidos; ?> de <?php echo $total_topicos; ?></span>
                                </div>
                                <div class="progress progress-custom">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $progresso_geral; ?>%">
                                        <?php echo $progresso_geral; ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid de Conteúdo -->
                <div class="row">
                    <!-- Tópicos Urgentes -->
                    <div class="col-md-6 mb-4">
                        <div class="card card-custom">
                            <div class="card-header">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i>Tópicos Urgentes</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($topicos_urgentes)): ?>
                                    <p class="text-muted text-center">Nenhum tópico urgente encontrado.</p>
                                <?php else: ?>
                                    <?php foreach ($topicos_urgentes as $topico): ?>
                                        <?php 
                                            $dias = diasAtePrazo($topico['prazo_final']);
                                            $status = statusPrazo($dias);
                                        ?>
                                        <div class="topic-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($topico['titulo']); ?></h6>
                                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($topico['disciplina_nome']); ?></p>
                                                </div>
                                                <span class="badge <?php echo $status['class']; ?>"><?php echo $status['texto']; ?></span>
                                            </div>
                                            <small class="text-muted">Prazo: <?php echo formatarData($topico['prazo_final']); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Próximas Aulas -->
                    <div class="col-md-6 mb-4">
                        <div class="card card-custom">
                            <div class="card-header">
                                <h5><i class="fas fa-calendar-alt me-2"></i>Próximas Aulas</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($proximas_aulas)): ?>
                                    <p class="text-muted text-center">Nenhuma aula agendada.</p>
                                <?php else: ?>
                                    <?php foreach ($proximas_aulas as $aula): ?>
                                        <div class="aula-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($aula['titulo']); ?></h6>
                                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($aula['disciplina_nome']); ?></p>
                                                </div>
                                                <span class="badge bg-success"><?php echo $aula['horario'] ?? 'Sem horário'; ?></span>
                                            </div>
                                            <small class="text-muted">Data: <?php echo formatarData($aula['data_aula']); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Disciplinas Ativas -->
                <div class="row">
                    <div class="col-12">
                        <div class="card card-custom">
                            <div class="card-header">
                                <h5><i class="fas fa-book-open me-2"></i>Disciplinas</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($disciplinas_ativas)): ?>
                                    <p class="text-muted text-center">Nenhuma disciplina encontrada.</p>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($disciplinas_ativas as $disciplina): ?>
                                            <div class="col-md-4 mb-3">
                                                <div class="disciplina-item">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($disciplina['nome']); ?></h6>
                                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($disciplina['curso_nome']); ?></p>
                                                    <span class="badge bg-info"><?php echo $disciplina['concluido'] ? 'Concluída' : 'Ativa'; ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Log no console para debug
        console.log('Dashboard carregado com sucesso!');
        console.log('Estatísticas:', <?php echo json_encode($stats); ?>);
        console.log('Progresso geral:', <?php echo $progresso_geral; ?>);
    </script>
</body>
</html>
<?php
error_log("DASHBOARD: HTML renderizado com sucesso");
?>
