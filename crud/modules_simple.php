<?php
// Configuração simplificada - Gerenciar Disciplinas
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/logger_config.php';
require_once __DIR__ . '/../Medoo.php';

use Medoo\Medoo;

// Iniciar sessão
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
    'host' => DB_HOST,
    'database' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'charset' => 'utf8mb4'
]);

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Função para converter status numérico em texto
function getStatusText($status) {
    $statusMap = [
        0 => 'Ativa',
        1 => 'Concluída', 
        2 => 'A Cursar',
        3 => 'Aproveitada',
        4 => 'Dispensada'
    ];
    return $statusMap[$status] ?? 'Desconhecido';
}

// Função para obter classe CSS do status
function getStatusClass($status) {
    $classMap = [
        0 => 'badge bg-primary',      // Ativa - azul
        1 => 'badge bg-success',      // Concluída - verde
        2 => 'badge bg-warning',      // A Cursar - amarelo
        3 => 'badge bg-info',         // Aproveitada - ciano
        4 => 'badge bg-secondary'     // Dispensada - cinza
    ];
    return $classMap[$status] ?? 'badge bg-dark';
}

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
                $status = intval($_POST['status'] ?? 0);
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
                    'status' => $status,
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
                $status = intval($_POST['status'] ?? 0);
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
                    'status' => $status
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

// ===== SISTEMA DE PERSISTÊNCIA DE FILTROS =====
// Verificar se há filtros sendo enviados pelo GET
if (isset($_GET['filtro_curso']) || isset($_GET['filtro_status'])) {
    // Salvar filtros na sessão
    $_SESSION['modules_filters'] = [
        'filtro_curso' => $_GET['filtro_curso'] ?? 'todos',
        'filtro_status' => $_GET['filtro_status'] ?? 'todos'
    ];
} elseif (isset($_GET['clear_filters'])) {
    // Limpar filtros se solicitado
    unset($_SESSION['modules_filters']);
} else {
    // Recuperar filtros salvos na sessão se não há GET
    if (isset($_SESSION['modules_filters'])) {
        $_GET = array_merge($_GET, $_SESSION['modules_filters']);
    }
}

// Buscar disciplinas para exibição com filtros
$filtro_curso = $_GET['filtro_curso'] ?? 'todos';
$filtro_status = $_GET['filtro_status'] ?? 'todos';

// Log dos filtros aplicados
logInfo("Filtros aplicados - Curso: $filtro_curso, Status: $filtro_status");

// Construir condições de filtro
$conditions = ['disciplinas.usuario_id' => $user_id];

// Aplicar filtro de curso
if ($filtro_curso !== 'todos') {
    $conditions['cursos.id'] = intval($filtro_curso);
    logInfo("Filtro de curso aplicado: " . intval($filtro_curso));
}

// Aplicar filtro de status
if ($filtro_status !== 'todos') {
    switch ($filtro_status) {
        case 'ativas':
            $conditions['disciplinas.status'] = 0;
            break;
        case 'concluidas':
            $conditions['disciplinas.status'] = 1;
            break;
        case 'a_cursar':
            $conditions['disciplinas.status'] = 2;
            break;
        case 'aproveitadas':
            $conditions['disciplinas.status'] = 3;
            break;
        case 'dispensadas':
            $conditions['disciplinas.status'] = 4;
            break;
    }
    logInfo("Filtro de status aplicado: $filtro_status");
}

// Adicionar ordenação
$conditions['ORDER'] = ['cursos.nome' => 'ASC', 'disciplinas.nome' => 'ASC'];

$disciplinas = $database->select('disciplinas', [
    '[>]cursos' => ['curso_id' => 'id']
], [
    'disciplinas.id',
    'disciplinas.nome',
    'disciplinas.codigo',
    'disciplinas.carga_horaria',
    'disciplinas.semestre',
    'disciplinas.status',
    'cursos.nome(curso_nome)'
], $conditions);

logInfo("Disciplinas encontradas: " . count($disciplinas));

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
                <div class="card-header">
                    <h5>Lista de Disciplinas (<?= count($disciplinas) ?>)</h5>
                </div>
                <div class="card-body">
                    <!-- Formulário de filtros -->
                    <form method="get" class="row g-2 mb-3 align-items-end">
                        <div class="col-md-3">
                            <label for="filtro_curso" class="form-label mb-0">Curso</label>
                            <select class="form-select" name="filtro_curso" id="filtro_curso">
                                <option value="todos" <?php if($filtro_curso === 'todos') echo 'selected'; ?>>Todos os Cursos</option>
                                <?php foreach($cursos as $curso): ?>
                                    <option value="<?= $curso['id'] ?>" <?php if($filtro_curso == $curso['id']) echo 'selected'; ?>>
                                        <?= htmlspecialchars($curso['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtro_status" class="form-label mb-0">Status</label>
                            <select class="form-select" name="filtro_status" id="filtro_status">
                                <option value="todos" <?php if($filtro_status === 'todos') echo 'selected'; ?>>Todos os Status</option>
                                <option value="ativas" <?php if($filtro_status === 'ativas') echo 'selected'; ?>>Ativas</option>
                                <option value="concluidas" <?php if($filtro_status === 'concluidas') echo 'selected'; ?>>Concluídas</option>
                                <option value="a_cursar" <?php if($filtro_status === 'a_cursar') echo 'selected'; ?>>A Cursar</option>
                                <option value="aproveitadas" <?php if($filtro_status === 'aproveitadas') echo 'selected'; ?>>Aproveitadas</option>
                                <option value="dispensadas" <?php if($filtro_status === 'dispensadas') echo 'selected'; ?>>Dispensadas</option>
                            </select>
                        </div>
                        <div class="col-md-3">
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
                    <?php if ($filtro_curso !== 'todos' || $filtro_status !== 'todos'): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Filtros ativos:</strong>
                            <?php if ($filtro_curso !== 'todos'): ?>
                                <?php 
                                $cursoNome = '';
                                foreach($cursos as $curso) {
                                    if($curso['id'] == $filtro_curso) {
                                        $cursoNome = $curso['nome'];
                                        break;
                                    }
                                }
                                ?>
                                <span class="badge bg-primary me-1">
                                    Curso: <?php echo htmlspecialchars($cursoNome); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($filtro_status !== 'todos'): ?>
                                <span class="badge bg-secondary me-1">
                                    Status: <?php 
                                        $statusNames = [
                                            'ativas' => 'Ativas',
                                            'concluidas' => 'Concluídas',
                                            'a_cursar' => 'A Cursar',
                                            'aproveitadas' => 'Aproveitadas',
                                            'dispensadas' => 'Dispensadas'
                                        ];
                                        echo $statusNames[$filtro_status] ?? $filtro_status; 
                                    ?>
                                </span>
                            <?php endif; ?>
                            <a href="?clear_filters=1" class="ms-2 text-decoration-none">
                                <small><i class="fas fa-times"></i> Limpar todos</small>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tabela de disciplinas -->
                    <div class="table-responsive">
                        <?php if (empty($disciplinas)): ?>
                            <p class="text-center text-muted">Nenhuma disciplina encontrada com os filtros aplicados.</p>
                        <?php else: ?>
                            <table class="table table-striped table-hover">
                                <thead><tr>
                                    <th>Nome</th><th>Código</th><th>Curso</th><th>C.H.</th><th style="display: none;">Sem.</th><th>Status</th><th>Ações</th>
                                </tr></thead>
                                <tbody>
                                <?php foreach ($disciplinas as $d): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d['nome']) ?></td>
                                        <td><?= htmlspecialchars($d['codigo']) ?></td>
                                        <td><?= htmlspecialchars($d['curso_nome']) ?></td>
                                        <td><?= $d['carga_horaria'] ?></td>
                                        <td style="display: none;"><?= $d['semestre'] ?></td>
                                        <td><span class="<?= getStatusClass($d['status']) ?>"><?= getStatusText($d['status']) ?></span></td>
                                        <td>
                                            <a href="?edit=<?= $d['id'] ?>" class="btn btn-sm btn-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta disciplina?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                                <button class="btn btn-sm btn-danger" title="Excluir"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
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
                        <!-- Campo semestre oculto -->
                        <div class="mb-3" style="display: none;"><label>Semestre</label><input type="number" name="semestre" class="form-control" value="<?= $editDisc['semestre'] ?? 0 ?>"></div>
                        <div class="mb-3"><label>Curso</label><select name="curso_id" class="form-select" required><option value="">Selecione</option><?php foreach($cursos as $c): ?><option value="<?= $c['id'] ?>" <?= isset($editDisc['curso_id']) && $editDisc['curso_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nome']) ?></option><?php endforeach; ?></select></div>
                        <div class="mb-3">
                            <label>Status</label>
                            <select name="status" class="form-select">
                                <option value="0" <?= isset($editDisc['status']) && $editDisc['status']==0?'selected':'' ?>>Ativa</option>
                                <option value="1" <?= isset($editDisc['status']) && $editDisc['status']==1?'selected':'' ?>>Concluída</option>
                                <option value="2" <?= isset($editDisc['status']) && $editDisc['status']==2?'selected':'' ?>>A Cursar</option>
                                <option value="3" <?= isset($editDisc['status']) && $editDisc['status']==3?'selected':'' ?>>Aproveitada</option>
                                <option value="4" <?= isset($editDisc['status']) && $editDisc['status']==4?'selected':'' ?>>Dispensada</option>
                            </select>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-<?= $editDisc ? 'primary' : 'success' ?>"><?= $editDisc ? 'Atualizar' : 'Criar' ?></button>
                            <?php if ($editDisc): ?>
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
