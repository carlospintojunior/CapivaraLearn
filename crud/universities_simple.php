<?php
// Configuração simplificada
require_once __DIR__ . '/../Medoo.php';
require_once __DIR__ . '/../includes/logger_config.php';

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

// Log do acesso ao CRUD
logInfo('CRUD Universidades acessado', [
    'user_id' => $_SESSION['user_id'],
    'user_name' => $_SESSION['user_name'] ?? 'unknown'
]);

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
                $sigla = trim($_POST['sigla'] ?? '');
                $cidade = trim($_POST['cidade'] ?? '');
                $estado = strtoupper(trim($_POST['estado'] ?? ''));
                
                if (empty($nome) || empty($sigla) || empty($cidade) || empty($estado)) {
                    throw new Exception('Todos os campos são obrigatórios.');
                }
                
                if (strlen($estado) !== 2) {
                    throw new Exception('Estado deve ter exatamente 2 caracteres.');
                }
                
                $result = $database->insert('universidades', [
                    'nome' => $nome,
                    'sigla' => $sigla,
                    'cidade' => $cidade,
                    'estado' => $estado,
                    'usuario_id' => $_SESSION['user_id']
                ]);
                
                // Log da criação
                logInfo('Universidade criada', [
                    'user_id' => $_SESSION['user_id'],
                    'nome' => $nome,
                    'sigla' => $sigla,
                    'cidade' => $cidade,
                    'estado' => $estado
                ]);
                
                logActivity($_SESSION['user_id'], 'university_create', "Universidade criada: {$nome} ({$sigla})", $database->pdo ?? null);
                
                $message = 'Universidade criada com sucesso!';
                $messageType = 'success';
                break;
                
            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $nome = trim($_POST['nome'] ?? '');
                $sigla = trim($_POST['sigla'] ?? '');
                $cidade = trim($_POST['cidade'] ?? '');
                $estado = strtoupper(trim($_POST['estado'] ?? ''));
                
                if (empty($nome) || empty($sigla) || empty($cidade) || empty($estado)) {
                    throw new Exception('Todos os campos são obrigatórios.');
                }
                
                if (strlen($estado) !== 2) {
                    throw new Exception('Estado deve ter exatamente 2 caracteres.');
                }
                
                $result = $database->update('universidades', [
                    'nome' => $nome,
                    'sigla' => $sigla,
                    'cidade' => $cidade,
                    'estado' => $estado
                ], [
                    'id' => $id,
                    'usuario_id' => $_SESSION['user_id']
                ]);
                
                // Log da atualização
                logInfo('Universidade atualizada', [
                    'user_id' => $_SESSION['user_id'],
                    'id' => $id,
                    'nome' => $nome,
                    'sigla' => $sigla,
                    'cidade' => $cidade,
                    'estado' => $estado
                ]);
                
                logActivity($_SESSION['user_id'], 'university_update', "Universidade atualizada: {$nome} ({$sigla})", $database->pdo ?? null);
                
                $message = 'Universidade atualizada com sucesso!';
                $messageType = 'success';
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                
                // Buscar dados antes de excluir para log
                $universityData = $database->get('universidades', '*', ['id' => $id, 'usuario_id' => $_SESSION['user_id']]);
                
                $result = $database->delete('universidades', [
                    'id' => $id,
                    'usuario_id' => $_SESSION['user_id']
                ]);
                
                // Log da exclusão
                if ($universityData) {
                    logInfo('Universidade excluída', [
                        'user_id' => $_SESSION['user_id'],
                        'id' => $id,
                        'nome' => $universityData['nome'],
                        'sigla' => $universityData['sigla']
                    ]);
                    
                    logActivity($_SESSION['user_id'], 'university_delete', "Universidade excluída: {$universityData['nome']} ({$universityData['sigla']})", $database->pdo ?? null);
                }
                
                $message = 'Universidade excluída com sucesso!';
                $messageType = 'success';
                break;
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Buscar universidades
$universities = $database->select('universidades', '*', [
    'usuario_id' => $_SESSION['user_id'],
    'ORDER' => ['nome' => 'ASC']
]);

// Buscar universidade para edição
$editUniversity = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editUniversity = $database->get('universidades', '*', [
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
    <title>Universidades - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>CapivaraLearn
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                <a class="nav-link" href="../logout.php">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-university me-2"></i>Universidades
                            <small class="text-muted">(<?= count($universities) ?> registros)</small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($universities)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-university fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhuma universidade cadastrada</h5>
                                <p class="text-muted">Use o formulário ao lado para adicionar sua primeira universidade.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Sigla</th>
                                            <th>Cidade</th>
                                            <th>Estado</th>
                                            <th width="120">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($universities as $university): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($university['nome']) ?></strong></td>
                                                <td><span class="badge bg-primary"><?= htmlspecialchars($university['sigla']) ?></span></td>
                                                <td><i class="fas fa-map-marker-alt text-muted me-1"></i><?= htmlspecialchars($university['cidade']) ?></td>
                                                <td><span class="badge bg-secondary"><?= htmlspecialchars($university['estado']) ?></span></td>
                                                <td>
                                                    <a href="?edit=<?= $university['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta universidade?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $university['id'] ?>">
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
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-<?= $editUniversity ? 'edit' : 'plus' ?> me-2"></i>
                            <?= $editUniversity ? 'Editar Universidade' : 'Nova Universidade' ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="<?= $editUniversity ? 'update' : 'create' ?>">
                            <?php if ($editUniversity): ?>
                                <input type="hidden" name="id" value="<?= $editUniversity['id'] ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome da Universidade</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?= $editUniversity ? htmlspecialchars($editUniversity['nome']) : '' ?>" 
                                       required maxlength="255" placeholder="Ex: Universidade de São Paulo">
                            </div>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="sigla" class="form-label">Sigla</label>
                                        <input type="text" class="form-control" id="sigla" name="sigla" 
                                               value="<?= $editUniversity ? htmlspecialchars($editUniversity['sigla']) : '' ?>" 
                                               required maxlength="10" placeholder="USP">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="estado" class="form-label">Estado</label>
                                        <input type="text" class="form-control" id="estado" name="estado" 
                                               value="<?= $editUniversity ? htmlspecialchars($editUniversity['estado']) : '' ?>" 
                                               required maxlength="2" placeholder="SP" style="text-transform: uppercase;">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade" 
                                       value="<?= $editUniversity ? htmlspecialchars($editUniversity['cidade']) : '' ?>" 
                                       required maxlength="100" placeholder="São Paulo">
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    <?= $editUniversity ? 'Atualizar' : 'Criar' ?>
                                </button>
                                <?php if ($editUniversity): ?>
                                    <a href="universities_simple.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancelar
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
