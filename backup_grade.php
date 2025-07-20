<?php
/**
 * CapivaraLearn - Sistema de Backup de Grade Curricular
 * 
 * Exporta a estrutura completa de um curso para compartilhamento
 * Inclui: Curso → Disciplinas → Tópicos → Unidades de Aprendizagem
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/Medoo.php';

use Medoo\Medoo;

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar login
if (!isset($_SESSION['user_id'])) {
    header('Location: /CapivaraLearn/login.php');
    exit;
}

// Configurar Medoo
$database = new Medoo([
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'capivaralearn',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
]);

$user_id = $_SESSION['user_id'];

// Processar exportação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export') {
    $curso_id = intval($_POST['curso_id'] ?? 0);
    
    if ($curso_id <= 0) {
        $error = 'Selecione um curso válido para exportar.';
    } else {
        try {
            // Verificar se o curso pertence ao usuário
            $curso = $database->get('cursos', '*', [
                'id' => $curso_id,
                'usuario_id' => $user_id
            ]);
            
            if (!$curso) {
                throw new Exception('Curso não encontrado ou não pertence ao usuário.');
            }
            
            // Buscar universidade do curso
            $universidade = $database->get('universidades', '*', [
                'id' => $curso['universidade_id']
            ]);
            
            // Buscar disciplinas do curso
            $disciplinas = $database->select('disciplinas', '*', [
                'curso_id' => $curso_id,
                'usuario_id' => $user_id,
                'ORDER' => ['nome' => 'ASC']
            ]);
            
            // Para cada disciplina, buscar tópicos e unidades
            foreach ($disciplinas as &$disciplina) {
                // Buscar tópicos da disciplina
                $topicos = $database->select('topicos', '*', [
                    'disciplina_id' => $disciplina['id'],
                    'usuario_id' => $user_id,
                    'ORDER' => ['ordem' => 'ASC', 'nome' => 'ASC']
                ]);
                
                // Para cada tópico, buscar unidades de aprendizagem
                foreach ($topicos as &$topico) {
                    $unidades = $database->select('unidades_aprendizagem', '*', [
                        'topico_id' => $topico['id'],
                        'usuario_id' => $user_id,
                        'ORDER' => ['nome' => 'ASC']
                    ]);
                    
                    // Remover campos específicos do usuário e dados de progresso das unidades
                    foreach ($unidades as &$unidade) {
                        unset(
                            $unidade['id'], 
                            $unidade['topico_id'], 
                            $unidade['usuario_id'],
                            $unidade['nota'],           // Remover nota
                            $unidade['concluido'],      // Remover status de conclusão
                            $unidade['data_prazo'],     // Remover prazo específico do usuário
                            $unidade['data_criacao'],
                            $unidade['data_atualizacao']
                        );
                    }
                    
                    $topico['unidades_aprendizagem'] = $unidades;
                    // Remover campos específicos do usuário e dados de progresso do tópico
                    unset(
                        $topico['id'], 
                        $topico['disciplina_id'], 
                        $topico['usuario_id'],
                        $topico['concluido'],       // Remover status de conclusão
                        $topico['data_prazo'],      // Remover prazo específico do usuário
                        $topico['data_criacao'],
                        $topico['data_atualizacao']
                    );
                }
                
                $disciplina['topicos'] = $topicos;
                // Remover campos específicos do usuário e dados de progresso da disciplina
                unset(
                    $disciplina['id'], 
                    $disciplina['curso_id'], 
                    $disciplina['usuario_id'],
                    $disciplina['concluido'],       // Remover status de conclusão
                    $disciplina['data_criacao'],
                    $disciplina['data_atualizacao']
                );
            }
            
            // Estrutura completa da grade
            $grade_curricular = [
                'metadata' => [
                    'versao_sistema' => APP_VERSION ?? '1.0.0',
                    'data_exportacao' => date('Y-m-d H:i:s'),
                    'exportado_por' => $_SESSION['user_name'] ?? 'Usuário',
                    'tipo' => 'grade_curricular_capivaralearn'
                ],
                'universidade' => [
                    'nome' => $universidade['nome'],
                    'sigla' => $universidade['sigla'],
                    'pais' => $universidade['pais'],
                    'cidade' => $universidade['cidade'],
                    'estado' => $universidade['estado'],
                    'site' => $universidade['site']
                ],
                'curso' => [
                    'nome' => $curso['nome'],
                    'descricao' => $curso['descricao'],
                    'nivel' => $curso['nivel'],
                    'carga_horaria' => $curso['carga_horaria']
                ],
                'disciplinas' => $disciplinas,
                'estatisticas' => [
                    'total_disciplinas' => count($disciplinas),
                    'total_topicos' => array_sum(array_map(function($d) { return count($d['topicos']); }, $disciplinas)),
                    'total_unidades' => array_sum(array_map(function($d) { 
                        return array_sum(array_map(function($t) { return count($t['unidades_aprendizagem']); }, $d['topicos'])); 
                    }, $disciplinas))
                ]
            ];
            
            // Gerar nome do arquivo
            $nome_arquivo = 'grade_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $curso['nome']) . '_' . date('Y-m-d_H-i-s') . '.json';
            
            // Configurar headers para download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
            
            // Enviar JSON formatado
            echo json_encode($grade_curricular, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
            
        } catch (Exception $e) {
            $error = 'Erro ao exportar grade: ' . $e->getMessage();
        }
    }
}

// Buscar cursos do usuário
$cursos = $database->select('cursos', [
    'id',
    'nome',
    'descricao'
], [
    'usuario_id' => $user_id,
    'ORDER' => ['nome' => 'ASC']
]);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup de Grade Curricular - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-download me-2"></i>
                            Backup de Grade Curricular
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <h5><i class="fas fa-info-circle me-2"></i>O que será exportado?</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-university me-2 text-primary"></i>Dados da universidade</span>
                                    <span class="badge bg-primary rounded-pill">Estrutural</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-graduation-cap me-2 text-success"></i>Informações do curso</span>
                                    <span class="badge bg-success rounded-pill">Estrutural</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-book me-2 text-info"></i>Todas as disciplinas</span>
                                    <span class="badge bg-info rounded-pill">Estrutural</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-list me-2 text-warning"></i>Todos os tópicos</span>
                                    <span class="badge bg-warning rounded-pill">Estrutural</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-play-circle me-2 text-danger"></i>Todas as unidades de aprendizagem</span>
                                    <span class="badge bg-danger rounded-pill">Estrutural</span>
                                </li>
                            </ul>
                            
                            <div class="alert alert-warning mt-3">
                                <h6 class="alert-heading">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Importante
                                </h6>
                                <p class="mb-2"><strong>Não serão exportados:</strong></p>
                                <ul class="mb-0">
                                    <li>Notas e avaliações</li>
                                    <li>Status de conclusão</li>
                                    <li>Prazos específicos</li>
                                    <li>Progresso pessoal</li>
                                </ul>
                                <hr>
                                <small class="mb-0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Para incluir notas e progresso, use o <a href="backup_user_data.php" class="alert-link">Backup Completo de Dados</a>.
                                </small>
                            </div>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="export">
                            
                            <div class="mb-3">
                                <label for="curso_id" class="form-label">
                                    <strong>Selecione o curso para exportar:</strong>
                                </label>
                                <select class="form-select" id="curso_id" name="curso_id" required>
                                    <option value="">Escolha um curso...</option>
                                    <?php foreach ($cursos as $curso): ?>
                                        <option value="<?= $curso['id'] ?>">
                                            <?= htmlspecialchars($curso['nome']) ?>
                                            <?php if ($curso['descricao']): ?>
                                                - <?= htmlspecialchars(substr($curso['descricao'], 0, 50)) ?>...
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Será gerado um arquivo JSON com toda a estrutura do curso selecionado.
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-download me-2"></i>
                                    Exportar Grade Curricular
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Voltar ao Dashboard
                                </a>
                            </div>
                        </form>
                        
                        <?php if (empty($cursos)): ?>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Nenhum curso encontrado!</strong><br>
                                Você precisa ter pelo menos um curso cadastrado para fazer o backup.
                                <a href="crud/courses_simple.php" class="alert-link">Cadastrar curso</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-tools me-2"></i>
                            Ferramentas de Manutenção
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-success">
                                    <div class="card-body text-center">
                                        <i class="fas fa-upload fa-2x text-success mb-2"></i>
                                        <h6>Importar Grade</h6>
                                        <p class="text-muted small">Importar estrutura curricular</p>
                                        <a href="import_grade.php" class="btn btn-success btn-sm">
                                            <i class="fas fa-upload me-1"></i>Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-info">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-shield fa-2x text-info mb-2"></i>
                                        <h6>Backup Completo</h6>
                                        <p class="text-muted small">Backup de todos os dados</p>
                                        <a href="backup_user_data.php" class="btn btn-info btn-sm">
                                            <i class="fas fa-download me-1"></i>Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-warning">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-cog fa-2x text-warning mb-2"></i>
                                        <h6>Restaurar Dados</h6>
                                        <p class="text-muted small">Restaurar backup completo</p>
                                        <a href="restore_user_data.php" class="btn btn-warning btn-sm">
                                            <i class="fas fa-upload me-1"></i>Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-secondary">
                                    <div class="card-body text-center">
                                        <i class="fas fa-history fa-2x text-secondary mb-2"></i>
                                        <h6>Changelog</h6>
                                        <p class="text-muted small">Histórico de versões</p>
                                        <a href="changelog.php" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-history me-1"></i>Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
