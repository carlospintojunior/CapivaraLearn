<?php
session_start();
require_once "includes/config.php";
require_once "includes/CourseService.php";

// Verificar se o usuário está logado e tem permissão
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

$courseService = CourseService::getInstance();
$message = '';
$error = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $code = trim($_POST['code']);
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                
                if ($courseService->createCourse($code, $title, $description)) {
                    $message = "Curso criado com sucesso!";
                } else {
                    $error = "Erro ao criar curso: " . $courseService->getLastError();
                }
                break;
                
            case 'update':
                $id = intval($_POST['id']);
                $code = trim($_POST['code']);
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $status = trim($_POST['status']);
                
                if ($courseService->updateCourse($id, $code, $title, $description, $status)) {
                    $message = "Curso atualizado com sucesso!";
                } else {
                    $error = "Erro ao atualizar curso: " . $courseService->getLastError();
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                if ($courseService->deleteCourse($id)) {
                    $message = "Curso excluído com sucesso!";
                } else {
                    $error = "Erro ao excluir curso: " . $courseService->getLastError();
                }
                break;
        }
    }
}

// Buscar todos os cursos
$courses = $courseService->getAllCourses();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cursos - CapivaraLearn</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="/src/css/style.css">
    <style>
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .course-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .course-card .actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 10px;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Gerenciar Cursos</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Formulário de Criação/Edição -->
        <div class="card">
            <h2 id="formTitle">Criar Novo Curso</h2>
            <form id="courseForm" method="POST" class="form">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="id" value="">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="code">Código</label>
                        <input type="text" id="code" name="code" required class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Título</label>
                        <input type="text" id="title" name="title" required class="form-control">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="description">Descrição</label>
                        <textarea id="description" name="description" required class="form-control" rows="4"></textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Salvar Curso</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">Limpar</button>
                </div>
            </form>
        </div>
        
        <!-- Lista de Cursos -->
        <div class="course-grid">
            <?php foreach ($courses as $course): ?>
                <div class="course-card">
                    <div class="actions">
                        <button onclick="editCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)" class="btn btn-sm btn-secondary">
                            <span class="material-icons">edit</span>
                        </button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este curso?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($course['id']); ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <span class="material-icons">delete</span>
                            </button>
                        </form>
                    </div>
                    
                    <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                    <p><strong>Código:</strong> <?php echo htmlspecialchars($course['code']); ?></p>
                    <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                    
                    <small>
                        Criado por: <?php echo htmlspecialchars($course['creator_name'] ?? 'Sistema'); ?><br>
                        Módulos: <?php echo htmlspecialchars($course['module_count']); ?>
                    </small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        function editCourse(course) {
            document.getElementById('formTitle').textContent = 'Editar Curso';
            document.querySelector('[name="action"]').value = 'update';
            document.querySelector('[name="id"]').value = course.id;
            document.querySelector('[name="code"]').value = course.code;
            document.querySelector('[name="title"]').value = course.title;
            document.querySelector('[name="description"]').value = course.description;
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function resetForm() {
            document.getElementById('formTitle').textContent = 'Criar Novo Curso';
            document.getElementById('courseForm').reset();
            document.querySelector('[name="action"]').value = 'create';
            document.querySelector('[name="id"]').value = '';
        }
    </script>
</body>
</html>
