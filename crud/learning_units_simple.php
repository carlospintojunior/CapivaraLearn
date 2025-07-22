<?php
/**
 * CRUD Simplificado de Unidades de Aprendizagem - CapivaraLearn
 * Sistema CapivaraLearn
 */

// Configuração simplificada
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

// Buscar tópicos com disciplinas para selects
$topicos = $database->select('topicos', [
    '[>]disciplinas' => ['disciplina_id' => 'id']
], [
    'topicos.id',
    'topicos.nome',
    'disciplinas.nome(disciplina_nome)'
], [
    'topicos.usuario_id' => $user_id,
    'ORDER' => ['disciplinas.nome' => 'ASC', 'topicos.nome' => 'ASC']
]);

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'create':
                $nome = trim($_POST['nome'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $tipo = $_POST['tipo'] ?? 'leitura';
                $nota = isset($_POST['nota']) ? floatval($_POST['nota']) : null;
                $data_prazo = $_POST['data_prazo'] ?: null;
                $concluido = isset($_POST['concluido']) ? 1 : 0;
                $topico_id = intval($_POST['topico_id'] ?? 0);
                if (empty($nome) || $topico_id <= 0) {
                    throw new Exception('Nome e tópico são obrigatórios.');
                }
                // Verificar propriedade do tópico
                $check = $database->get('topicos', 'id', [
                    'id' => $topico_id,
                    'usuario_id' => $user_id
                ]);
                if (!$check) {
                    throw new Exception('Tópico inválido ou não pertence ao usuário.');
                }
                $database->insert('unidades_aprendizagem', [
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'tipo' => $tipo,
                    'nota' => $nota,
                    'data_prazo' => $data_prazo,
                    'concluido' => $concluido,
                    'topico_id' => $topico_id,
                    'usuario_id' => $user_id
                ]);
                $message = 'Unidade criada com sucesso!';
                $messageType = 'success';
                break;
            case 'update':
                $id = intval($_POST['id'] ?? 0);
                $nome = trim($_POST['nome'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $tipo = $_POST['tipo'] ?? 'leitura';
                $nota = isset($_POST['nota']) ? floatval($_POST['nota']) : null;
                $data_prazo = $_POST['data_prazo'] ?: null;
                $concluido = isset($_POST['concluido']) ? 1 : 0;
                $topico_id = intval($_POST['topico_id'] ?? 0);
                if ($id <= 0 || empty($nome) || $topico_id <= 0) {
                    throw new Exception('ID, nome e tópico são obrigatórios.');
                }
                $checkUnit = $database->get('unidades_aprendizagem', 'id', [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                $checkTopico = $database->get('topicos', 'id', [
                    'id' => $topico_id,
                    'usuario_id' => $user_id
                ]);
                if (!$checkUnit) {
                    throw new Exception('Unidade inválida.');
                } elseif (!$checkTopico) {
                    throw new Exception('Tópico inválido.');
                }
                $database->update('unidades_aprendizagem', [
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'tipo' => $tipo,
                    'nota' => $nota,
                    'data_prazo' => $data_prazo,
                    'concluido' => $concluido,
                    'topico_id' => $topico_id
                ], [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                $message = 'Unidade atualizada com sucesso!';
                $messageType = 'success';
                break;
            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('ID inválido.');
                }
                $checkUnit = $database->get('unidades_aprendizagem', 'id', [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                if (!$checkUnit) {
                    throw new Exception('Unidade inválida ou não pertence ao usuário.');
                }
                $database->delete('unidades_aprendizagem', [
                    'id' => $id,
                    'usuario_id' => $user_id
                ]);
                $message = 'Unidade excluída com sucesso!';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Buscar unidades para exibição
$unidades = $database->select('unidades_aprendizagem', [
    '[>]topicos' => ['topico_id' => 'id'],
    '[>]disciplinas' => ['topicos.disciplina_id' => 'id']
], [
    'unidades_aprendizagem.id',
    'unidades_aprendizagem.nome',
    'unidades_aprendizagem.tipo',
    'unidades_aprendizagem.nota',
    'unidades_aprendizagem.data_prazo',
    'unidades_aprendizagem.concluido',
    'disciplinas.nome(disciplina_nome)',
    'topicos.nome(topico_nome)'
], [
    'unidades_aprendizagem.usuario_id' => $user_id,
    'ORDER' => ['disciplinas.nome' => 'ASC', 'topicos.nome' => 'ASC', 'unidades_aprendizagem.nome' => 'ASC']
]);

// Preparar edição
$editUnit = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $editUnit = $database->get('unidades_aprendizagem', '*', [
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
    <title>Gerenciar Unidades - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid mt-4">
    <!-- Cabeçalho -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-puzzle-piece"></i> Gerenciar Unidades</h2>
            <a href="../dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar ao Dashboard</a>
        </div>
    </div>
    <!-- Mensagens -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="row">
        <!-- Lista -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    Unidades Cadastradas (<?= count($unidades) ?>)
                </div>
                <div class="card-body">
                    <?php if (empty($unidades)): ?>
                        <p class="text-center text-muted">Nenhuma unidade cadastrada.</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead><tr>
                                <th>Nome</th><th>Disciplina/Tópico</th><th>Tipo</th><th>Nota</th><th>Status</th><th>Ações</th>
                            </tr></thead>
                            <tbody>
                                <?php foreach ($unidades as $u): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($u['nome']) ?></td>
                                        <td><?= htmlspecialchars($u['disciplina_nome'] . ' > ' . $u['topico_nome']) ?></td>
                                        <td><?= htmlspecialchars($u['tipo']) ?></td>
                                        <td><?= $u['nota'] ?? '-' ?></td>
                                        <td><?= $u['concluido'] ? 'Concluída' : 'Pendente' ?></td>
                                        <td>
                                            <a href="?edit=<?= $u['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                            <form method="post" class="d-inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Formulário -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><i class="fas fa-plus"></i> <?= $editUnit ? 'Editar' : 'Nova' ?> Unidade</div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="<?= $editUnit ? 'update' : 'create' ?>">
                        <?php if ($editUnit): ?><input type="hidden" name="id" value="<?= $editUnit['id'] ?>"><?php endif;?>
                        <div class="mb-3"><label>Nome *</label><input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($editUnit['nome'] ?? '') ?>" required></div>
                        <div class="mb-3"><label>Descrição</label><textarea name="descricao" class="form-control"><?= htmlspecialchars($editUnit['descricao'] ?? '') ?></textarea></div>
                        <div class="mb-3"><label>Tipo</label><select name="tipo" class="form-select"><option value="leitura" <?= (isset($editUnit['tipo']) && $editUnit['tipo']=='leitura')?'selected':'' ?>>Leitura</option><option value="exercicio" <?= (isset($editUnit['tipo']) && $editUnit['tipo']=='exercicio')?'selected':'' ?>>Exercício</option><option value="projeto" <?= (isset($editUnit['tipo']) && $editUnit['tipo']=='projeto')?'selected':'' ?>>Projeto</option><option value="prova" <?= (isset($editUnit['tipo']) && $editUnit['tipo']=='prova')?'selected':'' ?>>Prova</option><option value="outros" <?= (isset($editUnit['tipo']) && $editUnit['tipo']=='outros')?'selected':'' ?>>Outros</option></select></div>
                        <div class="mb-3"><label>Nota</label><input type="number" step="0.01" min="0" max="10" name="nota" class="form-control" value="<?= $editUnit['nota'] ?? '' ?>"></div>
                        <div class="mb-3"><label>Prazo</label><input type="date" name="data_prazo" class="form-control" value="<?= $editUnit['data_prazo'] ?? '' ?>"></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="concluido" class="form-check-input" id="concluido" <?= !empty($editUnit['concluido'])?'checked':'' ?>><label for="concluido" class="form-check-label">Concluído</label></div>
                        <div class="mb-3"><label>Tópico</label><select name="topico_id" class="form-select" required><option value="">Selecione</option><?php foreach($topicos as $t): ?><option value="<?= $t['id'] ?>" <?= (isset($editUnit['topico_id']) && $editUnit['topico_id']==$t['id'])?'selected':'' ?>><?= htmlspecialchars($t['disciplina_nome'].' > '.$t['nome']) ?></option><?php endforeach; ?></select></div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-<?= $editUnit?'primary':'success' ?>"><?= $editUnit?'Atualizar':'Criar' ?></button>
                            <?php if ($editUnit): ?>
                                <a href="?" class="btn btn-secondary">Cancelar</a>
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
