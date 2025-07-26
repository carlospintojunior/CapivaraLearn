<?php
/**
 * CRUD Simplificado de Unidades de Aprendizagem - CapivaraLearn
 * Sistema CapivaraLearn
 */


// Configuração de log igual ao dashboard
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não mostrar erros na tela
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/sistema.log');

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

// Buscar unidades para exibição com filtros


$where = [
    'unidades_aprendizagem.usuario_id' => $user_id
];
// Filtro de disciplina
if (isset($_GET['filtro_disciplina'])) {
    if ($_GET['filtro_disciplina'] === 'ativas') {
        $where['disciplinas.status'] = 1;
    } elseif ($_GET['filtro_disciplina'] === 'pendentes') {
        $where['disciplinas.status'] = 0;
    }
}
// Filtro de tópico
if (isset($_GET['filtro_topico'])) {
    if ($_GET['filtro_topico'] === 'ativos') {
        $where['topicos.ativo'] = 1;
    } elseif ($_GET['filtro_topico'] === 'pendentes') {
        $where['topicos.ativo'] = 0;
    }
}
// Filtro de unidade
if (isset($_GET['filtro_unidade'])) {
    if ($_GET['filtro_unidade'] === 'ativas') {
        $where['unidades_aprendizagem.concluido'] = 1;
    } elseif ($_GET['filtro_unidade'] === 'pendentes') {
        $where['unidades_aprendizagem.concluido'] = 0;
    }
}

$where['ORDER'] = [
    'disciplinas.nome' => 'ASC',
    'topicos.nome' => 'ASC',
    'unidades_aprendizagem.nome' => 'ASC'
];


// LOG: filtros aplicados (Monolog e error_log)
if (file_exists(__DIR__ . '/../includes/log_sistema.php')) {
    require_once __DIR__ . '/../includes/log_sistema.php';
    if (function_exists('getLogger')) {
        $logger = getLogger();
        $logger->info('Filtros WHERE learning_units_simple', ['where' => $where, 'get' => $_GET]);
    }
}
error_log('LEARNING_UNITS_SIMPLE: Filtros WHERE: ' . var_export($where, true) . ' | GET: ' . var_export($_GET, true));


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
], $where);

// LOG: resultado da consulta (Monolog e error_log)
if (isset($logger)) {
    $logger->info('Resultado SELECT learning_units_simple', ['count' => count($unidades), 'unidades' => $unidades]);
}
error_log('LEARNING_UNITS_SIMPLE: Resultado SELECT: count=' . count($unidades) . ' | unidades=' . var_export($unidades, true));

// LOG: resultado da consulta
if (isset($logger)) {
    $logger->info('Resultado SELECT learning_units_simple', ['count' => count($unidades), 'unidades' => $unidades]);
}

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
                    <!-- Formulário de filtros com dropdowns -->
                    <form method="get" class="row g-2 mb-3 align-items-end">
                        <div class="col-md-3">
                            <label for="filtro_disciplina" class="form-label mb-0">Disciplinas</label>
                            <select class="form-select" name="filtro_disciplina" id="filtro_disciplina">
                                <option value="todos" <?php if(!isset($_GET['filtro_disciplina']) || $_GET['filtro_disciplina']==='todos') echo 'selected'; ?>>Todas</option>
                                <option value="ativas" <?php if(isset($_GET['filtro_disciplina']) && $_GET['filtro_disciplina']==='ativas') echo 'selected'; ?>>Somente ativas</option>
                                <option value="pendentes" <?php if(isset($_GET['filtro_disciplina']) && $_GET['filtro_disciplina']==='pendentes') echo 'selected'; ?>>Somente pendentes</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtro_topico" class="form-label mb-0">Tópicos</label>
                            <select class="form-select" name="filtro_topico" id="filtro_topico">
                                <option value="todos" <?php if(!isset($_GET['filtro_topico']) || $_GET['filtro_topico']==='todos') echo 'selected'; ?>>Todos</option>
                                <option value="ativos" <?php if(isset($_GET['filtro_topico']) && $_GET['filtro_topico']==='ativos') echo 'selected'; ?>>Somente ativos</option>
                                <option value="pendentes" <?php if(isset($_GET['filtro_topico']) && $_GET['filtro_topico']==='pendentes') echo 'selected'; ?>>Somente pendentes</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtro_unidade" class="form-label mb-0">Unidades</label>
                            <select class="form-select" name="filtro_unidade" id="filtro_unidade">
                                <option value="todos" <?php if(!isset($_GET['filtro_unidade']) || $_GET['filtro_unidade']==='todos') echo 'selected'; ?>>Todas</option>
                                <option value="ativas" <?php if(isset($_GET['filtro_unidade']) && $_GET['filtro_unidade']==='ativas') echo 'selected'; ?>>Somente ativas</option>
                                <option value="pendentes" <?php if(isset($_GET['filtro_unidade']) && $_GET['filtro_unidade']==='pendentes') echo 'selected'; ?>>Somente pendentes</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary w-100">Filtrar</button>
                        </div>
                    </form>
                    <!-- Tabela de unidades cadastradas -->
                    <div class="table-responsive mb-3">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Disciplina</th>
                                    <th>Tópico</th>
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Nota</th>
                                    <th>Prazo</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($unidades)): ?>
                                    <tr><td colspan="8" class="text-center">Nenhuma unidade encontrada.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($unidades as $u): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($u['disciplina_nome'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($u['topico_nome'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($u['nome']) ?></td>
                                            <td><?= htmlspecialchars($u['tipo']) ?></td>
                                            <td><?= is_null($u['nota']) ? '-' : number_format($u['nota'], 2, ',', '.') ?></td>
                                            <td><?= $u['data_prazo'] ? date('d/m/Y', strtotime($u['data_prazo'])) : '-' ?></td>
                                            <td>
                                                <?php if (isset($u['concluido']) && $u['concluido']): ?>
                                                    <span class="badge bg-success">Ativa</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Pendente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?edit=<?= $u['id'] ?>" class="btn btn-sm btn-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                                <form method="post" action="" style="display:inline-block" onsubmit="return confirm('Tem certeza que deseja excluir esta unidade?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Excluir"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Formulário de cadastro/edição -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <?= $editUnit ? 'Editar Unidade' : 'Nova Unidade' ?>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="<?= $editUnit ? 'update' : 'create' ?>">
                        <?php if ($editUnit): ?>
                            <input type="hidden" name="id" value="<?= $editUnit['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-2">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" name="nome" id="nome" value="<?= htmlspecialchars($editUnit['nome'] ?? '') ?>" required>
                        </div>
                        <div class="mb-2">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" name="descricao" id="descricao" rows="2"><?= htmlspecialchars($editUnit['descricao'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-2">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" name="tipo" id="tipo">
                                <option value="leitura" <?= (isset($editUnit['tipo']) && $editUnit['tipo'] === 'leitura') ? 'selected' : '' ?>>Leitura</option>
                                <option value="atividade" <?= (isset($editUnit['tipo']) && $editUnit['tipo'] === 'atividade') ? 'selected' : '' ?>>Atividade</option>
                                <option value="prova" <?= (isset($editUnit['tipo']) && $editUnit['tipo'] === 'prova') ? 'selected' : '' ?>>Prova</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="nota" class="form-label">Nota</label>
                            <input type="number" step="0.01" class="form-control" name="nota" id="nota" value="<?= htmlspecialchars($editUnit['nota'] ?? '') ?>">
                        </div>
                        <div class="mb-2">
                            <label for="data_prazo" class="form-label">Prazo</label>
                            <input type="date" class="form-control" name="data_prazo" id="data_prazo" value="<?= isset($editUnit['data_prazo']) && $editUnit['data_prazo'] ? date('Y-m-d', strtotime($editUnit['data_prazo'])) : '' ?>">
                        </div>
                        <div class="mb-2 form-check">
                            <input type="checkbox" class="form-check-input" name="concluido" id="concluido" value="1" <?= (isset($editUnit['concluido']) && $editUnit['concluido']) ? 'checked' : '' ?>>
                            <label for="concluido" class="form-check-label">Concluído/Ativo</label>
                        </div>
                        <div class="mb-2">
                            <label for="topico_id" class="form-label">Tópico</label>
                            <select class="form-select" name="topico_id" id="topico_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($topicos as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= (isset($editUnit['topico_id']) && $editUnit['topico_id'] == $t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['disciplina_nome'] . ' - ' . $t['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success">Salvar</button>
                            <?php if ($editUnit): ?>
                                <a href="learning_units_simple.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
