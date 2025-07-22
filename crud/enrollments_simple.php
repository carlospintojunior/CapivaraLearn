<?php
/**
 * CRUD Simplificado de Matrículas
 * Sistema CapivaraLearn
 */

// Configuração simplificada
require_once __DIR__ . '/../Medoo.php';
// Configuração de logging Monolog
require_once __DIR__ . '/../includes/logger_config.php';

use Medoo\Medoo;

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar login simples
if (!isset($_SESSION['user_id'])) {
    header('Location: /CapivaraLearn/login.php');
    exit;
}

// Configurar Medoo
$database = new Medoo([
    'type' => 'mysql',
    'host' => DB_HOST,
    'database' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'charset' => 'utf8mb4'
]);

$user_id = $_SESSION['user_id'];
// Log de acesso ao CRUD de Matrículas
try {
    logInfo('CRUD Matrículas acessado', ['user_id' => $user_id]);
} catch (Exception $e) {
    // falha no log não impede execução
}
$message = '';
$error = '';

// Buscar universidades e cursos do usuário para os selects
try {
    $universidades = $database->select("universidades", 
        ["id", "nome"],
        ["usuario_id" => $user_id, "ORDER" => "nome"]
    );
    logInfo('Universidades carregadas', ['user_id' => $user_id, 'count' => count($universidades)]);
} catch (Exception $e) {
    logError('Erro ao carregar universidades', ['user_id' => $user_id, 'error' => $e->getMessage()]);
    $universidades = [];
}

try {
    $cursos = $database->select("cursos", 
        ["id", "nome"],
        ["usuario_id" => $user_id, "ORDER" => "nome"]
    );
    logInfo('Cursos carregados', ['user_id' => $user_id, 'count' => count($cursos)]);
} catch (Exception $e) {
    logError('Erro ao carregar cursos', ['user_id' => $user_id, 'error' => $e->getMessage()]);
    $cursos = [];
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    logInfo('Ação POST recebida', ['user_id' => $user_id, 'action' => $action]);
    
    switch ($action) {
        case 'create':
            try {
                $numero_matricula = trim($_POST['numero_matricula'] ?? '');
                $curso_id = intval($_POST['curso_id'] ?? 0);
                $status = $_POST['status'] ?? 'ativo';
                $progresso = floatval($_POST['progresso'] ?? 0.00);
                $nota_final = !empty($_POST['nota_final']) ? floatval($_POST['nota_final']) : null;
                
                logInfo('Tentativa de criar matrícula', [
                    'user_id' => $user_id,
                    'curso_id' => $curso_id,
                    'status' => $status
                ]);
                
                if ($curso_id <= 0) {
                    $error = 'Curso é obrigatório';
                    logWarning('Curso obrigatório faltando', ['user_id' => $user_id, 'error' => $error]);
                } else {
                    // Buscar o curso e sua universidade
                    $curso_info = $database->get("cursos", [
                        "id",
                        "nome", 
                        "universidade_id"
                    ], [
                        "id" => $curso_id,
                        "usuario_id" => $user_id
                    ]);
                    
                    if (!$curso_info) {
                        $error = 'Curso não encontrado ou não pertence ao usuário';
                        logWarning('Curso inválido', ['user_id' => $user_id, 'curso_id' => $curso_id]);
                    } else {
                        $universidade_id = $curso_info['universidade_id'];
                        
                        // Verificar se já existe uma matrícula para esta combinação
                        $matricula_existente = $database->get("matriculas", "id", [
                            "usuario_id" => $user_id,
                            "universidade_id" => $universidade_id,
                            "curso_id" => $curso_id
                        ]);
                        
                        if ($matricula_existente) {
                            $error = 'Já existe uma matrícula para este curso';
                            logWarning('Matrícula duplicada', ['user_id' => $user_id, 'universidade_id' => $universidade_id, 'curso_id' => $curso_id]);
                        } else {
                            $data_conclusao = ($status === 'concluida') ? date('Y-m-d H:i:s') : null;
                            
                            $result = $database->insert("matriculas", [
                                "numero_matricula" => $numero_matricula ?: null,
                                "usuario_id" => $user_id,
                                "universidade_id" => $universidade_id,
                                "curso_id" => $curso_id,
                                "status" => $status,
                                "progresso" => $progresso,
                                "data_conclusao" => $data_conclusao,
                                "nota_final" => $nota_final
                            ]);
                            
                            if ($result->rowCount()) {
                                $message = 'Matrícula criada com sucesso!';
                                logInfo('Matrícula criada', [
                                    'user_id' => $user_id,
                                    'universidade_id' => $universidade_id,
                                    'curso_id' => $curso_id,
                                    'numero_matricula' => $numero_matricula,
                                    'status' => $status,
                                    'progresso' => $progresso,
                                    'matricula_id' => $database->id()
                                ]);
                            } else {
                                $error = 'Erro ao criar matrícula: ' . implode(', ', $database->error());
                                logError('Erro ao inserir matrícula', [
                                    'user_id' => $user_id,
                                    'universidade_id' => $universidade_id,
                                    'curso_id' => $curso_id,
                                    'error' => $error
                                ]);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $error = 'Erro inesperado: ' . $e->getMessage();
                logError('Exceção na criação de matrícula', ['user_id' => $user_id, 'error' => $e->getMessage()]);
            }
            break;
            
        case 'update':
            try {
                $id = intval($_POST['id'] ?? 0);
                $numero_matricula = trim($_POST['numero_matricula'] ?? '');
                $universidade_id = intval($_POST['universidade_id'] ?? 0);
                $curso_id = intval($_POST['curso_id'] ?? 0);
                $status = $_POST['status'] ?? 'ativo';
                $progresso = floatval($_POST['progresso'] ?? 0.00);
                $nota_final = !empty($_POST['nota_final']) ? floatval($_POST['nota_final']) : null;
                
                logInfo('Tentativa de atualizar matrícula', [
                    'user_id' => $user_id,
                    'matricula_id' => $id,
                    'universidade_id' => $universidade_id,
                    'curso_id' => $curso_id,
                    'status' => $status
                ]);
                
                if ($universidade_id <= 0 || $curso_id <= 0 || $id <= 0) {
                    $error = 'Universidade, curso e ID são obrigatórios';
                    logWarning('Dados obrigatórios faltando na atualização', ['user_id' => $user_id, 'error' => $error]);
                } else {
                    // Verificar se a matrícula pertence ao usuário
                    $matricula_check = $database->get("matriculas", "id", [
                        "id" => $id,
                        "usuario_id" => $user_id
                    ]);
                    
                    if (!$matricula_check) {
                        $error = 'Matrícula não encontrada ou não pertence ao usuário';
                        logWarning('Matrícula não encontrada para atualização', ['user_id' => $user_id, 'matricula_id' => $id]);
                    } else {
                        $data_conclusao = ($status === 'concluido') ? date('Y-m-d H:i:s') : null;
                        
                        $result = $database->update("matriculas", [
                            "numero_matricula" => $numero_matricula ?: null,
                            "universidade_id" => $universidade_id,
                            "curso_id" => $curso_id,
                            "status" => $status,
                            "progresso" => $progresso,
                            "data_conclusao" => $data_conclusao,
                            "nota_final" => $nota_final
                        ], [
                            "id" => $id,
                            "usuario_id" => $user_id
                        ]);
                        
                        if ($result->rowCount() >= 0) {
                            $message = 'Matrícula atualizada com sucesso!';
                            logInfo('Matrícula atualizada', ['user_id' => $user_id, 'matricula_id' => $id]);
                        } else {
                            $error = 'Erro ao atualizar matrícula: ' . implode(', ', $database->error());
                            logError('Erro ao atualizar matrícula', ['user_id' => $user_id, 'matricula_id' => $id, 'error' => $error]);
                        }
                    }
                }
            } catch (Exception $e) {
                $error = 'Erro inesperado: ' . $e->getMessage();
                logError('Exceção na atualização de matrícula', ['user_id' => $user_id, 'error' => $e->getMessage()]);
            }
            break;
            
        case 'delete':
            try {
                $id = intval($_POST['id'] ?? 0);
                
                logInfo('Tentativa de excluir matrícula', ['user_id' => $user_id, 'matricula_id' => $id]);
                
                if ($id <= 0) {
                    $error = 'ID inválido';
                    logWarning('ID inválido para exclusão', ['user_id' => $user_id, 'id' => $id]);
                } else {
                    // Verificar se a matrícula pertence ao usuário
                    $matricula_check = $database->get("matriculas", "id", [
                        "id" => $id,
                        "usuario_id" => $user_id
                    ]);
                    
                    if (!$matricula_check) {
                        $error = 'Matrícula não encontrada ou não pertence ao usuário';
                        logWarning('Matrícula não encontrada para exclusão', ['user_id' => $user_id, 'matricula_id' => $id]);
                    } else {
                        $result = $database->delete("matriculas", [
                            "id" => $id,
                            "usuario_id" => $user_id
                        ]);
                        
                        if ($result->rowCount()) {
                            $message = 'Matrícula excluída com sucesso!';
                            logInfo('Matrícula excluída', ['user_id' => $user_id, 'matricula_id' => $id]);
                        } else {
                            $error = 'Erro ao excluir matrícula: ' . implode(', ', $database->error());
                            logError('Erro ao excluir matrícula', ['user_id' => $user_id, 'matricula_id' => $id, 'error' => $error]);
                        }
                    }
                }
            } catch (Exception $e) {
                $error = 'Erro inesperado: ' . $e->getMessage();
                logError('Exceção na exclusão de matrícula', ['user_id' => $user_id, 'error' => $e->getMessage()]);
            }
            break;
    }
}

// Buscar matrículas do usuário com nomes da universidade e curso
try {
    $matriculas = $database->select("matriculas", [
        "[>]universidades" => ["universidade_id" => "id"],
        "[>]cursos" => ["curso_id" => "id"]
    ], [
        "matriculas.id",
        "matriculas.numero_matricula",
        "matriculas.universidade_id",
        "matriculas.curso_id",
        "universidades.nome(universidade_nome)",
        "cursos.nome(curso_nome)",
        "matriculas.status",
        "matriculas.progresso",
        "matriculas.data_matricula",
        "matriculas.data_conclusao",
        "matriculas.nota_final"
    ], [
        "matriculas.usuario_id" => $user_id,
        "ORDER" => ["universidades.nome", "cursos.nome"]
    ]);
    
    logInfo('Matrículas carregadas', ['user_id' => $user_id, 'count' => count($matriculas)]);
} catch (Exception $e) {
    logError('Erro ao carregar matrículas', ['user_id' => $user_id, 'error' => $e->getMessage()]);
    $matriculas = [];
}

// Buscar matrícula para edição
$editando = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    logInfo('Editando matrícula', ['user_id' => $user_id, 'edit_id' => $edit_id]);
    
    try {
        $editando = $database->get("matriculas", "*", [
            "id" => $edit_id,
            "usuario_id" => $user_id
        ]);
        
        if ($editando) {
            logInfo('Matrícula encontrada para edição', ['user_id' => $user_id, 'matricula_id' => $edit_id]);
        } else {
            logWarning('Matrícula não encontrada para edição', ['user_id' => $user_id, 'edit_id' => $edit_id]);
        }
    } catch (Exception $e) {
        logError('Erro ao buscar matrícula para edição', ['user_id' => $user_id, 'edit_id' => $edit_id, 'error' => $e->getMessage()]);
    }
}

// Opções de status
$status_options = [
    'ativo' => 'Ativo',
    'concluido' => 'Concluído',
    'trancado' => 'Trancado',
    'cancelado' => 'Cancelado'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Matrículas - CapivaraLearn</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }
        .btn-logout {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        .btn-logout:hover {
            background-color: #c82333;
            border-color: #bd2130;
            color: white;
        }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-graduation-cap"></i> Gerenciar Matrículas</h2>
                <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
                </a>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Formulário de Criação/Edição -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-<?php echo $editando ? 'edit' : 'plus'; ?>"></i>
                        <?php echo $editando ? 'Editar Matrícula' : 'Nova Matrícula'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $editando ? 'update' : 'create'; ?>">
                        <?php if ($editando): ?>
                            <input type="hidden" name="id" value="<?php echo $editando['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="numero_matricula" class="form-label">Número de Matrícula</label>
                            <input type="text" class="form-control" id="numero_matricula" name="numero_matricula" 
                                   value="<?php echo $editando['numero_matricula'] ?? ''; ?>" 
                                   placeholder="Ex: 2024001234">
                            <div class="form-text">Número da matrícula fornecido pela instituição (opcional)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="universidade_id" class="form-label">Universidade *</label>
                            <select class="form-select" id="universidade_id" name="universidade_id" required>
                                <option value="">Selecione uma universidade</option>
                                <?php foreach ($universidades as $universidade): ?>
                                    <option value="<?php echo $universidade['id']; ?>" 
                                            <?php echo ($editando && $editando['universidade_id'] == $universidade['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($universidade['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="curso_id" class="form-label">Curso *</label>
                            <select class="form-select" id="curso_id" name="curso_id" required>
                                <option value="">Selecione um curso</option>
                                <?php foreach ($cursos as $curso): ?>
                                    <option value="<?php echo $curso['id']; ?>" 
                                            <?php echo ($editando && $editando['curso_id'] == $curso['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($curso['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <?php foreach ($status_options as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" 
                                            <?php echo ($editando && $editando['status'] == $value) || (!$editando && $value == 'ativo') ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="progresso" class="form-label">Progresso (%)</label>
                            <input type="number" class="form-control" id="progresso" name="progresso" 
                                   value="<?php echo $editando['progresso'] ?? 0.00; ?>" 
                                   min="0" max="100" step="0.01">
                            <div class="form-text">Progresso do curso (0 a 100%)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nota_final" class="form-label">Nota Final</label>
                            <input type="number" class="form-control" id="nota_final" name="nota_final" 
                                   value="<?php echo $editando['nota_final'] ?? ''; ?>" 
                                   min="0" max="10" step="0.01">
                            <div class="form-text">Nota final do curso (0 a 10)</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $editando ? 'Atualizar' : 'Criar'; ?>
                            </button>
                            <?php if ($editando): ?>
                                <a href="enrollments_simple.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista de Matrículas -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Matrículas Cadastradas
                        <span class="badge bg-primary ms-2"><?php echo count($matriculas); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($matriculas)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle"></i>
                            Nenhuma matrícula cadastrada. Crie a primeira matrícula usando o formulário ao lado.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Universidade</th>
                                        <th>Curso</th>
                                        <th>Status</th>
                                        <th>Progresso</th>
                                        <th>Nota</th>
                                        <th>Matrícula</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($matriculas as $matricula): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($matricula['universidade_nome']); ?></strong>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($matricula['curso_nome']); ?></strong>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'ativo' => 'success',
                                                    'concluido' => 'primary',
                                                    'trancado' => 'warning',
                                                    'cancelado' => 'danger'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $status_class[$matricula['status']] ?? 'secondary'; ?>">
                                                    <?php echo $status_options[$matricula['status']] ?? $matricula['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $matricula['progresso']; ?>%" 
                                                         aria-valuenow="<?php echo $matricula['progresso']; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo number_format($matricula['progresso'], 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($matricula['nota_final']): ?>
                                                    <span class="badge bg-info"><?php echo number_format($matricula['nota_final'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?></small>
                                                <?php if ($matricula['data_conclusao']): ?>
                                                    <br><small class="text-success">Concluído: <?php echo date('d/m/Y', strtotime($matricula['data_conclusao'])); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="?edit=<?php echo $matricula['id']; ?>" class="btn btn-outline-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="confirmarExclusao(<?php echo $matricula['id']; ?>, '<?php echo htmlspecialchars($matricula['universidade_nome'] . ' - ' . $matricula['curso_nome'], ENT_QUOTES); ?>')" 
                                                            title="Excluir">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalExclusao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a matrícula <strong id="nomeMatricula"></strong>?</p>
                <p class="text-danger"><small><i class="fas fa-warning"></i> Esta ação não pode ser desfeita.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="idMatricula">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id, nome) {
    document.getElementById('idMatricula').value = id;
    document.getElementById('nomeMatricula').textContent = nome;
    new bootstrap.Modal(document.getElementById('modalExclusao')).show();
}
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>