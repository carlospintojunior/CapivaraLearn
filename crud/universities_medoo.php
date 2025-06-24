<?php
// Configuração com Medoo
require_once __DIR__ . '/includes/medoo_config.php';
require_once __DIR__ . '/includes/crud_functions.php';

// Verificar login
requireLogin();

// Configurações da página
$pageTitle = 'Gerenciar Universidades - CapivaraLearn';
$message = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar CSRF
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token de segurança inválido.');
        }

        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                // Validar dados
                validate_required($_POST['nome'], 'Nome');
                validate_required($_POST['sigla'], 'Sigla');
                validate_required($_POST['cidade'], 'Cidade');
                validate_required($_POST['estado'], 'Estado');
                
                // Sanitizar dados
                $nome = sanitize_input($_POST['nome']);
                $sigla = sanitize_input($_POST['sigla']);
                $cidade = sanitize_input($_POST['cidade']);
                $estado = strtoupper(sanitize_input($_POST['estado']));
                
                // Validar estado (2 caracteres)
                if (strlen($estado) !== 2) {
                    throw new Exception('Estado deve ter exatamente 2 caracteres.');
                }
                
                // Inserir no banco usando Medoo
                $result = $database->insert('universidades', [
                    'nome' => $nome,
                    'sigla' => $sigla,
                    'cidade' => $cidade,
                    'estado' => $estado,
                    'usuario_id' => $_SESSION['user_id'],
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                if ($result->rowCount() > 0) {
                    log_activity('universidade_criada', "Universidade: $nome");
                    redirect_with_message('universities_medoo.php', 'success', 'Universidade criada com sucesso!');
                } else {
                    throw new Exception('Erro ao criar universidade.');
                }
                break;
                
            case 'update':
                // Validar dados
                validate_required($_POST['id'], 'ID');
                validate_required($_POST['nome'], 'Nome');
                validate_required($_POST['sigla'], 'Sigla');
                validate_required($_POST['cidade'], 'Cidade');
                validate_required($_POST['estado'], 'Estado');
                
                // Sanitizar dados
                $id = (int)$_POST['id'];
                $nome = sanitize_input($_POST['nome']);
                $sigla = sanitize_input($_POST['sigla']);
                $cidade = sanitize_input($_POST['cidade']);
                $estado = strtoupper(sanitize_input($_POST['estado']));
                
                // Validar estado (2 caracteres)
                if (strlen($estado) !== 2) {
                    throw new Exception('Estado deve ter exatamente 2 caracteres.');
                }
                
                // Atualizar no banco usando Medoo (apenas se pertencer ao usuário)
                $result = $database->update('universidades', [
                    'nome' => $nome,
                    'sigla' => $sigla,
                    'cidade' => $cidade,
                    'estado' => $estado,
                    'updated_at' => date('Y-m-d H:i:s')
                ], [
                    'id' => $id,
                    'usuario_id' => $_SESSION['user_id']
                ]);
                
                if ($result->rowCount() > 0) {
                    log_activity('universidade_atualizada', "ID: $id, Nome: $nome");
                    redirect_with_message('universities_medoo.php', 'success', 'Universidade atualizada com sucesso!');
                } else {
                    throw new Exception('Universidade não encontrada ou sem permissão para editar.');
                }
                break;
                
            case 'delete':
                // Validar dados
                validate_required($_POST['id'], 'ID');
                $id = (int)$_POST['id'];
                
                // Buscar nome para log
                $university = $database->get('universidades', 'nome', [
                    'id' => $id,
                    'usuario_id' => $_SESSION['user_id']
                ]);
                
                // Deletar do banco usando Medoo (apenas se pertencer ao usuário)
                $result = $database->delete('universidades', [
                    'id' => $id,
                    'usuario_id' => $_SESSION['user_id']
                ]);
                
                if ($result->rowCount() > 0) {
                    log_activity('universidade_excluida', "ID: $id, Nome: $university");
                    redirect_with_message('universities_medoo.php', 'success', 'Universidade excluída com sucesso!');
                } else {
                    throw new Exception('Universidade não encontrada ou sem permissão para excluir.');
                }
                break;
                
            default:
                throw new Exception('Ação inválida.');
        }
        
    } catch (Exception $e) {
        $message = show_error_message($e->getMessage());
    }
}

// Buscar universidades do usuário usando Medoo
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

// Incluir header
include __DIR__ . '/includes/crud_header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-university me-2"></i>Universidades
                    <small class="text-muted">(<?= count($universities) ?> registros)</small>
                </h5>
            </div>
            <div class="card-body">
                <?= show_flash_message() ?>
                <?= $message ?>
                
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
                                        <td>
                                            <strong><?= htmlspecialchars($university['nome']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?= htmlspecialchars($university['sigla']) ?></span>
                                        </td>
                                        <td>
                                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                            <?= htmlspecialchars($university['cidade']) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($university['estado']) ?></span>
                                        </td>
                                        <td>
                                            <a href="universities_medoo.php?edit=<?= $university['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" class="d-inline" 
                                                  onsubmit="return confirmDelete('<?= htmlspecialchars($university['nome']) ?>')">
                                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
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
                <h5 class="card-title mb-0">
                    <i class="fas fa-<?= $editUniversity ? 'edit' : 'plus' ?> me-2"></i>
                    <?= $editUniversity ? 'Editar Universidade' : 'Nova Universidade' ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
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
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sigla" class="form-label">Sigla</label>
                                <input type="text" class="form-control" id="sigla" name="sigla" 
                                       value="<?= $editUniversity ? htmlspecialchars($editUniversity['sigla']) : '' ?>" 
                                       required maxlength="10" placeholder="USP">
                            </div>
                        </div>
                        <div class="col-md-6">
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
                            <a href="universities_medoo.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/crud_footer.php'; ?>
