<?php
/**
 * CRUD Simplificado de Matrículas
 * Sistema CapivaraLearn
 */

// Configuração simplificada
require_once __DIR__ . '/../Medoo.php';

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
    'host' => 'localhost',
    'database' => 'capivaralearn',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
]);

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Buscar universidades e cursos do usuário para os selects
$universidades = $database->select("universidades", 
    ["id", "nome"],
    ["usuario_id" => $user_id, "ORDER" => "nome"]
);

$cursos = $database->select("cursos", 
    ["id", "nome"],
    ["usuario_id" => $user_id, "ORDER" => "nome"]
);

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $numero_matricula = trim($_POST['numero_matricula'] ?? '');
            $universidade_id = intval($_POST['universidade_id'] ?? 0);
            $curso_id = intval($_POST['curso_id'] ?? 0);
            $status = $_POST['status'] ?? 'ativo';
            $progresso = floatval($_POST['progresso'] ?? 0.00);
            $nota_final = !empty($_POST['nota_final']) ? floatval($_POST['nota_final']) : null;
            
            if ($universidade_id <= 0 || $curso_id <= 0) {
                $error = 'Universidade e curso são obrigatórios';
            } else {
                // Verificar se a universidade pertence ao usuário
                $universidade_check = $database->get("universidades", "id", [
                    "id" => $universidade_id,
                    "usuario_id" => $user_id
                ]);
                
                // Verificar se o curso pertence ao usuário
                $curso_check = $database->get("cursos", "id", [
                    "id" => $curso_id,
                    "usuario_id" => $user_id
                ]);
                
                if (!$universidade_check) {
                    $error = 'Universidade não encontrada ou não pertence ao usuário';
                } elseif (!$curso_check) {
                    $error = 'Curso não encontrado ou não pertence ao usuário';
                } else {
                    // Verificar se já existe uma matrícula para esta combinação
                    $matricula_existente = $database->get("inscricoes", "id", [
                        "usuario_id" => $user_id,
                        "universidade_id" => $universidade_id,
                        "curso_id" => $curso_id
                    ]);
                    
                    if ($matricula_existente) {
                        $error = 'Já existe uma matrícula para esta combinação de universidade e curso';
                    } else {
                        $data_conclusao = ($status === 'concluido') ? date('Y-m-d H:i:s') : null;
                        
                        $result = $database->insert("inscricoes", [
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
                        } else {
                            $error = 'Erro ao criar matrícula: ' . implode(', ', $database->error());
                        }
                    }
                }
            }
            break;
            
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $numero_matricula = trim($_POST['numero_matricula'] ?? '');
            $universidade_id = intval($_POST['universidade_id'] ?? 0);
            $curso_id = intval($_POST['curso_id'] ?? 0);
            $status = $_POST['status'] ?? 'ativo';
            $progresso = floatval($_POST['progresso'] ?? 0.00);
            $nota_final = !empty($_POST['nota_final']) ? floatval($_POST['nota_final']) : null;
            
            if ($universidade_id <= 0 || $curso_id <= 0 || $id <= 0) {
                $error = 'Universidade, curso e ID são obrigatórios';
            } else {
                // Verificar se a matrícula pertence ao usuário
                $matricula_check = $database->get("inscricoes", "id", [
                    "id" => $id,
                    "usuario_id" => $user_id
                ]);
                
                // Verificar se a universidade pertence ao usuário
                $universidade_check = $database->get("universidades", "id", [
                    "id" => $universidade_id,
                    "usuario_id" => $user_id
                ]);
                
                // Verificar se o curso pertence ao usuário
                $curso_check = $database->get("cursos", "id", [
                    "id" => $curso_id,
                    "usuario_id" => $user_id
                ]);
                
                if (!$matricula_check) {
                    $error = 'Matrícula não encontrada ou não pertence ao usuário';
                } elseif (!$universidade_check) {
                    $error = 'Universidade não encontrada ou não pertence ao usuário';
                } elseif (!$curso_check) {
                    $error = 'Curso não encontrado ou não pertence ao usuário';
                } else {
                    // Verificar se já existe outra matrícula para esta combinação (exceto a atual)
                    $matricula_existente = $database->get("inscricoes", "id", [
                        "usuario_id" => $user_id,
                        "universidade_id" => $universidade_id,
                        "curso_id" => $curso_id,
                        "id[!]" => $id
                    ]);
                    
                    if ($matricula_existente) {
                        $error = 'Já existe outra matrícula para esta combinação de universidade e curso';
                    } else {
                        $data_conclusao = ($status === 'concluido') ? date('Y-m-d H:i:s') : null;
                        
                        $result = $database->update("inscricoes", [
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
                        } else {
                            $error = 'Erro ao atualizar matrícula: ' . implode(', ', $database->error());
                        }
                    }
                }
            }
            break;
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                $error = 'ID inválido';
            } else {
                // Verificar se a matrícula pertence ao usuário
                $matricula_check = $database->get("inscricoes", "id", [
                    "id" => $id,
                    "usuario_id" => $user_id
                ]);
                
                if (!$matricula_check) {
                    $error = 'Matrícula não encontrada ou não pertence ao usuário';
                } else {
                    $result = $database->delete("inscricoes", [
                        "id" => $id,
                        "usuario_id" => $user_id
                    ]);
                    
                    if ($result->rowCount()) {
                        $message = 'Matrícula excluída com sucesso!';
                    } else {
                        $error = 'Erro ao excluir matrícula: ' . implode(', ', $database->error());
                    }
                }
            }
            break;
    }
}

// Buscar matrículas do usuário com nomes da universidade e curso
$matriculas = $database->select("inscricoes", [
    "[>]universidades" => ["universidade_id" => "id"],
    "[>]cursos" => ["curso_id" => "id"]
], [
    "inscricoes.id",
    "inscricoes.numero_matricula",
    "inscricoes.universidade_id",
    "inscricoes.curso_id",
    "universidades.nome(universidade_nome)",
    "cursos.nome(curso_nome)",
    "inscricoes.status",
    "inscricoes.progresso",
    "inscricoes.data_matricula",
    "inscricoes.data_conclusao",
    "inscricoes.nota_final"
], [
    "inscricoes.usuario_id" => $user_id,
    "ORDER" => ["universidades.nome", "cursos.nome"]
]);

// Buscar matrícula para edição
$editando = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $editando = $database->get("inscricoes", "*", [
        "id" => $edit_id,
        "usuario_id" => $user_id
    ]);
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
