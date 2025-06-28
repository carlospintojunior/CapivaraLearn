<?php
/**
 * CRUD Simplificado de Tópicos
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

// Buscar disciplinas do usuário para os selects
$disciplinas = $database->select("disciplinas", 
    ["id", "nome"],
    ["usuario_id" => $user_id, "ORDER" => "nome"]
);

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $nome = trim($_POST['nome'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $disciplina_id = intval($_POST['disciplina_id'] ?? 0);
            $ordem = intval($_POST['ordem'] ?? 0);
            $data_prazo = trim($_POST['data_prazo'] ?? '') ?: null;
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            
            if (empty($nome) || $disciplina_id <= 0) {
                $error = 'Nome e disciplina são obrigatórios';
            } else {
                // Verificar se a disciplina pertence ao usuário
                $disciplina_check = $database->get("disciplinas", "id", [
                    "id" => $disciplina_id,
                    "usuario_id" => $user_id
                ]);
                
                if (!$disciplina_check) {
                    $error = 'Disciplina não encontrada ou não pertence ao usuário';
                } else {
                    $result = $database->insert("topicos", [
                        "nome" => $nome,
                        "descricao" => $descricao,
                        "disciplina_id" => $disciplina_id,
                        "usuario_id" => $user_id,
                        "ordem" => $ordem,
                        "data_prazo" => $data_prazo,
                        "ativo" => $ativo
                    ]);
                    
                    if ($result->rowCount()) {
                        $message = 'Tópico criado com sucesso!';
                    } else {
                        $error = 'Erro ao criar tópico: ' . implode(', ', $database->error());
                    }
                }
            }
            break;
            
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $disciplina_id = intval($_POST['disciplina_id'] ?? 0);
            $ordem = intval($_POST['ordem'] ?? 0);
            $data_prazo = trim($_POST['data_prazo'] ?? '') ?: null;
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            
            if (empty($nome) || $disciplina_id <= 0 || $id <= 0) {
                $error = 'Nome, disciplina e ID são obrigatórios';
            } else {
                // Verificar se o tópico pertence ao usuário
                $topico_check = $database->get("topicos", "id", [
                    "id" => $id,
                    "usuario_id" => $user_id
                ]);
                
                // Verificar se a disciplina pertence ao usuário
                $disciplina_check = $database->get("disciplinas", "id", [
                    "id" => $disciplina_id,
                    "usuario_id" => $user_id
                ]);
                
                if (!$topico_check) {
                    $error = 'Tópico não encontrado ou não pertence ao usuário';
                } elseif (!$disciplina_check) {
                    $error = 'Disciplina não encontrada ou não pertence ao usuário';
                } else {
                    $result = $database->update("topicos", [
                        "nome" => $nome,
                        "descricao" => $descricao,
                        "disciplina_id" => $disciplina_id,
                        "ordem" => $ordem,
                        "data_prazo" => $data_prazo,
                        "ativo" => $ativo
                    ], [
                        "id" => $id,
                        "usuario_id" => $user_id
                    ]);
                    
                    if ($result->rowCount() >= 0) {
                        $message = 'Tópico atualizado com sucesso!';
                    } else {
                        $error = 'Erro ao atualizar tópico: ' . implode(', ', $database->error());
                    }
                }
            }
            break;
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                $error = 'ID inválido';
            } else {
                // Verificar se o tópico pertence ao usuário
                $topico_check = $database->get("topicos", "id", [
                    "id" => $id,
                    "usuario_id" => $user_id
                ]);
                
                if (!$topico_check) {
                    $error = 'Tópico não encontrado ou não pertence ao usuário';
                } else {
                    $result = $database->delete("topicos", [
                        "id" => $id,
                        "usuario_id" => $user_id
                    ]);
                    
                    if ($result->rowCount()) {
                        $message = 'Tópico excluído com sucesso!';
                    } else {
                        $error = 'Erro ao excluir tópico: ' . implode(', ', $database->error());
                    }
                }
            }
            break;
    }
}

// Buscar tópicos do usuário com nome da disciplina
$topicos = $database->select("topicos", [
    "[>]disciplinas" => ["disciplina_id" => "id"]
], [
    "topicos.id",
    "topicos.nome",
    "topicos.descricao",
    "topicos.disciplina_id",
    "disciplinas.nome(disciplina_nome)",
    "topicos.ordem",
    "topicos.data_prazo",
    "topicos.ativo",
    "topicos.data_criacao"
], [
    "topicos.usuario_id" => $user_id,
    "ORDER" => ["disciplinas.nome", "topicos.ordem", "topicos.nome"]
]);

// Buscar tópico para edição
$editando = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $editando = $database->get("topicos", "*", [
        "id" => $edit_id,
        "usuario_id" => $user_id
    ]);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Tópicos - CapivaraLearn</title>
    
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
                <h2><i class="fas fa-list-ul"></i> Gerenciar Tópicos</h2>
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
                        <?php echo $editando ? 'Editar Tópico' : 'Novo Tópico'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $editando ? 'update' : 'create'; ?>">
                        <?php if ($editando): ?>
                            <input type="hidden" name="id" value="<?php echo $editando['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="nome" name="nome" 
                                   value="<?php echo htmlspecialchars($editando['nome'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($editando['descricao'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="disciplina_id" class="form-label">Disciplina *</label>
                            <select class="form-select" id="disciplina_id" name="disciplina_id" required>
                                <option value="">Selecione uma disciplina</option>
                                <?php foreach ($disciplinas as $disciplina): ?>
                                    <option value="<?php echo $disciplina['id']; ?>" 
                                            <?php echo ($editando && $editando['disciplina_id'] == $disciplina['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($disciplina['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ordem" class="form-label">Ordem</label>
                            <input type="number" class="form-control" id="ordem" name="ordem" 
                                   value="<?php echo $editando['ordem'] ?? 0; ?>" min="0">
                            <div class="form-text">Ordem de exibição do tópico (0 = primeiro)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="data_prazo" class="form-label">Data Limite</label>
                            <input type="date" class="form-control" id="data_prazo" name="data_prazo" 
                                   value="<?php echo $editando['data_prazo'] ?? ''; ?>">
                            <div class="form-text">Data limite para conclusão do tópico (opcional)</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="ativo" name="ativo" 
                                   <?php echo (!$editando || $editando['ativo']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="ativo">Ativo</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $editando ? 'Atualizar' : 'Criar'; ?>
                            </button>
                            <?php if ($editando): ?>
                                <a href="topics_simple.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista de Tópicos -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Tópicos Cadastrados
                        <span class="badge bg-primary ms-2"><?php echo count($topicos); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($topicos)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle"></i>
                            Nenhum tópico cadastrado. Crie o primeiro tópico usando o formulário ao lado.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Disciplina</th>
                                        <th>Ordem</th>
                                        <th>Data Limite</th>
                                        <th>Status</th>
                                        <th>Criado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topicos as $topico): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($topico['nome']); ?></strong>
                                                <?php if ($topico['descricao']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($topico['descricao'], 0, 50)) . (strlen($topico['descricao']) > 50 ? '...' : ''); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($topico['disciplina_nome']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $topico['ordem']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($topico['data_prazo']): ?>
                                                    <?php 
                                                    $hoje = new DateTime();
                                                    $prazo = new DateTime($topico['data_prazo']);
                                                    $diff = $hoje->diff($prazo);
                                                    $dias_restantes = $prazo < $hoje ? -$diff->days : $diff->days;
                                                    
                                                    $classe_prazo = 'bg-secondary';
                                                    if ($dias_restantes < 0) $classe_prazo = 'bg-danger';
                                                    elseif ($dias_restantes == 0) $classe_prazo = 'bg-warning';
                                                    elseif ($dias_restantes <= 3) $classe_prazo = 'bg-danger';
                                                    elseif ($dias_restantes <= 7) $classe_prazo = 'bg-warning';
                                                    else $classe_prazo = 'bg-success';
                                                    ?>
                                                    <span class="badge <?php echo $classe_prazo; ?>">
                                                        <?php echo date('d/m/Y', strtotime($topico['data_prazo'])); ?>
                                                        <?php if ($dias_restantes < 0): ?>
                                                            <br><small><?php echo abs($dias_restantes); ?> dias atrasado</small>
                                                        <?php elseif ($dias_restantes == 0): ?>
                                                            <br><small>Vence hoje!</small>
                                                        <?php elseif ($dias_restantes <= 7): ?>
                                                            <br><small><?php echo $dias_restantes; ?> dias</small>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Sem prazo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($topico['ativo']): ?>
                                                    <span class="badge bg-success">Ativo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Inativo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo date('d/m/Y H:i', strtotime($topico['data_criacao'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="?edit=<?php echo $topico['id']; ?>" class="btn btn-outline-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="confirmarExclusao(<?php echo $topico['id']; ?>, '<?php echo htmlspecialchars($topico['nome'], ENT_QUOTES); ?>')" 
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
                <p>Tem certeza que deseja excluir o tópico <strong id="nomeTopico"></strong>?</p>
                <p class="text-danger"><small><i class="fas fa-warning"></i> Esta ação não pode ser desfeita.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="idTopico">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id, nome) {
    document.getElementById('idTopico').value = id;
    document.getElementById('nomeTopico').textContent = nome;
    new bootstrap.Modal(document.getElementById('modalExclusao')).show();
}
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
