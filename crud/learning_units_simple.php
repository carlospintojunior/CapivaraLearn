<?php
/**
 * CRUD Simplificado de Unidades de Aprendizagem - Versão Simples
 * Sistema CapivaraLearn
 */

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

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Buscar tópicos do usuário para os selects
$topicos = $database->select("topicos", [
    "[>]disciplinas" => ["disciplina_id" => "id"]
], [
    "topicos.id",
    "topicos.nome",
    "disciplinas.nome(disciplina_nome)"
], [
    "topicos.usuario_id" => $user_id,
    "ORDER" => ["disciplinas.nome", "topicos.nome"]
]);

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $nome = trim($_POST['nome'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $topico_id = intval($_POST['topico_id'] ?? 0);
            $nota = floatval($_POST['nota'] ?? 0.0);
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            
            if (empty($nome) || $topico_id <= 0) {
                $error = 'Nome e tópico são obrigatórios';
            } elseif ($nota < 0.0 || $nota > 10.0) {
                $error = 'A nota deve estar entre 0.0 e 10.0';
            } else {
                // Verificar se o tópico pertence ao usuário
                $topico_check = $database->get("topicos", "id", [
                    "id" => $topico_id,
                    "usuario_id" => $user_id
                ]);
                
                if (!$topico_check) {
                    $error = 'Tópico não encontrado ou não pertence ao usuário';
                } else {
                    $result = $database->insert("unidades_aprendizagem", [
                        "nome" => $nome,
                        "descricao" => $descricao,
                        "topico_id" => $topico_id,
                        "usuario_id" => $user_id,
                        "ordem" => 0,
                        "nota" => $nota,
                        "ativo" => $ativo
                    ]);
                    
                    if ($result->rowCount()) {
                        $message = 'Unidade de aprendizagem criada com sucesso!';
                    } else {
                        $error = 'Erro ao criar unidade de aprendizagem: ' . implode(', ', $database->error());
                    }
                }
            }
            break;
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                $error = 'ID inválido';
            } else {
                // Verificar se a unidade pertence ao usuário
                $unidade_check = $database->get("unidades_aprendizagem", "id", [
                    "id" => $id,
                    "usuario_id" => $user_id
                ]);
                
                if (!$unidade_check) {
                    $error = 'Unidade de aprendizagem não encontrada ou não pertence ao usuário';
                } else {
                    $result = $database->delete("unidades_aprendizagem", [
                        "id" => $id,
                        "usuario_id" => $user_id
                    ]);
                    
                    if ($result->rowCount()) {
                        $message = 'Unidade de aprendizagem excluída com sucesso!';
                    } else {
                        $error = 'Erro ao excluir unidade de aprendizagem: ' . implode(', ', $database->error());
                    }
                }
            }
            break;
    }
}

// Buscar unidades de aprendizagem do usuário
$unidades = $database->select("unidades_aprendizagem", [
    "[>]topicos" => ["topico_id" => "id"],
    "[>]disciplinas" => ["topicos.disciplina_id" => "id"]
], [
    "unidades_aprendizagem.id",
    "unidades_aprendizagem.nome",
    "unidades_aprendizagem.descricao",
    "topicos.nome(topico_nome)",
    "disciplinas.nome(disciplina_nome)",
    "unidades_aprendizagem.nota",
    "unidades_aprendizagem.ativo",
    "unidades_aprendizagem.data_criacao"
], [
    "unidades_aprendizagem.usuario_id" => $user_id,
    "ORDER" => ["disciplinas.nome", "topicos.nome", "unidades_aprendizagem.nome"]
]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unidades de Aprendizagem - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        .nota-badge { font-size: 0.9em; padding: 0.25em 0.6em; }
        .nota-excelente { background-color: #28a745; }
        .nota-bom { background-color: #17a2b8; }
        .nota-regular { background-color: #ffc107; color: #212529; }
        .nota-ruim { background-color: #dc3545; }
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="row">
        <div class="col-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-puzzle-piece"></i> Unidades de Aprendizagem</h2>
                <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Formulário -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-plus"></i> Nova Unidade</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="topico_id" class="form-label">Tópico *</label>
                            <select class="form-select" id="topico_id" name="topico_id" required>
                                <option value="">Selecione um tópico</option>
                                <?php foreach ($topicos as $topico): ?>
                                    <option value="<?php echo $topico['id']; ?>">
                                        <?php echo htmlspecialchars($topico['disciplina_nome'] . ' > ' . $topico['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nota" class="form-label">Nota (0.0 a 10.0) *</label>
                            <input type="number" class="form-control" id="nota" name="nota" 
                                   value="0.0" min="0.0" max="10.0" step="0.1" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="ativo" name="ativo" checked>
                            <label class="form-check-label" for="ativo">Ativo</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Criar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Unidades Cadastradas (<?php echo count($unidades); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($unidades)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle"></i>
                            Nenhuma unidade cadastrada. 
                            <?php if (empty($topicos)): ?>
                                <br><small>Crie primeiro alguns <a href="topics_simple.php">tópicos</a>.</small>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Disciplina/Tópico</th>
                                        <th>Nota</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unidades as $unidade): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($unidade['nome']); ?></strong>
                                                <?php if ($unidade['descricao']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($unidade['descricao'], 0, 50)); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($unidade['disciplina_nome']); ?></span>
                                                <br><small><?php echo htmlspecialchars($unidade['topico_nome']); ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $nota = floatval($unidade['nota']);
                                                $class = 'nota-ruim';
                                                if ($nota >= 9.0) $class = 'nota-excelente';
                                                elseif ($nota >= 7.0) $class = 'nota-bom';
                                                elseif ($nota >= 5.0) $class = 'nota-regular';
                                                ?>
                                                <span class="badge nota-badge <?php echo $class; ?>">
                                                    <?php echo number_format($nota, 1); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($unidade['ativo']): ?>
                                                    <span class="badge bg-success">Ativo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Inativo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Excluir unidade <?php echo htmlspecialchars($unidade['nome']); ?>?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $unidade['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
