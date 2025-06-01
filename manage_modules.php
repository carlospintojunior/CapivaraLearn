<?php
session_start();
require_once "includes/config.php";
require_once "includes/ModuleService.php";

// Verificar se o usuário está logado e tem permissão
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

$moduleService = ModuleService::getInstance();
$message = '';
$error = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $icon = !empty($_POST['icon']) ? trim($_POST['icon']) : null;
                $color = !empty($_POST['color']) ? trim($_POST['color']) : null;
                $orderIndex = !empty($_POST['order_index']) ? intval($_POST['order_index']) : 0;
                
                if ($moduleService->createModule($title, $description, $icon, $color, $orderIndex)) {
                    $message = "Módulo criado com sucesso!";
                } else {
                    $error = "Erro ao criar módulo: " . $moduleService->getLastError();
                }
                break;
                
            case 'update':
                $id = intval($_POST['id']);
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $icon = !empty($_POST['icon']) ? trim($_POST['icon']) : null;
                $color = !empty($_POST['color']) ? trim($_POST['color']) : null;
                $orderIndex = !empty($_POST['order_index']) ? intval($_POST['order_index']) : null;
                
                if ($moduleService->updateModule($id, $title, $description, $icon, $color, $orderIndex)) {
                    $message = "Módulo atualizado com sucesso!";
                } else {
                    $error = "Erro ao atualizar módulo: " . $moduleService->getLastError();
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                if ($moduleService->deleteModule($id)) {
                    $message = "Módulo excluído com sucesso!";
                } else {
                    $error = "Erro ao excluir módulo: " . $moduleService->getLastError();
                }
                break;
        }
    }
}

// Buscar todos os módulos
$modules = $moduleService->getAllModules();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Módulos - CapivaraLearn</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="/src/css/style.css">
    <style>
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .module-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .module-card .actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 10px;
        }
        
        .module-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .module-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            margin-left: 10px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gerenciar Módulos</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Formulário de Criação/Edição -->
        <div class="card">
            <h2 id="formTitle">Criar Novo Módulo</h2>
            <form id="moduleForm" method="POST" class="form">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="id" value="">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Título</label>
                        <input type="text" id="title" name="title" required class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="icon">Ícone (Material Icons)</label>
                        <div class="input-group">
                            <input type="text" id="icon" name="icon" class="form-control" placeholder="Exemplo: school">
                            <span class="input-group-text material-icons" id="iconPreview">school</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="color">Cor</label>
                        <div class="input-group">
                            <input type="text" id="color" name="color" class="form-control" placeholder="#RRGGBB">
                            <div class="color-preview" id="colorPreview"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="order_index">Ordem</label>
                        <input type="number" id="order_index" name="order_index" class="form-control" value="0">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="description">Descrição</label>
                        <textarea id="description" name="description" required class="form-control" rows="4"></textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Salvar Módulo</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">Limpar</button>
                </div>
            </form>
        </div>
        
        <!-- Lista de Módulos -->
        <div class="module-grid">
            <?php foreach ($modules as $module): ?>
                <div class="module-card">
                    <div class="actions">
                        <button onclick="editModule(<?php echo htmlspecialchars(json_encode($module)); ?>)" class="btn btn-sm btn-secondary">
                            <span class="material-icons">edit</span>
                        </button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este módulo?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($module['id']); ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <span class="material-icons">delete</span>
                            </button>
                        </form>
                    </div>
                    
                    <div class="module-header">
                        <div class="module-icon" style="background-color: <?php echo htmlspecialchars($module['color'] ?? '#3498db'); ?>">
                            <span class="material-icons"><?php echo htmlspecialchars($module['icon'] ?? 'school'); ?></span>
                        </div>
                        <h3><?php echo htmlspecialchars($module['title']); ?></h3>
                    </div>
                    
                    <p><?php echo nl2br(htmlspecialchars($module['description'])); ?></p>
                    
                    <small>
                        Criado por: <?php echo htmlspecialchars($module['creator_name'] ?? 'Sistema'); ?><br>
                        Ordem: <?php echo htmlspecialchars($module['order_index']); ?>
                    </small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        function editModule(module) {
            document.getElementById('formTitle').textContent = 'Editar Módulo';
            document.querySelector('[name="action"]').value = 'update';
            document.querySelector('[name="id"]').value = module.id;
            document.querySelector('[name="title"]').value = module.title;
            document.querySelector('[name="description"]').value = module.description;
            document.querySelector('[name="icon"]').value = module.icon || '';
            document.querySelector('[name="color"]').value = module.color || '';
            document.querySelector('[name="order_index"]').value = module.order_index;
            
            updateIconPreview();
            updateColorPreview();
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function resetForm() {
            document.getElementById('formTitle').textContent = 'Criar Novo Módulo';
            document.getElementById('moduleForm').reset();
            document.querySelector('[name="action"]').value = 'create';
            document.querySelector('[name="id"]').value = '';
            
            updateIconPreview();
            updateColorPreview();
        }
        
        function updateIconPreview() {
            const icon = document.querySelector('[name="icon"]').value || 'school';
            document.getElementById('iconPreview').textContent = icon;
        }
        
        function updateColorPreview() {
            const color = document.querySelector('[name="color"]').value || '#3498db';
            document.getElementById('colorPreview').style.backgroundColor = color;
        }
        
        // Event listeners
        document.querySelector('[name="icon"]').addEventListener('input', updateIconPreview);
        document.querySelector('[name="color"]').addEventListener('input', updateColorPreview);
        
        // Initial preview
        updateIconPreview();
        updateColorPreview();
    </script>
</body>
</html>
