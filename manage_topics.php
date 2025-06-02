<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/services/TopicService.php';
require_once __DIR__ . '/includes/services/ModuleService.php';

// Verificar se usuário está logado
requireLogin();

$topicService = TopicService::getInstance();
$moduleService = ModuleService::getInstance();

// Processar ações
require_once __DIR__ . '/includes/services/FileService.php';

$fileService = FileService::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_topic':
                $topicId = $topicService->create([
                    'titulo' => $_POST['titulo'],
                    'descricao' => $_POST['descricao'],
                    'modulo_id' => $_POST['modulo_id'],
                    'ordem' => $_POST['ordem'] ?? 0
                ]);

                // Processar uploads de arquivos
                if (!empty($_FILES['attachments']['name'][0])) {
                    foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
                        if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['attachments']['name'][$key],
                                'type' => $_FILES['attachments']['type'][$key],
                                'tmp_name' => $tmpName,
                                'error' => $_FILES['attachments']['error'][$key],
                                'size' => $_FILES['attachments']['size'][$key]
                            ];
                            
                            $uploadResult = $fileService->processUpload($file, 'topicos/' . $topicId);
                            $topicService->attachFile($topicId, $uploadResult['id']);
                        }
                    }
                }
                
                header('Location: manage_topics.php?success=1');
                exit;
                
            case 'edit_topic':
                $topicService->update($_POST['id'], [
                    'titulo' => $_POST['titulo'],
                    'descricao' => $_POST['descricao'],
                    'modulo_id' => $_POST['modulo_id'],
                    'ordem' => $_POST['ordem'] ?? 0
                ]);
                header('Location: manage_topics.php?success=2');
                exit;
                
            case 'delete_topic':
                $topicService->delete($_POST['id']);
                header('Location: manage_topics.php?success=3');
                exit;
                
            case 'attach_files':
                $topicId = $_POST['topic_id'];
                
                if (!empty($_FILES['attachments']['name'][0])) {
                    foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
                        if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['attachments']['name'][$key],
                                'type' => $_FILES['attachments']['type'][$key],
                                'tmp_name' => $tmpName,
                                'error' => $_FILES['attachments']['error'][$key],
                                'size' => $_FILES['attachments']['size'][$key]
                            ];
                            
                            $uploadResult = $fileService->processUpload($file, 'topicos/' . $topicId);
                            $topicService->attachFile($topicId, $uploadResult['id']);
                        }
                    }
                }
                
                header('Location: manage_topics.php?id=' . $topicId . '&success=4');
                exit;
                
            case 'remove_file':
                $topicId = $_POST['topic_id'];
                $fileId = $_POST['file_id'];
                
                $topicService->detachFile($topicId, $fileId);
                $fileService->deleteFile($fileId);
                
                header('Location: manage_topics.php?id=' . $topicId . '&success=5');
                exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Buscar lista de tópicos e módulos
$topics = $topicService->listAll();
$modules = $moduleService->listAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="mb-4">Gerenciar Tópicos</h1>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        switch ($_GET['success']) {
            case 1:
                echo 'Tópico adicionado com sucesso!';
                break;
            case 2:
                echo 'Tópico atualizado com sucesso!';
                break;
            case 3:
                echo 'Tópico excluído com sucesso!';
                break;
            case 4:
                echo 'Arquivos anexados com sucesso!';
                break;
            case 5:
                echo 'Arquivo removido com sucesso!';
                break;
        }
        ?>
    </div>
<?php endif; ?>

<div class="mb-3">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTopicModal">
        Adicionar Tópico
    </button>
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Módulo</th>
                <th>Ordem</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topics as $topic): ?>
                <tr>
                    <td><?php echo htmlspecialchars($topic['id']); ?></td>
                    <td><?php echo htmlspecialchars($topic['titulo']); ?></td>
                    <td>
                        <?php
                        $module = array_filter($modules, function($m) use ($topic) {
                            return $m['id'] == $topic['modulo_id'];
                        });
                        $module = reset($module);
                        echo htmlspecialchars($module['nome'] ?? 'N/A');
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($topic['ordem']); ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editTopicModal"
                                data-id="<?php echo $topic['id']; ?>"
                                data-titulo="<?php echo htmlspecialchars($topic['titulo']); ?>"
                                data-descricao="<?php echo htmlspecialchars($topic['descricao']); ?>"
                                data-modulo-id="<?php echo $topic['modulo_id']; ?>"
                                data-ordem="<?php echo $topic['ordem']; ?>">
                            Editar
                        </button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este tópico?');">
                            <input type="hidden" name="action" value="delete_topic">
                            <input type="hidden" name="id" value="<?php echo $topic['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Adicionar Tópico -->
<div class="modal fade" id="addTopicModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Tópico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_topic">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="attachments" class="form-label">Anexos</label>
                        <input type="file" class="form-control" id="attachments" name="attachments[]" multiple
                               accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.zip,.rar">
                        <div class="form-text">
                            Tipos permitidos: PDF, DOC, DOCX, PPT, PPTX, JPG, PNG, ZIP, RAR. Tamanho máximo: 50MB por arquivo.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="modulo_id" class="form-label">Módulo</label>
                        <select class="form-select" id="modulo_id" name="modulo_id" required>
                            <option value="">Selecione um módulo</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?php echo $module['id']; ?>">
                                    <?php echo htmlspecialchars($module['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="ordem" class="form-label">Ordem</label>
                        <input type="number" class="form-control" id="ordem" name="ordem" value="0" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="attachments" class="form-label">Anexos</label>
                        <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Tópico -->
<div class="modal fade" id="editTopicModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Tópico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_topic">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_titulo" class="form-label">Título</label>
                        <input type="text" class="form-control" id="edit_titulo" name="titulo" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="edit_descricao" name="descricao" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_modulo_id" class="form-label">Módulo</label>
                        <select class="form-select" id="edit_modulo_id" name="modulo_id" required>
                            <option value="">Selecione um módulo</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?php echo $module['id']; ?>">
                                    <?php echo htmlspecialchars($module['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_ordem" class="form-label">Ordem</label>
                        <input type="number" class="form-control" id="edit_ordem" name="ordem" value="0" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Lista de Arquivos do Tópico -->
<?php if (isset($_GET['id'])): 
    $topicId = $_GET['id'];
    $topic = $topicService->getById($topicId);
    $files = $topicService->listTopicFiles($topicId);
?>
    <div class="card mb-4">
        <div class="card-header">
            <h4>Arquivos do Tópico: <?php echo htmlspecialchars($topic['titulo']); ?></h4>
        </div>
        <div class="card-body">
            <!-- Formulário de Upload -->
            <form method="POST" enctype="multipart/form-data" class="mb-4">
                <input type="hidden" name="action" value="attach_files">
                <input type="hidden" name="topic_id" value="<?php echo $topicId; ?>">
                <div class="row">
                    <div class="col-md-8">
                        <input type="file" class="form-control" name="attachments[]" multiple
                               accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.zip,.rar" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Anexar Arquivos</button>
                    </div>
                </div>
            </form>

            <!-- Lista de Arquivos -->
            <?php if (!empty($files)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Tamanho</th>
                                <th>Data Upload</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($file['caminho']); ?>" target="_blank">
                                            <?php echo htmlspecialchars($file['nome_original']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($file['tipo']); ?></td>
                                    <td><?php echo number_format($file['tamanho'] / 1024 / 1024, 2); ?> MB</td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($file['data_upload'])); ?></td>
                                    <td>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover este arquivo?');">
                                            <input type="hidden" name="action" value="remove_file">
                                            <input type="hidden" name="topic_id" value="<?php echo $topicId; ?>">
                                            <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">Nenhum arquivo anexado a este tópico.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
document.getElementById('editTopicModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    const titulo = button.getAttribute('data-titulo');
    const descricao = button.getAttribute('data-descricao');
    const moduloId = button.getAttribute('data-modulo-id');
    const ordem = button.getAttribute('data-ordem');
    
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_titulo').value = titulo;
    document.getElementById('edit_descricao').value = descricao;
    document.getElementById('edit_modulo_id').value = moduloId;
    document.getElementById('edit_ordem').value = ordem;
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
