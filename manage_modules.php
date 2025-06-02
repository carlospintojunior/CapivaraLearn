<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/services/ModuleService.php';
require_once __DIR__ . '/includes/services/TopicService.php';

// Verificar se usuário está logado
requireLogin();

$moduleService = ModuleService::getInstance();
$topicService = TopicService::getInstance();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_module':
                $moduleId = $moduleService->create($_SESSION['user_id'], [
                    'nome' => $_POST['nome'],
                    'codigo' => $_POST['codigo'],
                    'descricao' => $_POST['descricao'],
                    'data_inicio' => $_POST['data_inicio'],
                    'data_fim' => $_POST['data_fim'],
                    'cor' => $_POST['cor'] ?? '#3498db'
                ]);
                header('Location: manage_modules.php?success=1');
                exit;
                
            case 'edit_module':
                $moduleService->update($_POST['id'], $_SESSION['user_id'], [
                    'nome' => $_POST['nome'],
                    'codigo' => $_POST['codigo'],
                    'descricao' => $_POST['descricao'],
                    'data_inicio' => $_POST['data_inicio'],
                    'data_fim' => $_POST['data_fim'],
                    'cor' => $_POST['cor'] ?? '#3498db'
                ]);
                header('Location: manage_modules.php?success=2');
                exit;
                
            case 'delete_module':
                $moduleService->delete($_POST['id'], $_SESSION['user_id']);
                header('Location: manage_modules.php?success=3');
                exit;
                
            case 'add_topic':
                $topicService->create($_POST['module_id'], $_SESSION['user_id'], [
                    'nome' => $_POST['nome'],
                    'descricao' => $_POST['descricao'],
                    'data_inicio' => $_POST['data_inicio'],
                    'data_fim' => $_POST['data_fim'],
                    'ordem' => $_POST['ordem'] ?? null
                ]);
                header('Location: manage_modules.php?module=' . $_POST['module_id'] . '&success=4');
                exit;
                
            case 'edit_topic':
                $topicService->update($_POST['id'], $_SESSION['user_id'], [
                    'nome' => $_POST['nome'],
                    'descricao' => $_POST['descricao'],
                    'data_inicio' => $_POST['data_inicio'],
                    'data_fim' => $_POST['data_fim'],
                    'ordem' => $_POST['ordem'] ?? null
                ]);
                header('Location: manage_modules.php?module=' . $_POST['module_id'] . '&success=5');
                exit;
                
            case 'delete_topic':
                $topicService->delete($_POST['id'], $_SESSION['user_id']);
                header('Location: manage_modules.php?module=' . $_POST['module_id'] . '&success=6');
                exit;
                
            case 'toggle_topic':
                $topicService->toggleComplete($_POST['id'], $_SESSION['user_id']);
                header('Location: manage_modules.php?module=' . $_POST['module_id'] . '&success=7');
                exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Buscar módulos do usuário
$modules = $moduleService->listByUser($_SESSION['user_id']);

// Se um módulo específico foi selecionado, buscar seus tópicos
$currentModule = null;
$topics = [];
if (isset($_GET['module'])) {
    $currentModule = $moduleService->getById($_GET['module'], $_SESSION['user_id']);
    if ($currentModule) {
        $topics = $topicService->listByModule($currentModule['id'], $_SESSION['user_id']);
    }
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Módulos - CapivaraLearn</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f5f6fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f1f1;
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .module-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .module-card h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        .module-info {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        .success-message {
            background: #d5f5e3;
            color: #27ae60;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error-message {
            background: #f8d7da;
            color: #dc3545;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 100%;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }
        .topic-list {
            margin-top: 20px;
        }
        .topic-item {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .topic-item.completed {
            border-left-color: #27ae60;
            background: #f0fff4;
        }
        .topic-item.overdue {
            border-left-color: #e74c3c;
            background: #fff5f5;
        }
        .topic-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .topic-title {
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        .topic-dates {
            color: #7f8c8d;
            font-size: 0.9em;
            margin: 5px 0;
        }
        .topic-description {
            color: #34495e;
            font-size: 0.95em;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gerenciar Módulos</h1>
            <button class="btn" onclick="openAddModuleModal()">Novo Módulo</button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <?php
                switch ($_GET['success']) {
                    case '1':
                        echo 'Módulo adicionado com sucesso!';
                        break;
                    case '2':
                        echo 'Módulo atualizado com sucesso!';
                        break;
                    case '3':
                        echo 'Módulo removido com sucesso!';
                        break;
                    case '4':
                        echo 'Tópico adicionado com sucesso!';
                        break;
                    case '5':
                        echo 'Tópico atualizado com sucesso!';
                        break;
                    case '6':
                        echo 'Tópico removido com sucesso!';
                        break;
                    case '7':
                        echo 'Status do tópico atualizado com sucesso!';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($currentModule): ?>
            <div style="margin-bottom: 20px;">
                <a href="manage_modules.php" class="btn">← Voltar para Módulos</a>
            </div>
            <div class="header" style="margin-top: 20px;">
                <div>
                    <h2><?= h($currentModule['nome']) ?></h2>
                    <?php if ($currentModule['codigo']): ?>
                        <p style="color: #7f8c8d; margin: 5px 0;"><?= h($currentModule['codigo']) ?></p>
                    <?php endif; ?>
                </div>
                <button class="btn" onclick="openAddTopicModal(<?= $currentModule['id'] ?>)">Novo Tópico</button>
            </div>

            <div class="topic-list">
                <?php if (empty($topics)): ?>
                    <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                        <p>Nenhum tópico cadastrado neste módulo.</p>
                        <p>Clique em "Novo Tópico" para começar!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($topics as $topic): ?>
                        <div class="topic-item <?= $topic['concluido'] ? 'completed' : ($topic['data_fim'] < date('Y-m-d') ? 'overdue' : '') ?>">
                            <div class="topic-header">
                                <div>
                                    <h3 class="topic-title"><?= h($topic['nome']) ?></h3>
                                    <div class="topic-dates">
                                        📅 <?= formatDate($topic['data_inicio']) ?> - <?= formatDate($topic['data_fim']) ?>
                                    </div>
                                </div>
                                <div class="actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_topic">
                                        <input type="hidden" name="id" value="<?= $topic['id'] ?>">
                                        <input type="hidden" name="module_id" value="<?= $currentModule['id'] ?>">
                                        <button type="submit" class="btn" style="background: <?= $topic['concluido'] ? '#95a5a6' : '#27ae60' ?>">
                                            <?= $topic['concluido'] ? '✓ Concluído' : 'Marcar como Concluído' ?>
                                        </button>
                                    </form>
                                    <button class="btn" onclick="openEditTopicModal(<?= $topic['id'] ?>, <?= $currentModule['id'] ?>, '<?= h(addslashes($topic['nome'])) ?>', '<?= h(addslashes($topic['descricao'])) ?>', '<?= $topic['data_inicio'] ?>', '<?= $topic['data_fim'] ?>', <?= $topic['ordem'] ?>)">
                                        Editar
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este tópico?');">
                                        <input type="hidden" name="action" value="delete_topic">
                                        <input type="hidden" name="id" value="<?= $topic['id'] ?>">
                                        <input type="hidden" name="module_id" value="<?= $currentModule['id'] ?>">
                                        <button type="submit" class="btn btn-danger">Excluir</button>
                                    </form>
                                </div>
                            </div>
                            <?php if ($topic['descricao']): ?>
                                <div class="topic-description"><?= nl2br(h($topic['descricao'])) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="module-grid">
                <?php if (empty($modules)): ?>
                    <div style="text-align: center; padding: 40px; color: #7f8c8d; grid-column: 1/-1;">
                        <p>Nenhum módulo cadastrado.</p>
                        <p>Clique em "Novo Módulo" para começar!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($modules as $module): ?>
                        <div class="module-card" style="border-left: 4px solid <?= h($module['cor']) ?>">
                            <h3>
                                <a href="?module=<?= $module['id'] ?>" style="color: inherit; text-decoration: none;">
                                    <?= h($module['nome']) ?>
                                </a>
                            </h3>
                            <?php if ($module['codigo']): ?>
                                <p style="color: #7f8c8d; margin: 5px 0;"><?= h($module['codigo']) ?></p>
                            <?php endif; ?>
                            <div class="module-info">
                                <p>📅 <?= formatDate($module['data_inicio']) ?> - <?= formatDate($module['data_fim']) ?></p>
                                <p>
                                    <span title="Total de tópicos">📋 <?= $module['total_topicos'] ?></span> |
                                    <span title="Tópicos concluídos" style="color: #27ae60;">✓ <?= $module['topicos_concluidos'] ?></span>
                                    <?php if ($module['topicos_atrasados'] > 0): ?>
                                        | <span title="Tópicos atrasados" style="color: #e74c3c;">⚠️ <?= $module['topicos_atrasados'] ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="actions">
                                <a href="?module=<?= $module['id'] ?>" class="btn">Ver Tópicos</a>
                                <button class="btn" onclick="openEditModuleModal(<?= $module['id'] ?>, '<?= h(addslashes($module['nome'])) ?>', '<?= h(addslashes($module['codigo'])) ?>', '<?= h(addslashes($module['descricao'])) ?>', '<?= $module['data_inicio'] ?>', '<?= $module['data_fim'] ?>', '<?= $module['cor'] ?>')">
                                    Editar
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este módulo?');">
                                    <input type="hidden" name="action" value="delete_module">
                                    <input type="hidden" name="id" value="<?= $module['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Excluir</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Adicionar Módulo -->
    <div id="addModuleModal" class="modal">
        <div class="modal-content">
            <h2>Novo Módulo</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_module">
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="nome" required>
                </div>
                <div class="form-group">
                    <label>Código (opcional)</label>
                    <input type="text" name="codigo" placeholder="Ex: MOD101">
                </div>
                <div class="form-group">
                    <label>Descrição (opcional)</label>
                    <textarea name="descricao"></textarea>
                </div>
                <div class="form-group">
                    <label>Data de Início</label>
                    <input type="date" name="data_inicio" required>
                </div>
                <div class="form-group">
                    <label>Data de Término</label>
                    <input type="date" name="data_fim" required>
                </div>
                <div class="form-group">
                    <label>Cor</label>
                    <input type="color" name="cor" value="#3498db">
                </div>
                <div class="actions">
                    <button type="submit" class="btn">Salvar</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('addModuleModal')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Módulo -->
    <div id="editModuleModal" class="modal">
        <div class="modal-content">
            <h2>Editar Módulo</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit_module">
                <input type="hidden" name="id" id="edit_module_id">
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="nome" id="edit_module_nome" required>
                </div>
                <div class="form-group">
                    <label>Código (opcional)</label>
                    <input type="text" name="codigo" id="edit_module_codigo" placeholder="Ex: MOD101">
                </div>
                <div class="form-group">
                    <label>Descrição (opcional)</label>
                    <textarea name="descricao" id="edit_module_descricao"></textarea>
                </div>
                <div class="form-group">
                    <label>Data de Início</label>
                    <input type="date" name="data_inicio" id="edit_module_data_inicio" required>
                </div>
                <div class="form-group">
                    <label>Data de Término</label>
                    <input type="date" name="data_fim" id="edit_module_data_fim" required>
                </div>
                <div class="form-group">
                    <label>Cor</label>
                    <input type="color" name="cor" id="edit_module_cor">
                </div>
                <div class="actions">
                    <button type="submit" class="btn">Atualizar</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('editModuleModal')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Adicionar Tópico -->
    <div id="addTopicModal" class="modal">
        <div class="modal-content">
            <h2>Novo Tópico</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_topic">
                <input type="hidden" name="module_id" id="add_topic_module_id">
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="nome" required>
                </div>
                <div class="form-group">
                    <label>Descrição (opcional)</label>
                    <textarea name="descricao"></textarea>
                </div>
                <div class="form-group">
                    <label>Data de Início</label>
                    <input type="date" name="data_inicio" required>
                </div>
                <div class="form-group">
                    <label>Data de Término</label>
                    <input type="date" name="data_fim" required>
                </div>
                <div class="form-group">
                    <label>Ordem (opcional)</label>
                    <input type="number" name="ordem" min="1">
                </div>
                <div class="actions">
                    <button type="submit" class="btn">Salvar</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('addTopicModal')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Tópico -->
    <div id="editTopicModal" class="modal">
        <div class="modal-content">
            <h2>Editar Tópico</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit_topic">
                <input type="hidden" name="id" id="edit_topic_id">
                <input type="hidden" name="module_id" id="edit_topic_module_id">
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="nome" id="edit_topic_nome" required>
                </div>
                <div class="form-group">
                    <label>Descrição (opcional)</label>
                    <textarea name="descricao" id="edit_topic_descricao"></textarea>
                </div>
                <div class="form-group">
                    <label>Data de Início</label>
                    <input type="date" name="data_inicio" id="edit_topic_data_inicio" required>
                </div>
                <div class="form-group">
                    <label>Data de Término</label>
                    <input type="date" name="data_fim" id="edit_topic_data_fim" required>
                </div>
                <div class="form-group">
                    <label>Ordem (opcional)</label>
                    <input type="number" name="ordem" id="edit_topic_ordem" min="1">
                </div>
                <div class="actions">
                    <button type="submit" class="btn">Atualizar</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('editTopicModal')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModuleModal() {
            document.getElementById('addModuleModal').style.display = 'flex';
        }

        function openEditModuleModal(id, nome, codigo, descricao, dataInicio, dataFim, cor) {
            document.getElementById('edit_module_id').value = id;
            document.getElementById('edit_module_nome').value = nome;
            document.getElementById('edit_module_codigo').value = codigo;
            document.getElementById('edit_module_descricao').value = descricao;
            document.getElementById('edit_module_data_inicio').value = dataInicio;
            document.getElementById('edit_module_data_fim').value = dataFim;
            document.getElementById('edit_module_cor').value = cor;
            document.getElementById('editModuleModal').style.display = 'flex';
        }

        function openAddTopicModal(moduleId) {
            document.getElementById('add_topic_module_id').value = moduleId;
            document.getElementById('addTopicModal').style.display = 'flex';
        }

        function openEditTopicModal(id, moduleId, nome, descricao, dataInicio, dataFim, ordem) {
            document.getElementById('edit_topic_id').value = id;
            document.getElementById('edit_topic_module_id').value = moduleId;
            document.getElementById('edit_topic_nome').value = nome;
            document.getElementById('edit_topic_descricao').value = descricao;
            document.getElementById('edit_topic_data_inicio').value = dataInicio;
            document.getElementById('edit_topic_data_fim').value = dataFim;
            document.getElementById('edit_topic_ordem').value = ordem || '';
            document.getElementById('editTopicModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>