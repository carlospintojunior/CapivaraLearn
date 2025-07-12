<?php
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

$message = '';
$messageType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create':
                $nome = trim($_POST['nome'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $codigo = trim($_POST['codigo'] ?? '');
                $carga_horaria = (int)($_POST['carga_horaria'] ?? 0);
                
                if (empty($nome)) {
                    throw new Exception('Nome da disciplina é obrigatório.');
                }
                
                if ($carga_horaria < 0) {
                    throw new Exception('Carga horária deve ser um número positivo.');
                }
                
                $result = $database->insert('disciplinas', [
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'codigo' => $codigo,
                    'carga_horaria' => $carga_horaria,
                    'usuario_id' => $_SESSION['user_id']
                ]);
                
                $message = 'Disciplina criada com sucesso!';
                $messageType = 'success';
                break;
                
            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $nome = trim($_POST['nome'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $codigo = trim($_POST['codigo'] ?? '');
                $carga_horaria = (int)($_POST['carga_horaria'] ?? 0);
                
                if (empty($nome)) {
                    throw new Exception('Nome da disciplina é obrigatório.');
                }
                
                if ($carga_horaria < 0) {
                    throw new Exception('Carga horária deve ser um número positivo.');
                }
                
                $result = $database->update('disciplinas', [
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'codigo' => $codigo,
                    'carga_horaria' => $carga_horaria
                ], [
                    'id' => $id,
                    'usuario_id' => $_SESSION['user_id']
                ]);
                
                $message = 'Disciplina atualizada com sucesso!';
                $messageType = 'success';
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                
                $result = $database->delete('disciplinas', [
                    'id' => $id,
                    'usuario_id' => $_SESSION['user_id']
                ]);
                
                $message = 'Disciplina excluída com sucesso!';
                $messageType = 'success';
                break;
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Buscar disciplinas
$modules = $database->select('disciplinas', '*', [
    'usuario_id' => $_SESSION['user_id'],
    'ORDER' => ['nome' => 'ASC']
]);

// Buscar disciplina para edição
$editModule = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editModule = $database->get('disciplinas', '*', [
        'id' => $editId,
        'usuario_id' => $_SESSION['user_id']
    ]);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Disciplinas - CapivaraLearn</title>
    
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
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-folder"></i> Gerenciar Disciplinas</h2>
                <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
                </a>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Lista de Disciplinas -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Lista de Disciplinas
                        <small class="text-muted">(<?= count($modules) ?> registros)</small>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($modules)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-folder fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhuma disciplina cadastrada</h5>
                            <p class="text-muted">Use o formulário ao lado para adicionar sua primeira disciplina.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Código</th>
                                        <th>Descrição</th>
                                        <th>Carga Horária</th>
                                        <th width="120">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($modules as $module): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($module['nome']) ?></strong></td>
                                            <td>
                                                <?php if ($module['codigo']): ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($module['codigo']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($module['descricao']): ?>
                                                    <?= htmlspecialchars(strlen($module['descricao']) > 40 ? substr($module['descricao'], 0, 40) . '...' : $module['descricao']) ?>
                                                <?php else: ?>
                                                    <em class="text-muted">Sem descrição</em>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($module['carga_horaria']): ?>
                                                    <span class="badge bg-info"><?= $module['carga_horaria'] ?>h</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?edit=<?= $module['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta disciplina?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $module['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
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
        
        <!-- Formulário de Criação/Edição -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-<?= $editModule ? 'edit' : 'plus' ?>"></i>
                        <?= $editModule ? 'Editar Disciplina' : 'Nova Disciplina' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?= $editModule ? 'update' : 'create' ?>">
                        <?php if ($editModule): ?>
                            <input type="hidden" name="id" value="<?= $editModule['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome da Disciplina *</label>
                            <input type="text" class="form-control" id="nome" name="nome" 
                                   value="<?= $editModule ? htmlspecialchars($editModule['nome']) : '' ?>" 
                                   required maxlength="255" placeholder="Ex: Algoritmos e Estruturas de Dados">
                        </div>
                        
                        <div class="mb-3">
                            <label for="codigo" class="form-label">Código</label>
                            <input type="text" class="form-control" id="codigo" name="codigo" 
                                   value="<?= $editModule ? htmlspecialchars($editModule['codigo']) : '' ?>" 
                                   maxlength="50" placeholder="Ex: AED001">
                            <div class="form-text">Código da disciplina (opcional)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3" 
                                      placeholder="Descreva a disciplina..."><?= $editModule ? htmlspecialchars($editModule['descricao']) : '' ?></textarea>
                            <div class="form-text">Descrição opcional da disciplina</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="carga_horaria" class="form-label">Carga Horária (horas)</label>
                            <input type="number" class="form-control" id="carga_horaria" name="carga_horaria" 
                                   value="<?= $editModule ? $editModule['carga_horaria'] : '' ?>" 
                                   min="0" placeholder="Ex: 60">
                            <div class="form-text">Carga horária da disciplina em horas (opcional)</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                <?= $editModule ? 'Atualizar Disciplina' : 'Criar Disciplina' ?>
                            </button>
                            <?php if ($editModule): ?>
                                <a href="modules_simple.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
