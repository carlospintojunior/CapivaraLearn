<?php
// Ativar exibição e registro detalhado de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
// Registrar erros em sistema.log
ini_set('error_log', __DIR__ . '/../logs/sistema.log');
// Manipuladores globais para capturar exceções não tratadas
set_exception_handler(function (Throwable $e) {
    error_log("TOPICOS UNCAUGHT EXCEPTION: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    exit('Erro interno do sistema. Verifique o log.');
});
set_error_handler(function ($severity, $message, $file, $line) {
    error_log("TOPICOS ERROR [$severity]: $message em $file:$line");
    return false; // Executa o handler padrão
});
// Error logging to sistema.log
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/sistema.log');
// CRUD Simplificado de Tópicos - CapivaraLearn
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

// Buscar disciplinas para o select
$disciplinas = $database->select('disciplinas', ['id', 'nome'], [
    'usuario_id' => $user_id,
    'ORDER' => ['nome' => 'ASC']
]);
// Map disciplines by id for display
$disciplinasMap = [];
foreach ($disciplinas as $d) {
    $disciplinasMap[$d['id']] = $d['nome'];
}

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'create':
                $nome = trim($_POST['nome'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $disciplina_id = intval($_POST['disciplina_id'] ?? 0);
                $ordem = intval($_POST['ordem'] ?? 0);
                $data_prazo = $_POST['data_prazo'] ?: null;
                $concluido = isset($_POST['concluido']) ? 1 : 0;

                if (empty($nome) || $disciplina_id <= 0) {
                    throw new Exception('Nome e disciplina são obrigatórios.');
                }
                // Verificar propriedade da disciplina
                $check = $database->get('disciplinas', 'id', [
                    'id' => $disciplina_id,
                    'usuario_id' => $user_id
                ]);
                if (!$check) {
                    throw new Exception('Disciplina inválida ou não pertence ao usuário.');
                }
                $database->insert('topicos', [
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'disciplina_id' => $disciplina_id,
                    'usuario_id' => $user_id,
                    'ordem' => $ordem,
                    'data_prazo' => $data_prazo,
                    'concluido' => $concluido
                ]);
                $message = 'Tópico criado com sucesso!';
                $messageType = 'success';
                break;

            case 'update':
                $id = intval($_POST['id'] ?? 0);
                $nome = trim($_POST['nome'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $disciplina_id = intval($_POST['disciplina_id'] ?? 0);
                $ordem = intval($_POST['ordem'] ?? 0);
                $data_prazo = $_POST['data_prazo'] ?: null;
                $concluido = isset($_POST['concluido']) ? 1 : 0;

                if ($id <= 0 || empty($nome) || $disciplina_id <= 0) {
                    throw new Exception('ID, nome e disciplina são obrigatórios.');
                }
                // Verificar propriedade
                $checkTopico = $database->get('topicos', 'id', [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                $checkDisc = $database->get('disciplinas', 'id', [
                    'id' => $disciplina_id,
                    'usuario_id' => $user_id
                ]);
                if (!$checkTopico) {
                    throw new Exception('Tópico inválido ou não pertence ao usuário.');
                } elseif (!$checkDisc) {
                    throw new Exception('Disciplina inválida ou não pertence ao usuário.');
                }
                $database->update('topicos', [
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'disciplina_id' => $disciplina_id,
                    'ordem' => $ordem,
                    'data_prazo' => $data_prazo,
                    'concluido' => $concluido
                ], [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                $message = 'Tópico atualizado com sucesso!';
                $messageType = 'success';
                break;

            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('ID inválido.');
                }
                $check = $database->get('topicos', 'id', [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                if (!$check) {
                    throw new Exception('Tópico inválido ou não pertence ao usuário.');
                }
                $database->delete('topicos', [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                $message = 'Tópico excluído com sucesso!';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Buscar tópicos para exibição
$topicos = $database->select('topicos', '*', [
    'usuario_id' => $user_id,
    'ORDER' => ['nome' => 'ASC']
]);

// Buscar tópico para edição
$editTopic = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $editTopic = $database->get('topicos', '*', [
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
    <title>Gerenciar Tópicos - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-list"></i> Gerenciar Tópicos</h2>
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
        <!-- Lista de Tópicos -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Tópicos <small class="text-muted"><?= count($topicos) ?> registros</small></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($topicos)): ?>
                        <p class="text-muted text-center">Nenhum tópico cadastrado.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Disciplina</th>
                                        <th>Ordem</th>
                                        <th>Prazo</th>
                                        <th>Status</th>
                                        <th width="120">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topicos as $t): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($t['nome']) ?></td>
                                        <td><?= htmlspecialchars($disciplinasMap[$t['disciplina_id']] ?? '-') ?></td>
                                            <td><?= $t['ordem'] ?></td>
                                            <td><?= $t['data_prazo'] ?: '-' ?></td>
                                            <td><?= $t['concluido'] ? 'Concluído' : 'Pendente' ?></td>
                                            <td>
                                                <a href="?edit=<?= $t['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
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
                <div class="card-header"><i class="fas fa-<?= $editTopic ? 'edit' : 'plus' ?>"></i> <?= $editTopic ? 'Editar' : 'Novo' ?> Tópico</div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="<?= $editTopic ? 'update' : 'create' ?>">
                        <?php if ($editTopic): ?><input type="hidden" name="id" value="<?= $editTopic['id'] ?>"><?php endif;?>
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($editTopic['nome'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Disciplina</label>
                            <select name="disciplina_id" class="form-select" required>
                                <option value="">Selecione</option>
                                <?php foreach ($disciplinas as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= isset($editTopic['disciplina_id']) && $editTopic['disciplina_id']==$d['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ordem</label>
                            <input type="number" name="ordem" class="form-control" value="<?= $editTopic['ordem'] ?? 0 ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prazo</label>
                            <input type="date" name="data_prazo" class="form-control" value="<?= $editTopic['data_prazo'] ?? '' ?>">
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="concluido" class="form-check-input" id="concluido" <?= (!empty($editTopic['concluido']) ? 'checked' : '') ?>>
                            <label class="form-check-label" for="concluido">Concluído</label>
                        </div>
                        <button type="submit" class="btn btn-<?= $editTopic ? 'primary' : 'success' ?> w-100">
                            <?= $editTopic ? 'Atualizar' : 'Criar' ?>
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
