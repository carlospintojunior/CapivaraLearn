<?php
// Configuração simplificada - CRUD de Cursos
require_once __DIR__ . '/../includes/config.php';
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
    'host' => DB_HOST,
    'database' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'charset' => 'utf8mb4'
]);

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Log do acesso ao CRUD de Cursos
logInfo('CRUD Cursos acessado', [
    'user_id' => $user_id,
    'user_name' => $_SESSION['user_name'] ?? 'unknown'
]);

// Carregar universidades para o select
$universities = $database->select('universidades', ['id', 'nome'], [
    'usuario_id' => $user_id,
    'ORDER' => ['nome' => 'ASC']
]);

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'create':
                $nome = trim($_POST['nome'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $carga_horaria = (int)($_POST['carga_horaria'] ?? 0);
                $universidade_id = (int)($_POST['universidade_id'] ?? 0);
                if (empty($nome)) {
                    throw new Exception('Nome do curso é obrigatório.');
                }
                if ($universidade_id <= 0) {
                    throw new Exception('Universidade é obrigatória.');
                }
                if ($carga_horaria < 0) {
                    throw new Exception('Carga horária deve ser um número positivo.');
                }

                $result = $database->insert('cursos', [
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'carga_horaria' => $carga_horaria,
                    'universidade_id' => $universidade_id,
                    'usuario_id' => $user_id
                ]);
                $message = 'Curso criado com sucesso!';
                $messageType = 'success';
                break;

            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $nome = trim($_POST['nome'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $carga_horaria = (int)($_POST['carga_horaria'] ?? 0);
                $universidade_id = (int)($_POST['universidade_id'] ?? 0);
                if (empty($nome)) {
                    throw new Exception('Nome do curso é obrigatório.');
                }
                if ($universidade_id <= 0) {
                    throw new Exception('Universidade é obrigatória.');
                }
                if ($carga_horaria < 0) {
                    throw new Exception('Carga horária deve ser um número positivo.');
                }

                $result = $database->update('cursos', [
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'carga_horaria' => $carga_horaria,
                    'universidade_id' => $universidade_id
                ], [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                $message = 'Curso atualizado com sucesso!';
                $messageType = 'success';
                break;

            case 'delete':
                $id = (int)($_POST['id'] ?? 0);

                $result = $database->delete('cursos', [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                $message = 'Curso excluído com sucesso!';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
        
        // Log detalhado do erro
        logError('CRUD Cursos erro: ' . $e->getMessage(), [
            'user_id' => $user_id,
            'action' => $action ?? 'unknown',
            'file' => __FILE__,
            'line' => __LINE__
        ]);
    }
}

// Buscar cursos com nome de universidade
$courses = $database->select('cursos', [
    '[>]universidades' => ['universidade_id' => 'id']
], [
    'cursos.id',
    'cursos.nome',
    'cursos.descricao',
    'cursos.carga_horaria',
    'universidades.nome(universidade_nome)'
], [
    'cursos.usuario_id' => $user_id,
    'ORDER' => ['cursos.nome' => 'ASC']
]);

// Buscar curso para edição
$editCourse = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editCourse = $database->get('cursos', '*', [
        'id' => $editId,
        'usuario_id' => $user_id
    ]);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cursos - CapivaraLearn</title>
    
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
                <h2><i class="fas fa-book"></i> Gerenciar Cursos</h2>
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
        <!-- Lista de Cursos -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Lista de Cursos
                        <small class="text-muted">(<?= count($courses) ?> registros)</small>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($courses)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhum curso cadastrado</h5>
                            <p class="text-muted">Use o formulário ao lado para adicionar seu primeiro curso.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Universidade</th>
                                        <th>Nome</th>
                                        <th>Descrição</th>
                                        <th>Carga Horária</th>
                                        <th width="120">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $course): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($course['universidade_nome']) ?></td>
                                            <td><strong><?= htmlspecialchars($course['nome']) ?></strong></td>
                                            <td>
                                                <?php if ($course['descricao']): ?>
                                                    <?= htmlspecialchars(strlen($course['descricao']) > 50 ? substr($course['descricao'], 0, 50) . '...' : $course['descricao']) ?>
                                                <?php else: ?>
                                                    <em class="text-muted">Sem descrição</em>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($course['carga_horaria']): ?>
                                                    <span class="badge bg-info"><?= $course['carga_horaria'] ?>h</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?edit=<?= $course['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este curso?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $course['id'] ?>">
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
                        <i class="fas fa-<?= $editCourse ? 'edit' : 'plus' ?>"></i>
                        <?= $editCourse ? 'Editar Curso' : 'Novo Curso' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?= $editCourse ? 'update' : 'create' ?>">
                        <?php if ($editCourse): ?>
                            <input type="hidden" name="id" value="<?= $editCourse['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do Curso *</label>
                            <input type="text" class="form-control" id="nome" name="nome" 
                                   value="<?= $editCourse ? htmlspecialchars($editCourse['nome']) : '' ?>" 
                                   required maxlength="255" placeholder="Ex: Ciência da Computação">
                        </div>
                        
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3" 
                                      placeholder="Descreva o curso..."><?= $editCourse ? htmlspecialchars($editCourse['descricao']) : '' ?></textarea>
                            <div class="form-text">Descrição opcional do curso</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="universidade_id" class="form-label">Universidade *</label>
                            <select id="universidade_id" name="universidade_id" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach (
                                    $universities as $uni
                                ): ?>
                                    <option value="<?= $uni['id'] ?>" <?= $editCourse && $editCourse['universidade_id']==$uni['id']? 'selected':'' ?>>
                                        <?= htmlspecialchars($uni['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="carga_horaria" class="form-label">Carga Horária (horas)</label>
                            <input type="number" class="form-control" id="carga_horaria" name="carga_horaria" 
                                   value="<?= $editCourse ? $editCourse['carga_horaria'] : '' ?>" 
                                   min="0" placeholder="Ex: 2400">
                            <div class="form-text">Carga horária total do curso em horas (opcional)</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                <?= $editCourse ? 'Atualizar Curso' : 'Criar Curso' ?>
                            </button>
                            <?php if ($editCourse): ?>
                                <a href="courses_simple.php" class="btn btn-secondary">
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
