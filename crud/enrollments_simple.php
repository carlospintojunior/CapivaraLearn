<?php
// CRUD Simplificado de Matrículas - CapivaraLearn
require_once __DIR__ . '/../Medoo.php';

use Medoo\Medoo;

// Iniciar sessão e validar autenticação
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
$messageType = '';

// Buscar cursos para o select
$cursos = $database->select('cursos', ['id', 'nome'], [
    'usuario_id' => $user_id,
    'ORDER' => ['nome' => 'ASC']
]);

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'create':
                $curso_id = intval($_POST['curso_id'] ?? 0);
                $status = $_POST['status'] ?? 'ativa';
                if ($curso_id <= 0) {
                    throw new Exception('Curso é obrigatório.');
                }
                // Verificar se curso pertence ao usuário
                $checkCurso = $database->get('cursos', 'id', [
                    'id' => $curso_id,
                    'usuario_id' => $user_id
                ]);
                if (!$checkCurso) {
                    throw new Exception('Curso inválido ou não pertence ao usuário.');
                }
                $data_conclusao = ($status === 'concluida') ? date('Y-m-d H:i:s') : null;
                $database->insert('matriculas', [
                    'usuario_id' => $user_id,
                    'curso_id' => $curso_id,
                    'status' => $status,
                    'data_conclusao' => $data_conclusao
                ]);
                $message = 'Matrícula criada com sucesso!';
                $messageType = 'success';
                break;
            case 'update':
                $id = intval($_POST['id'] ?? 0);
                $curso_id = intval($_POST['curso_id'] ?? 0);
                $status = $_POST['status'] ?? 'ativa';
                if ($id <= 0 || $curso_id <= 0) {
                    throw new Exception('ID e curso são obrigatórios.');
                }
                $checkMat = $database->get('matriculas', 'id', [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                $checkCurso = $database->get('cursos', 'id', [
                    'id' => $curso_id,
                    'usuario_id' => $user_id
                ]);
                if (!$checkMat) {
                    throw new Exception('Matrícula não encontrada ou não pertence ao usuário.');
                } elseif (!$checkCurso) {
                    throw new Exception('Curso inválido ou não pertence ao usuário.');
                }
                $data_conclusao = ($status === 'concluida') ? date('Y-m-d H:i:s') : null;
                $database->update('matriculas', [
                    'curso_id' => $curso_id,
                    'status' => $status,
                    'data_conclusao' => $data_conclusao
                ], [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                $message = 'Matrícula atualizada com sucesso!';
                $messageType = 'success';
                break;
            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('ID inválido.');
                }
                $checkMat = $database->get('matriculas', 'id', [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                if (!$checkMat) {
                    throw new Exception('Matrícula não encontrada ou não pertence ao usuário.');
                }
                $database->delete('matriculas', [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                $message = 'Matrícula excluída com sucesso!';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Buscar matrículas para exibição
$matriculas = $database->select('matriculas', [
    '[>]cursos' => ['curso_id' => 'id']
], [
    'matriculas.id',
    'cursos.nome(curso_nome)',
    'matriculas.status',
    'matriculas.data_matricula',
    'matriculas.data_conclusao'
], [
    'matriculas.usuario_id' => $user_id,
    'ORDER' => ['cursos.nome' => 'ASC']
]);

// Preparar edição
$editMat = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $editMat = $database->get('matriculas', '*', [
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
    <title>Gerenciar Matrículas - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-users"></i> Gerenciar Matrículas</h2>
            <a href="../dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar ao Dashboard</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Lista de Matrículas -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Matrículas Cadastradas <small class="text-muted"><?= count($matriculas) ?> registros</small></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($matriculas)): ?>
                        <p class="text-center text-muted">Nenhuma matrícula registrada.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Curso</th>
                                        <th>Status</th>
                                        <th>Data Matrícula</th>
                                        <th>Data Conclusão</th>
                                        <th width="120">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($matriculas as $m): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($m['curso_nome']) ?></td>
                                            <td><?= htmlspecialchars($m['status']) ?></td>
                                            <td><?= $m['data_matricula'] ?></td>
                                            <td><?= $m['data_conclusao'] ?? '-' ?></td>
                                            <td>
                                                <a href="?edit=<?= $m['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
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
                <div class="card-header"><i class="fas fa-<?= $editMat ? 'edit' : 'plus' ?>"></i> <?= $editMat ? 'Editar' : 'Nova' ?> Matrícula</div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="<?= $editMat ? 'update' : 'create' ?>">
                        <?php if ($editMat): ?><input type="hidden" name="id" value="<?= $editMat['id'] ?>"><?php endif;?>
                        <div class="mb-3">
                            <label class="form-label">Curso *</label>
                            <select name="curso_id" class="form-select" required>
                                <option value="">Selecione</option>
                                <?php foreach ($cursos as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= isset($editMat['curso_id']) && $editMat['curso_id']==$c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="ativa" <?= isset($editMat['status']) && $editMat['status']=='ativa' ? 'selected' : '' ?>>Ativa</option>
                                <option value="concluida" <?= isset($editMat['status']) && $editMat['status']=='concluida' ? 'selected' : '' ?>>Concluída</option>
                                <option value="cancelada" <?= isset($editMat['status']) && $editMat['status']=='cancelada' ? 'selected' : '' ?>>Cancelada</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-<?= $editMat ? 'primary' : 'success' ?> w-100">
                            <?= $editMat ? 'Atualizar' : 'Criar' ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
