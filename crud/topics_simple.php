<?php
/**
 * CRUD Simplificado de Tópicos - CapivaraLearn
 * Sistema CapivaraLearn
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/logger_config.php';
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

// ===== SISTEMA DE PERSISTÊNCIA DE FILTROS =====
// Verificar se há filtros sendo enviados pelo GET
if (isset($_GET['filtro_disciplina']) || isset($_GET['filtro_topico']) || isset($_GET['disciplina_especifica'])) {
    // Salvar filtros na sessão
    $_SESSION['topics_filters'] = [
        'filtro_disciplina' => $_GET['filtro_disciplina'] ?? 'todos',
        'filtro_topico' => $_GET['filtro_topico'] ?? 'todos',
        'disciplina_especifica' => $_GET['disciplina_especifica'] ?? 'todas'
    ];
} elseif (isset($_GET['clear_filters'])) {
    // Limpar filtros se solicitado
    unset($_SESSION['topics_filters']);
    $filtros_ativos = [];
} else {
    // Recuperar filtros salvos na sessão se não há GET
    if (isset($_SESSION['topics_filters'])) {
        $_GET = array_merge($_GET, $_SESSION['topics_filters']);
    }
}

// Buscar tópicos para exibição com filtros
$where = [
    'topicos.usuario_id' => $user_id
];

// Aplicar filtros salvos ou recebidos
$filtro_disciplina = $_GET['filtro_disciplina'] ?? 'todos';
$filtro_topico = $_GET['filtro_topico'] ?? 'todos';
$disciplina_especifica = $_GET['disciplina_especifica'] ?? 'todas';

// PRIORIDADE: Disciplina específica sobrepõe filtro de status
if ($disciplina_especifica !== 'todas') {
    // Se uma disciplina específica for selecionada, use apenas ela
    $where['topicos.disciplina_id'] = intval($disciplina_especifica);
} else {
    // Aplicar filtro de status de disciplina apenas se não há disciplina específica
    if ($filtro_disciplina === 'ativas') {
        // Disciplinas concluídas, aproveitadas ou dispensadas (status 1, 3, 4)
        $where['disciplinas.status'] = [1, 3, 4];
    } elseif ($filtro_disciplina === 'pendentes') {
        // Disciplinas ativas ou a cursar (status 0, 2)
        $where['disciplinas.status'] = [0, 2];
    }
}

// Filtro de tópico
if ($filtro_topico === 'ativos') {
    $where['topicos.concluido'] = 1;
} elseif ($filtro_topico === 'pendentes') {
    $where['topicos.concluido'] = 0;
}

$where['ORDER'] = [
    'disciplinas.nome' => 'ASC',
    'topicos.nome' => 'ASC'
];

// LOG: filtros aplicados (Monolog e error_log)
if (file_exists(__DIR__ . '/../includes/log_sistema.php')) {
    require_once __DIR__ . '/../includes/log_sistema.php';
    if (function_exists('getLogger')) {
        $logger = getLogger();
        $logger->info('Filtros WHERE topics_simple', ['where' => $where, 'get' => $_GET]);
    }
}
error_log('TOPICS_SIMPLE: Filtros WHERE: ' . var_export($where, true) . ' | GET: ' . var_export($_GET, true));

$topicos = $database->select('topicos', [
    '[>]disciplinas' => ['disciplina_id' => 'id']
], [
    'topicos.id',
    'topicos.nome',
    'topicos.descricao',
    'topicos.ordem',
    'topicos.data_prazo',
    'topicos.concluido',
    'topicos.disciplina_id',
    'disciplinas.nome(disciplina_nome)'
], $where);

// LOG: resultado da consulta (Monolog e error_log)
if (isset($logger)) {
    $logger->info('Resultado SELECT topics_simple', ['count' => count($topicos), 'topicos' => $topicos]);
}
error_log('TOPICS_SIMPLE: Resultado SELECT: count=' . count($topicos) . ' | topicos=' . var_export($topicos, true));

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
                    Tópicos Cadastrados (<?= count($topicos) ?>)
                </div>
                <div class="card-body">
                    <!-- Formulário de filtros com dropdowns -->
                    <form method="get" class="row g-2 mb-3 align-items-end">
                        <div class="col-md-2">
                            <label for="filtro_disciplina" class="form-label mb-0">Status Disciplinas</label>
                            <select class="form-select" name="filtro_disciplina" id="filtro_disciplina">
                                <option value="todos" <?php if($filtro_disciplina === 'todos') echo 'selected'; ?>>Todas</option>
                                <option value="ativas" <?php if($filtro_disciplina === 'ativas') echo 'selected'; ?>>Concluídas</option>
                                <option value="pendentes" <?php if($filtro_disciplina === 'pendentes') echo 'selected'; ?>>Pendentes</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="disciplina_especifica" class="form-label mb-0">Disciplina Específica</label>
                            <select class="form-select" name="disciplina_especifica" id="disciplina_especifica">
                                <option value="todas">Todas as disciplinas</option>
                                <?php foreach ($disciplinas as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?php if($disciplina_especifica == $d['id']) echo 'selected'; ?>>
                                        <?= htmlspecialchars($d['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="filtro_topico" class="form-label mb-0">Status Tópicos</label>
                            <select class="form-select" name="filtro_topico" id="filtro_topico">
                                <option value="todos" <?php if($filtro_topico === 'todos') echo 'selected'; ?>>Todos</option>
                                <option value="ativos" <?php if($filtro_topico === 'ativos') echo 'selected'; ?>>Concluídos</option>
                                <option value="pendentes" <?php if($filtro_topico === 'pendentes') echo 'selected'; ?>>Pendentes</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="fas fa-filter me-1"></i>Filtrar
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="?clear_filters=1" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times me-1"></i>Limpar
                            </a>
                        </div>
                    </form>
                    
                    <!-- Indicador de filtros ativos -->
                    <?php if ($filtro_disciplina !== 'todos' || $filtro_topico !== 'todos' || $disciplina_especifica !== 'todas'): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Filtros ativos:</strong>
                            <?php if ($disciplina_especifica !== 'todas'): ?>
                                <span class="badge bg-primary me-1">
                                    <i class="fas fa-book me-1"></i>
                                    Disciplina: <?php echo htmlspecialchars($disciplinasMap[$disciplina_especifica] ?? 'Desconhecida'); ?>
                                </span>
                            <?php elseif ($filtro_disciplina !== 'todos'): ?>
                                <span class="badge bg-secondary me-1">
                                    Status Disciplinas: <?php 
                                        echo $filtro_disciplina === 'ativas' ? 'Concluídas' : 'Pendentes'; 
                                    ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($filtro_topico !== 'todos'): ?>
                                <span class="badge bg-success me-1">
                                    Status Tópicos: <?php 
                                        echo $filtro_topico === 'ativos' ? 'Concluídos' : 'Pendentes'; 
                                    ?>
                                </span>
                            <?php endif; ?>
                            <a href="?clear_filters=1" class="ms-2 text-decoration-none">
                                <small><i class="fas fa-times"></i> Limpar todos</small>
                            </a>
                        </div>
                    <?php endif; ?>
                    <!-- Tabela de tópicos cadastrados -->
                    <div class="table-responsive mb-3">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Disciplina</th>
                                    <th>Nome</th>
                                    <th>Ordem</th>
                                    <th>Prazo</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topicos)): ?>
                                    <tr><td colspan="6" class="text-center">Nenhum tópico encontrado.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($topicos as $t): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($t['disciplina_nome'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($t['nome']) ?></td>
                                            <td><?= $t['ordem'] ?></td>
                                            <td><?= $t['data_prazo'] ? date('d/m/Y', strtotime($t['data_prazo'])) : '-' ?></td>
                                            <td>
                                                <?php if (isset($t['concluido']) && $t['concluido']): ?>
                                                    <span class="badge bg-success">Concluído</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Pendente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?edit=<?= $t['id'] ?>" class="btn btn-sm btn-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                                <form method="post" action="" style="display:inline-block" onsubmit="return confirm('Tem certeza que deseja excluir este tópico?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
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

        <!-- Formulário de Criação/Edição -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <?= $editTopic ? 'Editar Tópico' : 'Novo Tópico' ?>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="<?= $editTopic ? 'update' : 'create' ?>">
                        <?php if ($editTopic): ?>
                            <input type="hidden" name="id" value="<?= $editTopic['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-2">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" name="nome" id="nome" value="<?= htmlspecialchars($editTopic['nome'] ?? '') ?>" required>
                        </div>
                        <div class="mb-2">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" name="descricao" id="descricao" rows="2"><?= htmlspecialchars($editTopic['descricao'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-2">
                            <label for="disciplina_id" class="form-label">Disciplina</label>
                            <select class="form-select" name="disciplina_id" id="disciplina_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($disciplinas as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= (isset($editTopic['disciplina_id']) && $editTopic['disciplina_id'] == $d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="ordem" class="form-label">Ordem</label>
                            <input type="number" class="form-control" name="ordem" id="ordem" value="<?= htmlspecialchars($editTopic['ordem'] ?? 0) ?>">
                        </div>
                        <div class="mb-2">
                            <label for="data_prazo" class="form-label">Prazo</label>
                            <input type="date" class="form-control" name="data_prazo" id="data_prazo" value="<?= isset($editTopic['data_prazo']) && $editTopic['data_prazo'] ? date('Y-m-d', strtotime($editTopic['data_prazo'])) : '' ?>">
                        </div>
                        <div class="mb-2 form-check">
                            <input type="checkbox" class="form-check-input" name="concluido" id="concluido" value="1" <?= (isset($editTopic['concluido']) && $editTopic['concluido']) ? 'checked' : '' ?>>
                            <label for="concluido" class="form-check-label">Concluído</label>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success">Salvar</button>
                            <?php if ($editTopic): ?>
                                <a href="topics_simple.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Controle de interação entre filtros
document.addEventListener('DOMContentLoaded', function() {
    const filtroStatus = document.getElementById('filtro_disciplina');
    const disciplinaEspecifica = document.getElementById('disciplina_especifica');
    
    function atualizarEstadoFiltros() {
        if (disciplinaEspecifica.value !== 'todas') {
            // Se uma disciplina específica foi selecionada, desabilitar filtro de status
            filtroStatus.disabled = true;
            filtroStatus.style.opacity = '0.6';
            filtroStatus.title = 'Desabilitado quando disciplina específica está selecionada';
        } else {
            // Se "Todas" está selecionado, habilitar filtro de status
            filtroStatus.disabled = false;
            filtroStatus.style.opacity = '1';
            filtroStatus.title = '';
        }
    }
    
    // Executar na inicialização
    atualizarEstadoFiltros();
    
    // Executar quando disciplina específica mudar
    disciplinaEspecifica.addEventListener('change', atualizarEstadoFiltros);
    
    // Quando filtro de status mudar, limpar disciplina específica se necessário
    filtroStatus.addEventListener('change', function() {
        if (this.value !== 'todos' && disciplinaEspecifica.value !== 'todas') {
            disciplinaEspecifica.value = 'todas';
            atualizarEstadoFiltros();
        }
    });
});
</script>
</body>
</html>
