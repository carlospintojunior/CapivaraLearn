<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/services/UniversityService.php';
require_once __DIR__ . '/includes/services/CourseService.php';

// Verificar se usuário está logado
requireLogin();

$universityService = UniversityService::getInstance();
$courseService = CourseService::getInstance();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_university':
                $universityService->create([
                    'nome' => $_POST['nome'],
                    'sigla' => $_POST['sigla'],
                    'cidade' => $_POST['cidade'],
                    'estado' => $_POST['estado']
                ]);
                header('Location: manage_universities.php?success=1');
                exit;
                
            case 'edit_university':
                $universityService->update($_POST['id'], [
                    'nome' => $_POST['nome'],
                    'sigla' => $_POST['sigla'],
                    'cidade' => $_POST['cidade'],
                    'estado' => $_POST['estado']
                ]);
                header('Location: manage_universities.php?success=2');
                exit;
                
            case 'delete_university':
                $universityService->delete($_POST['id']);
                header('Location: manage_universities.php?success=3');
                exit;
                
            case 'add_course_to_university':
                $universityService->addCourse($_POST['university_id'], $_POST['course_id']);
                header('Location: manage_universities.php?success=4&id=' . $_POST['university_id']);
                exit;
                
            case 'remove_course_from_university':
                $universityService->removeCourse($_POST['university_id'], $_POST['course_id']);
                header('Location: manage_universities.php?success=5&id=' . $_POST['university_id']);
                exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Buscar lista de universidades e cursos
$universities = $universityService->listAll();
$courses = $courseService->listAll();

// Se houver um ID específico, buscar cursos da universidade
$selectedUniversity = null;
$universityCourses = [];
if (isset($_GET['id'])) {
    $selectedUniversity = $universityService->getById($_GET['id']);
    if ($selectedUniversity) {
        $universityCourses = $universityService->listCourses($_GET['id']);
    }
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Universidades - CapivaraLearn</title>
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
        .university-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .university-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .university-card h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        .university-info {
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
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gerenciar Universidades</h1>
            <button class="btn" onclick="openAddModal()">Nova Universidade</button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <?php
                switch ($_GET['success']) {
                    case '1':
                        echo 'Universidade adicionada com sucesso!';
                        break;
                    case '2':
                        echo 'Universidade atualizada com sucesso!';
                        break;
                    case '3':
                        echo 'Universidade removida com sucesso!';
                        break;
                    case '4':
                        echo 'Curso adicionado à universidade com sucesso!';
                        break;
                    case '5':
                        echo 'Curso removido da universidade com sucesso!';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="university-grid">
            <?php foreach ($universities as $university): ?>
                <div class="university-card">
                    <h3><?= h($university['nome']) ?></h3>
                    <div class="university-info">
                        <p><strong>Sigla:</strong> <?= h($university['sigla']) ?></p>
                        <p><strong>Cidade:</strong> <?= h($university['cidade']) ?></p>
                        <p><strong>Estado:</strong> <?= h($university['estado']) ?></p>
                    </div>
                    <div class="actions">
                        <button class="btn" onclick="openEditModal(<?= $university['id'] ?>, '<?= h(addslashes($university['nome'])) ?>', '<?= h(addslashes($university['sigla'])) ?>', '<?= h(addslashes($university['cidade'])) ?>', '<?= h(addslashes($university['estado'])) ?>')">Editar</button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir esta universidade?');">
                            <input type="hidden" name="action" value="delete_university">
                            <input type="hidden" name="id" value="<?= $university['id'] ?>">
                            <button type="submit" class="btn btn-danger">Excluir</button>
                        </form>
                    </div>

                    <!-- Cursos da Universidade -->
                    <div class="university-courses" style="margin-top: 15px;">
                        <h4>Cursos Oferecidos:</h4>
                        <ul>
                            <?php foreach ($universityCourses as $course): ?>
                                <li>
                                    <?= h($course['nome']) ?> 
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja remover este curso?');">
                                        <input type="hidden" name="action" value="remove_course_from_university">
                                        <input type="hidden" name="university_id" value="<?= $university['id'] ?>">
                                        <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Remover</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <!-- Adicionar Curso -->
                        <form method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="action" value="add_course_to_university">
                            <input type="hidden" name="university_id" value="<?= $university['id'] ?>">
                            <div class="form-group" style="display: inline-block; width: calc(100% - 120px);">
                                <label>Adicionar Curso</label>
                                <select name="course_id" required>
                                    <option value="">Selecione um curso</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?= $course['id'] ?>"><?= h($course['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn" style="display: inline-block;">Adicionar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($universities)): ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <p>Nenhuma universidade cadastrada.</p>
                <p>Clique em "Nova Universidade" para começar!</p>
            </div>
        <?php endif; ?>

        <?php if (isset($selectedUniversity)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Cursos de <?php echo htmlspecialchars($selectedUniversity['nome']); ?></h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                            Adicionar Curso
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nome do Curso</th>
                                    <th>Área</th>
                                    <th>Nível</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($universityCourses as $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($course['area']); ?></td>
                                        <td><?php echo htmlspecialchars($course['nivel']); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover este curso da universidade?');">
                                                <input type="hidden" name="action" value="remove_course_from_university">
                                                <input type="hidden" name="university_id" value="<?php echo $selectedUniversity['id']; ?>">
                                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Remover</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Adicionar Curso -->
            <div class="modal fade" id="addCourseModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Adicionar Curso à <?php echo htmlspecialchars($selectedUniversity['nome']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_course_to_university">
                            <input type="hidden" name="university_id" value="<?php echo $selectedUniversity['id']; ?>">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="course_id" class="form-label">Curso</label>
                                    <select class="form-select" id="course_id" name="course_id" required>
                                        <option value="">Selecione um curso</option>
                                        <?php foreach ($courses as $course): ?>
                                            <?php if (!in_array($course['id'], array_column($universityCourses, 'id'))): ?>
                                                <option value="<?php echo $course['id']; ?>">
                                                    <?php echo htmlspecialchars($course['nome']); ?> (<?php echo htmlspecialchars($course['nivel']); ?>)
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Adicionar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Adicionar -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Nova Universidade</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_university">
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="nome" required>
                </div>
                <div class="form-group">
                    <label>Sigla</label>
                    <input type="text" name="sigla" required>
                </div>
                <div class="form-group">
                    <label>Cidade</label>
                    <input type="text" name="cidade" required>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <input type="text" name="estado" maxlength="2" required>
                </div>
                <div class="actions">
                    <button type="submit" class="btn">Salvar</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('addModal')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Editar Universidade</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit_university">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="nome" id="edit_nome" required>
                </div>
                <div class="form-group">
                    <label>Sigla</label>
                    <input type="text" name="sigla" id="edit_sigla" required>
                </div>
                <div class="form-group">
                    <label>Cidade</label>
                    <input type="text" name="cidade" id="edit_cidade" required>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <input type="text" name="estado" id="edit_estado" maxlength="2" required>
                </div>
                <div class="actions">
                    <button type="submit" class="btn">Atualizar</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('editModal')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }

        function openEditModal(id, nome, sigla, cidade, estado) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nome').value = nome;
            document.getElementById('edit_sigla').value = sigla;
            document.getElementById('edit_cidade').value = cidade;
            document.getElementById('edit_estado').value = estado;
            document.getElementById('editModal').style.display = 'flex';
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
