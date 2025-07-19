<?php
// Configuração simplificada - Gerenciar Disciplinas
require_once __DIR__ . '/../Medoo.php';

use Medoo\Medoo;

// Iniciar sessão e autenticar usuário
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

// Carregar cursos para o select
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
                $nome = trim($_POST['nome'] ?? '');
                $codigo = trim($_POST['codigo'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $carga_horaria = intval($_POST['carga_horaria'] ?? 0);
                $semestre = intval($_POST['semestre'] ?? 0);
                $curso_id = intval($_POST['curso_id'] ?? 0);
                $concluido = intval($_POST['concluido'] ?? 0);
                if (empty($nome) || $curso_id <= 0) {
                    throw new Exception('Nome e curso são obrigatórios.');
                }
                // Verificar se curso pertence ao usuário
                $check = $database->get('cursos', 'id', [
                    'id' => $curso_id,
                    'usuario_id' => $user_id
                ]);
                if (!$check) {
                    throw new Exception('Curso inválido.');
                }
                $database->insert('disciplinas', [
                    'nome' => $nome,
                    'codigo' => $codigo,
                    'descricao' => $descricao,
                    'carga_horaria' => $carga_horaria,
                    'semestre' => $semestre,
                    'concluido' => $concluido,
                    'curso_id' => $curso_id,
                    'usuario_id' => $user_id
                ]);
                $message = 'Disciplina criada com sucesso!';
                $messageType = 'success';
                break;
            case 'update':
                $id = intval($_POST['id'] ?? 0);
                $nome = trim($_POST['nome'] ?? '');
                $codigo = trim($_POST['codigo'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $carga_horaria = intval($_POST['carga_horaria'] ?? 0);
                $semestre = intval($_POST['semestre'] ?? 0);
                $curso_id = intval($_POST['curso_id'] ?? 0);
                $concluido = intval($_POST['concluido'] ?? 0);
                if ($id <= 0 || empty($nome) || $curso_id <= 0) {
                    throw new Exception('ID, nome e curso são obrigatórios.');
                }
                $checkDisc = $database->get('disciplinas', 'id', [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                $checkCurso = $database->get('cursos', 'id', [
                    'id' => $curso_id,
                    'usuario_id' => $user_id
                ]);
                if (!$checkDisc) {
                    throw new Exception('Disciplina inválida.');
                } elseif (!$checkCurso) {
                    throw new Exception('Curso inválido.');
                }
                $database->update('disciplinas', [
                    'nome' => $nome,
                    'codigo' => $codigo,
                    'descricao' => $descricao,
                    'carga_horaria' => $carga_horaria,
                    'semestre' => $semestre,
                    'curso_id' => $curso_id,
                    'concluido' => $concluido
                ], [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                $message = 'Disciplina atualizada com sucesso!';
                $messageType = 'success';
                break;
            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('ID inválido.');
                }
                $check = $database->get('disciplinas', 'id', [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                if (!$check) {
                    throw new Exception('Disciplina inválida.');
                }
                $database->delete('disciplinas', [
                    'id' => $id,
                    'usuario_id' => $user_id
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

// Buscar disciplinas para exibição
$disciplinas = $database->select('disciplinas', [
    '[>]cursos' => ['curso_id' => 'id']
], [
    'disciplinas.id',
    'disciplinas.nome',
    'disciplinas.codigo',
    'disciplinas.carga_horaria',
    'disciplinas.semestre',
    'disciplinas.concluido',
    'cursos.nome(curso_nome)'
], [
    'disciplinas.usuario_id' => $user_id,
    'ORDER' => ['cursos.nome' => 'ASC', 'disciplinas.nome' => 'ASC']
]);

// Preparar edição
$editDisc = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $editDisc = $database->get('disciplinas', '*', [
        'id' => $eid,
        'usuario_id' => $user_id
    ]);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Disciplinas - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-book-open"></i> Gerenciar Disciplinas</h2>
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
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header"><h5>Lista de Disciplinas (<?= count($disciplinas) ?>)</h5></div>
                <div class="card-body">
                    <?php if (empty($disciplinas)): ?>
                        <p class="text-center text-muted">Nenhuma disciplina cadastrada.</p>
                    <?php else: ?>
                        <table class="table table-striped table-hover">
                            <thead><tr>
                                <th>Nome</th><th>Código</th><th>Curso</th><th>C.H.</th><th>Sem.</th><th>Status</th><th>Ações</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($disciplinas as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['nome']) ?></td>
                                    <td><?= htmlspecialchars($d['codigo']) ?></td>
                                    <td><?= htmlspecialchars($d['curso_nome']) ?></td>
                                    <td><?= $d['carga_horaria'] ?></td>
                                    <td><?= $d['semestre'] ?></td>
                                    <td><?= $d['concluido'] ? 'Concluído' : 'Ativa' ?></td>
                                    <td>
                                        <a href="?edit=<?= $d['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                        <form method="post" class="d-inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $d['id'] ?>"><button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h5><?= $editDisc ? 'Editar' : 'Nova' ?> Disciplina</h5></div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="<?= $editDisc ? 'update' : 'create' ?>">
                        <?php if ($editDisc): ?><input type="hidden" name="id" value="<?= $editDisc['id'] ?>"><?php endif;?>
                        <div class="mb-3"><label>Nome</label><input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($editDisc['nome'] ?? '') ?>" required></div>
                        <div class="mb-3"><label>Código</label><input type="text" name="codigo" class="form-control" value="<?= htmlspecialchars($editDisc['codigo'] ?? '') ?>"></div>
                        <div class="mb-3"><label>Descrição</label><textarea name="descricao" class="form-control"><?= htmlspecialchars($editDisc['descricao'] ?? '') ?></textarea></div>
                        <div class="mb-3"><label>Carga Horária</label><input type="number" name="carga_horaria" class="form-control" value="<?= $editDisc['carga_horaria'] ?? 0 ?>"></div>
                        <div class="mb-3"><label>Semestre</label><input type="number" name="semestre" class="form-control" value="<?= $editDisc['semestre'] ?? 0 ?>"></div>
                        <div class="mb-3"><label>Curso</label><select name="curso_id" class="form-select" required><option value="">Selecione</option><?php foreach($cursos as $c): ?><option value="<?= $c['id'] ?>" <?= isset($editDisc['curso_id']) && $editDisc['curso_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nome']) ?></option><?php endforeach; ?></select></div>
                        <div class="mb-3">
                            <label>Status</label>
                            <select name="concluido" class="form-select">
                                <option value="0" <?= isset($editDisc['concluido']) && $editDisc['concluido']==0?'selected':'' ?>>Ativa</option>
                                <option value="1" <?= isset($editDisc['concluido']) && $editDisc['concluido']==1?'selected':'' ?>>Concluída</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-<?= $editDisc ? 'primary' : 'success' ?> w-100"><?= $editDisc ? 'Atualizar' : 'Criar' ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
