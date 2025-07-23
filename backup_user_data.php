<?php
// Configuração essencial
require_once __DIR__ . '/includes/config.php';

// Verificar login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Carregar dependências
require_once 'Medoo.php';

// Configuração do banco
$database = new Medoo\Medoo([
    'type' => 'mysql',
    'host' => DB_HOST,
    'database' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'charset' => 'utf8mb4'
]);

$user_id = $_SESSION['user_id'];

// Processar download do backup
if (isset($_GET['download']) && $_GET['download'] === 'true') {
    try {
        // Buscar dados do usuário
        $user_data = $database->get("usuarios", "*", ["id" => $user_id]);
        
        // Buscar universidades
        $universidades = $database->select("universidades", "*", ["usuario_id" => $user_id]);
        
        // Buscar cursos
        $cursos = $database->select("cursos", "*", ["usuario_id" => $user_id]);
        
        // Buscar disciplinas
        $disciplinas = $database->select("disciplinas", "*", ["usuario_id" => $user_id]);
        
        // Buscar tópicos
        $topicos = $database->select("topicos", "*", ["usuario_id" => $user_id]);
        
        // Buscar unidades de aprendizagem
        $unidades = $database->select("unidades_aprendizagem", "*", ["usuario_id" => $user_id]);
        
        // Buscar matrículas
        $matriculas = $database->select("matriculas", "*", ["usuario_id" => $user_id]);
        
        // Remover dados sensíveis do usuário
        unset($user_data['senha']);
        
        // Estrutura do backup
        $backup_data = [
            'backup_info' => [
                'version' => APP_VERSION ?? '1.1.0',
                'created_at' => date('Y-m-d H:i:s'),
                'user_id' => $user_id,
                'user_name' => $user_data['nome'],
                'type' => 'complete_user_data'
            ],
            'user_profile' => $user_data,
            'universities' => $universidades,
            'courses' => $cursos,
            'subjects' => $disciplinas,
            'topics' => $topicos,
            'learning_units' => $unidades,
            'enrollments' => $matriculas,
            'statistics' => [
                'total_universities' => count($universidades),
                'total_courses' => count($cursos),
                'total_subjects' => count($disciplinas),
                'total_topics' => count($topicos),
                'total_learning_units' => count($unidades),
                'total_enrollments' => count($matriculas),
                'completed_subjects' => count(array_filter($disciplinas, fn($d) => $d['concluido'] == 1)),
                'completed_topics' => count(array_filter($topicos, fn($t) => $t['concluido'] == 1))
            ]
        ];
        
        $json_data = json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        $filename = 'capivaralearn_backup_user_' . $user_id . '_' . date('Y-m-d_H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json_data));
        
        echo $json_data;
        exit;
        
    } catch (Exception $e) {
        $error_message = "Erro ao gerar backup: " . $e->getMessage();
    }
}

// Estatísticas para exibição
$stats = [];
try {
    $stats = [
        'universidades' => $database->count("universidades", ["usuario_id" => $user_id]),
        'cursos' => $database->count("cursos", ["usuario_id" => $user_id]),
        'disciplinas' => $database->count("disciplinas", ["usuario_id" => $user_id]),
        'topicos' => $database->count("topicos", ["usuario_id" => $user_id]),
        'unidades' => $database->count("unidades_aprendizagem", ["usuario_id" => $user_id]),
        'matriculas' => $database->count("matriculas", ["usuario_id" => $user_id])
    ];
} catch (Exception $e) {
    $error_message = "Erro ao carregar estatísticas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup de Dados do Usuário - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stats-card {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            color: white;
        }
        .download-section {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <div class="card card-custom">
                    <div class="card-body text-center bg-gradient-primary text-white">
                        <h1 class="display-6 mb-3">
                            <i class="fas fa-download me-3"></i>
                            Backup de Dados do Usuário
                        </h1>
                        <p class="lead mb-0">Exportar todos os seus dados acadêmicos em formato JSON</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegação -->
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-white p-3 rounded shadow-sm">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php" class="text-decoration-none">
                                <i class="fas fa-home me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="breadcrumb-item active">Backup de Dados</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Seus Dados Acadêmicos</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 mb-3">
                                <div class="stats-card text-center">
                                    <h3><?php echo $stats['universidades'] ?? 0; ?></h3>
                                    <small>Universidades</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="stats-card text-center">
                                    <h3><?php echo $stats['cursos'] ?? 0; ?></h3>
                                    <small>Cursos</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="stats-card text-center">
                                    <h3><?php echo $stats['disciplinas'] ?? 0; ?></h3>
                                    <small>Disciplinas</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="stats-card text-center">
                                    <h3><?php echo $stats['topicos'] ?? 0; ?></h3>
                                    <small>Tópicos</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="stats-card text-center">
                                    <h3><?php echo $stats['unidades'] ?? 0; ?></h3>
                                    <small>Unidades</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="stats-card text-center">
                                    <h3><?php echo $stats['matriculas'] ?? 0; ?></h3>
                                    <small>Matrículas</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção de Download -->
        <div class="row">
            <div class="col-12">
                <div class="download-section text-center">
                    <h3 class="mb-4">
                        <i class="fas fa-cloud-download-alt me-3 text-primary"></i>
                        Gerar Backup Completo
                    </h3>
                    
                    <div class="row mb-4">
                        <div class="col-md-10 mx-auto">
                            <div class="card border-0 shadow">
                                <div class="card-body">
                                    <h6 class="text-muted mb-3">O que será incluído no backup completo:</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul class="list-unstyled text-start">
                                                <li><i class="fas fa-check text-success me-2"></i>Perfil do usuário</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Universidades cadastradas</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Cursos e estruturas completas</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Disciplinas com status de conclusão</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <ul class="list-unstyled text-start">
                                                <li><i class="fas fa-check text-success me-2"></i>Tópicos com prazos e progresso</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Unidades com notas e avaliações</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Matrículas com notas finais</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Estatísticas de progresso</li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-success mt-3 mb-0">
                                        <h6 class="alert-heading">
                                            <i class="fas fa-star me-2"></i>Backup Completo
                                        </h6>
                                        <p class="mb-2"><strong>Este backup inclui:</strong></p>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <ul class="mb-0">
                                                    <li>✅ Todas as notas</li>
                                                    <li>✅ Status de conclusão</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <ul class="mb-0">
                                                    <li>✅ Prazos pessoais</li>
                                                    <li>✅ Progresso completo</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <hr class="my-2">
                                        <small class="mb-0">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Para exportar apenas estrutura (sem notas), use o <a href="backup_grade.php" class="alert-link">Backup de Grade Curricular</a>.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="?download=true" class="btn btn-primary btn-lg px-5 py-3">
                        <i class="fas fa-download me-2"></i>
                        Baixar Backup Completo
                    </a>
                    
                    <div class="mt-4">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            O arquivo será salvo no formato JSON com timestamp atual
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informações Adicionais -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Informações Importantes</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">🔒 Segurança</h6>
                                <ul class="list-unstyled">
                                    <li><small>• Senha não é incluída no backup</small></li>
                                    <li><small>• Dados pessoais são preservados</small></li>
                                    <li><small>• Arquivo em formato JSON legível</small></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success">📁 Uso do Backup</h6>
                                <ul class="list-unstyled">
                                    <li><small>• Migração entre contas</small></li>
                                    <li><small>• Backup de segurança pessoal</small></li>
                                    <li><small>• Análise de dados acadêmicos</small></li>
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
