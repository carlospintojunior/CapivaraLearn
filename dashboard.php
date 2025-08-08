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

// Incluir configurações
require_once __DIR__ . '/includes/config.php';

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

// Carregar sistema de versão
try {
    require_once __DIR__ . '/includes/version.php';
    error_log("DASHBOARD: Sistema de versão carregado");
} catch (Exception $e) {
    error_log("DASHBOARD: ERRO ao carregar versão - " . $e->getMessage());
}

// Configuração do Medoo
try {
    require_once 'Medoo.php';
    
    $database = new Medoo\Medoo([
        'type' => 'mysql',
        'host' => DB_HOST,
        'database' => DB_NAME,
        'username' => DB_USER,
        'password' => DB_PASS,
        'charset' => DB_CHARSET
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
    $hoje->setTime(0, 0, 0); // Zerar hora para comparar apenas a data
    $prazo = new DateTime($data_prazo);
    $prazo->setTime(23, 59, 59); // Considerar o prazo até o final do dia
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

// Função para status da unidade de aprendizagem
function statusUnidade($concluido, $data_prazo) {
    if ($concluido) {
        return ['class' => 'bg-success', 'texto' => 'Concluído', 'icon' => 'fa-check-circle'];
    }
    
    $dias = diasAtePrazo($data_prazo);
    if ($dias === null) {
        return ['class' => 'bg-secondary', 'texto' => 'Pendente', 'icon' => 'fa-clock'];
    }
    if ($dias < 0) {
        return ['class' => 'bg-danger', 'texto' => 'Atrasado', 'icon' => 'fa-exclamation-triangle'];
    }
    if ($dias <= 3) {
        return ['class' => 'bg-warning', 'texto' => 'Urgente', 'icon' => 'fa-exclamation-circle'];
    }
    
    return ['class' => 'bg-info', 'texto' => 'Pendente', 'icon' => 'fa-clock'];
}

// Função para status do tópico
function statusTopico($concluido) {
    if ($concluido) {
        return ['class' => 'bg-success', 'texto' => 'Concluído', 'icon' => 'fa-check-circle'];
    } else {
        return ['class' => 'bg-warning', 'texto' => 'Pendente', 'icon' => 'fa-clock'];
    }
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
        "topicos.data_prazo[!]" => null,
        "topicos.data_prazo[>=]" => date('Y-m-d', strtotime('-7 days')), // Incluir até 7 dias atrás
        "ORDER" => ["topicos.data_prazo" => "ASC"],
        "LIMIT" => 15
    ]);
    
    error_log("DASHBOARD: Encontrados " . count($topicos_urgentes) . " tópicos urgentes");
} catch (Exception $e) {
    error_log("DASHBOARD: ERRO ao buscar tópicos urgentes - " . $e->getMessage());
    $topicos_urgentes = [];
}

// ===== UNIDADES DE APRENDIZAGEM DAS DISCIPLINAS ATIVAS =====
try {
    error_log("DASHBOARD: Buscando unidades de aprendizagem das disciplinas ativas");
    
    $unidades_aprendizagem = $database->select("unidades_aprendizagem", [
        "[>]topicos" => ["topico_id" => "id"],
        "[>]disciplinas" => ["topicos.disciplina_id" => "id"]
    ], [
        "unidades_aprendizagem.id",
        "unidades_aprendizagem.nome (titulo)",
        "unidades_aprendizagem.data_prazo",
        "unidades_aprendizagem.concluido",
        "unidades_aprendizagem.nota",
        "disciplinas.nome (disciplina_nome)",
        "disciplinas.status (disciplina_status)"
    ], [
        "unidades_aprendizagem.usuario_id" => $user_id,
        "disciplinas.status" => 0, // Apenas disciplinas ativas
        "ORDER" => [
            "disciplinas.nome" => "ASC",
            "unidades_aprendizagem.data_prazo" => "ASC"
        ]
    ]);
    
    error_log("DASHBOARD: Encontradas " . count($unidades_aprendizagem) . " unidades de aprendizagem");
} catch (Exception $e) {
    error_log("DASHBOARD: ERRO ao buscar unidades de aprendizagem - " . $e->getMessage());
    $unidades_aprendizagem = [];
}

// ===== DISCIPLINAS ATIVAS =====
try {
    error_log("DASHBOARD: Buscando disciplinas ativas");
    
    $disciplinas_ativas = $database->select("disciplinas", [
        "[>]cursos" => ["curso_id" => "id"]
    ], [
        "disciplinas.id",
        "disciplinas.nome",
        "disciplinas.status (status)",
        "cursos.nome (curso_nome)"
    ], [
        "disciplinas.usuario_id" => $user_id,
        "disciplinas.status" => 0,
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
    
    // ===== ESTATÍSTICAS DE CARGA HORÁRIA =====
    error_log("DASHBOARD: Calculando estatísticas de carga horária");
    logInfo('Iniciando cálculos de progresso', ['user_id' => $user_id]);

    // Carga horária total de todas as disciplinas do usuário
    $carga_total_result = $database->sum("disciplinas", "carga_horaria", ["usuario_id" => $user_id]);
    $carga_horaria_total = $carga_total_result ? (int)$carga_total_result : 0;
    logInfo('Carga horária total calculada', [
        'user_id' => $user_id,
        'carga_total_result' => $carga_total_result,
        'carga_horaria_total' => $carga_horaria_total
    ]);
    
    // Primeiro vamos testar a sintaxe correta do Medoo para IN
    // Opção 1: Usando OR
    $carga_concluida_result = $database->sum("disciplinas", "carga_horaria", [
        "usuario_id" => $user_id,
        "OR" => [
            "status" => 1,  // Concluída
            "status" => 3,  // Aproveitada  
            "status" => 4   // Dispensada
        ]
    ]);
    
    // Se não funcionar, vamos tentar com array direto
    if (!$carga_concluida_result) {
        $carga_concluida_result = $database->sum("disciplinas", "carga_horaria", [
            "usuario_id" => $user_id,
            "status" => [1, 3, 4]  // Array direto
        ]);
        logInfo('Tentativa com array direto', [
            'user_id' => $user_id,
            'carga_concluida_result' => $carga_concluida_result
        ]);
    }
    
    $carga_horaria_concluida = $carga_concluida_result ? (int)$carga_concluida_result : 0;
    logInfo('Carga horária concluída calculada', [
        'user_id' => $user_id,
        'carga_concluida_result' => $carga_concluida_result,
        'carga_horaria_concluida' => $carga_horaria_concluida,
        'query_status' => 'status IN (1,3,4) - Concluída, Aproveitada, Dispensada'
    ]);
    
    // Vamos fazer uma consulta manual para verificar os dados
    $disciplinas_debug = $database->select("disciplinas", [
        "id", "nome", "status", "carga_horaria"
    ], [
        "usuario_id" => $user_id
    ]);
    
    $status_count = ['0'=>0, '1'=>0, '2'=>0, '3'=>0, '4'=>0];
    $carga_por_status = ['0'=>0, '1'=>0, '2'=>0, '3'=>0, '4'=>0];
    
    foreach ($disciplinas_debug as $disc) {
        $status = (string)$disc['status'];
        $status_count[$status]++;
        $carga_por_status[$status] += (int)$disc['carga_horaria'];
    }
    
    logInfo('Debug detalhado das disciplinas', [
        'user_id' => $user_id,
        'total_disciplinas_debug' => count($disciplinas_debug),
        'status_count' => $status_count,
        'carga_por_status' => $carga_por_status,
        'carga_manual_concluida' => $carga_por_status['1'] + $carga_por_status['3'] + $carga_por_status['4']
    ]);

    // Progresso por carga horária (prioridade 1)
    $progresso_carga_horaria = $carga_horaria_total > 0 ? round(($carga_horaria_concluida / $carga_horaria_total) * 100) : 0;
    
    // Total de disciplinas e disciplinas concluídas, aproveitadas ou dispensadas
    $total_disciplinas = $database->count("disciplinas", ["usuario_id" => $user_id]);
    
    // Testando count com mesma lógica
    $disciplinas_concluidas = $database->count("disciplinas", [
        "usuario_id" => $user_id,
        "OR" => [
            "status" => 1,  // Concluída
            "status" => 3,  // Aproveitada  
            "status" => 4   // Dispensada
        ]
    ]);
    
    // Se não funcionar, tentar com array
    if (!$disciplinas_concluidas) {
        $disciplinas_concluidas = $database->count("disciplinas", [
            "usuario_id" => $user_id,
            "status" => [1, 3, 4]
        ]);
    }
    
    logInfo('Contagem de disciplinas', [
        'user_id' => $user_id,
        'total_disciplinas' => $total_disciplinas,
        'disciplinas_concluidas' => $disciplinas_concluidas,
        'disciplinas_concluidas_manual' => $status_count['1'] + $status_count['3'] + $status_count['4']
    ]);
    
    // Progresso por disciplinas (prioridade 2)  
    $progresso_disciplinas = $total_disciplinas > 0 ? round(($disciplinas_concluidas / $total_disciplinas) * 100) : 0;
    
    logInfo('Resultado final dos cálculos', [
        'user_id' => $user_id,
        'carga_horaria_concluida' => $carga_horaria_concluida,
        'carga_horaria_total' => $carga_horaria_total,
        'progresso_carga_horaria' => $progresso_carga_horaria,
        'disciplinas_concluidas' => $disciplinas_concluidas,
        'total_disciplinas' => $total_disciplinas,
        'progresso_disciplinas' => $progresso_disciplinas
    ]);
    
    error_log("DASHBOARD: Estatísticas calculadas - Carga: {$carga_horaria_concluida}h/{$carga_horaria_total}h ({$progresso_carga_horaria}%) - Disciplinas: {$disciplinas_concluidas}/{$total_disciplinas} ({$progresso_disciplinas}%)");} catch (Exception $e) {
    error_log("DASHBOARD: ERRO ao calcular progresso - " . $e->getMessage());
    $progresso_geral = 0;
    $topicos_concluidos = 0;
    $total_topicos = 0;
    $carga_horaria_total = 0;
    $carga_horaria_concluida = 0;
    $progresso_carga_horaria = 0;
    $total_disciplinas = 0;
    $disciplinas_concluidas = 0;
    $progresso_disciplinas = 0;
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
        .unidade-item {
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0 !important;
        }
        .unidade-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }
        .badge {
            font-size: 0.75em;
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
        .sidebar-nav {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .sidebar-footer {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        .sidebar-version {
            background-color: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.9) !important;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .nav-section-header {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-top: 0.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
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
                
                <nav class="nav flex-column sidebar-nav">
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
                    
                    <!-- Seção de Manutenção -->
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
                    <a class="nav-link" href="#">
                        <i class="fas fa-user me-2"></i>Minha Conta
                    </a>
                    <a class="nav-link" href="financial_dashboard.php">
                        <i class="fas fa-dollar-sign me-2"></i>Contribuições
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Sair
                    </a>
                </nav>
                
                <!-- Footer da Sidebar com Versão -->
                <div class="sidebar-footer text-center">
                    <small class="sidebar-version d-block mb-2">
                        <?php 
                        if (class_exists('AppVersion')) {
                            echo AppVersion::getSidebarText(); 
                        } else {
                            echo 'v1.1.0';
                        }
                        ?>
                    </small>
                    <div class="mb-2">
                        <a href="https://github.com/carlospintojunior/CapivaraLearn" target="_blank" 
                           class="text-white-50 text-decoration-none" title="GitHub Repository">
                            <i class="fab fa-github fa-lg"></i>
                        </a>
                    </div>
                </div>
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
                                
                                <!-- 1. Carga Horária Concluída (Prioridade 1) -->
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span><i class="fas fa-clock me-2 text-warning"></i>Carga Horária Concluída</span>
                                        <span class="fw-bold"><?php echo $carga_horaria_concluida; ?>h de <?php echo $carga_horaria_total; ?>h</span>
                                    </div>
                                    <div class="progress progress-custom mb-1">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $progresso_carga_horaria; ?>%">
                                            <?php echo $progresso_carga_horaria; ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">Horas das disciplinas concluídas, aproveitadas ou dispensadas</small>
                                </div>

                                <!-- 2. Disciplinas Concluídas (Prioridade 2) -->
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span><i class="fas fa-book me-2 text-success"></i>Disciplinas Concluídas</span>
                                        <span class="fw-bold"><?php echo $disciplinas_concluidas; ?> de <?php echo $total_disciplinas; ?></span>
                                    </div>
                                    <div class="progress progress-custom mb-1">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progresso_disciplinas; ?>%">
                                            <?php echo $progresso_disciplinas; ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">Disciplinas concluídas, aproveitadas ou dispensadas</small>
                                </div>

                                <!-- 3. Tópicos Concluídos (Prioridade 3) -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span><i class="fas fa-list-ul me-2 text-primary"></i>Tópicos Concluídos</span>
                                        <span class="fw-bold"><?php echo $topicos_concluidos; ?> de <?php echo $total_topicos; ?></span>
                                    </div>
                                    <div class="progress progress-custom mb-1">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $progresso_geral; ?>%">
                                            <?php echo $progresso_geral; ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">Progresso baseado em tópicos de estudo</small>
                                </div>

                                <!-- Estatísticas Resumidas -->
                                <div class="row mt-4 pt-3 border-top">
                                    <div class="col-md-4 text-center">
                                        <div class="bg-light p-3 rounded">
                                            <h6 class="text-warning mb-1"><?php echo $progresso_carga_horaria; ?>%</h6>
                                            <small class="text-muted">Por Carga Horária</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="bg-light p-3 rounded">
                                            <h6 class="text-success mb-1"><?php echo $progresso_disciplinas; ?>%</h6>
                                            <small class="text-muted">Por Disciplinas</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="bg-light p-3 rounded">
                                            <h6 class="text-primary mb-1"><?php echo $progresso_geral; ?>%</h6>
                                            <small class="text-muted">Por Tópicos</small>
                                        </div>
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
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i>Tópicos Urgentes</h5>
                                <small class="text-muted"><?php echo count($topicos_urgentes); ?> tópicos</small>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <?php if (empty($topicos_urgentes)): ?>
                                    <p class="text-muted text-center">Nenhum tópico urgente encontrado.</p>
                                <?php else: ?>
                                    <?php foreach ($topicos_urgentes as $topico): ?>
                                        <?php 
                                            $dias = diasAtePrazo($topico['prazo_final']);
                                            $status_prazo = statusPrazo($dias);
                                            $status_topico = statusTopico($topico['concluido']);
                                        ?>
                                        <div class="topic-item mb-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($topico['titulo']); ?></h6>
                                                    <p class="text-muted mb-2"><?php echo htmlspecialchars($topico['disciplina_nome']); ?></p>
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        <!-- Status do Tópico -->
                                                        <span class="badge <?php echo $status_topico['class']; ?>">
                                                            <i class="fas <?php echo $status_topico['icon']; ?> me-1"></i>
                                                            <?php echo $status_topico['texto']; ?>
                                                        </span>
                                                        
                                                        <!-- Status do Prazo -->
                                                        <span class="badge <?php echo $status_prazo['class']; ?>">
                                                            <i class="fas fa-calendar me-1"></i>
                                                            <?php echo $status_prazo['texto']; ?>
                                                        </span>
                                                        
                                                        <!-- Data do Prazo -->
                                                        <span class="badge bg-light text-dark">
                                                            <?php echo formatarData($topico['prazo_final']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Unidades de Aprendizagem -->
                    <div class="col-md-6 mb-4">
                        <div class="card card-custom">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-graduation-cap me-2"></i>Unidades de Aprendizagem</h5>
                                <small class="text-muted"><?php echo count($unidades_aprendizagem); ?> unidades</small>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <?php if (empty($unidades_aprendizagem)): ?>
                                    <p class="text-muted text-center">Nenhuma unidade de aprendizagem encontrada nas disciplinas ativas.</p>
                                <?php else: ?>
                                    <?php 
                                    $disciplina_atual = '';
                                    foreach ($unidades_aprendizagem as $unidade): 
                                        $status = statusUnidade($unidade['concluido'], $unidade['data_prazo']);
                                        $prazo_info = statusPrazo(diasAtePrazo($unidade['data_prazo']));
                                        
                                        // Separador por disciplina
                                        if ($disciplina_atual !== $unidade['disciplina_nome']):
                                            if ($disciplina_atual !== '') echo '<hr class="my-2">';
                                            $disciplina_atual = $unidade['disciplina_nome'];
                                    ?>
                                            <div class="fw-bold text-primary mb-2">
                                                <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($disciplina_atual); ?>
                                            </div>
                                    <?php endif; ?>
                                        
                                        <div class="unidade-item mb-2 p-2 border rounded">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($unidade['titulo']); ?></h6>
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        <!-- Status -->
                                                        <span class="badge <?php echo $status['class']; ?>">
                                                            <i class="fas <?php echo $status['icon']; ?> me-1"></i>
                                                            <?php echo $status['texto']; ?>
                                                        </span>
                                                        
                                                        <!-- Prazo -->
                                                        <?php if ($unidade['data_prazo']): ?>
                                                            <span class="badge bg-light text-dark">
                                                                <i class="fas fa-calendar me-1"></i>
                                                                <?php echo formatarData($unidade['data_prazo']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Nota (apenas para unidades concluídas) -->
                                                        <?php if ($unidade['nota'] !== null && $unidade['concluido'] == 1): ?>
                                                            <span class="badge bg-dark">
                                                                <i class="fas fa-star me-1"></i>
                                                                Nota: <?php echo number_format($unidade['nota'], 1); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
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
                                                    <span class="badge bg-info"><?php 
                                                        $statusMap = [0 => 'Ativa', 1 => 'Concluída', 2 => 'A Cursar', 3 => 'Aproveitada', 4 => 'Dispensada'];
                                                        echo $statusMap[$disciplina['status']] ?? 'Desconhecido'; 
                                                    ?></span>
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
        console.log('Progresso por tópicos:', <?php echo $progresso_geral; ?>);
        console.log('Progresso por disciplinas:', <?php echo $total_disciplinas > 0 ? round(($disciplinas_concluidas / $total_disciplinas) * 100) : 0; ?>);
        console.log('Progresso por carga horária:', <?php echo $progresso_carga_horaria; ?>);
        console.log('Carga horária:', '<?php echo $carga_horaria_concluida; ?>h / <?php echo $carga_horaria_total; ?>h');
        console.log('Disciplinas:', '<?php echo $disciplinas_concluidas; ?> / <?php echo $total_disciplinas; ?>');
        console.log('Tópicos:', '<?php echo $topicos_concluidos; ?> / <?php echo $total_topicos; ?>');
    </script>
</body>
</html>
<?php
error_log("DASHBOARD: HTML renderizado com sucesso");
?>
