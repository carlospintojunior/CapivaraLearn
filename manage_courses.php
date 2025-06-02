<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/services/CourseService.php';

// Verificar se usuário está logado
requireLogin();

$courseService = CourseService::getInstance();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_course':
                $courseService->create([
                    'nome' => $_POST['nome'],
                    'area' => $_POST['area'],
                    'nivel' => $_POST['nivel']
                ]);
                header('Location: manage_courses.php?success=1');
                exit;
                
            case 'edit_course':
                $courseService->update($_POST['id'], [
                    'nome' => $_POST['nome'],
                    'area' => $_POST['area'],
                    'nivel' => $_POST['nivel']
                ]);
                header('Location: manage_courses.php?success=2');
                exit;
                
            case 'delete_course':
                $courseService->delete($_POST['id']);
                header('Location: manage_courses.php?success=3');
                exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Buscar lista de cursos
$courses = $courseService->listAll();
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>
    <title>Gerenciar Cursos - CapivaraLearn</title>
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
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .course-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .course-card h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        .course-info {
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
        .form-group input, .form-group select {
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
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-graduacao { background: #e3f2fd; color: #1976d2; }
        .badge-pos_graduacao { background: #f3e5f5; color: #7b1fa2; }
        .badge-mestrado { background: #e8f5e9; color: #388e3c; }
        .badge-doutorado { background: #fff3e0; color: #f57c00; }
        .badge-outros { background: #eceff1; color: #455a64; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gerenciar Cursos</h1>
            <button class="btn" onclick="openAddModal()">Novo Curso</button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <?php
                switch ($_GET['success']) {
                    case '1':
                        echo 'Curso adicionado com sucesso!';
                        break;
                    case '2':
                        echo 'Curso atualizado com sucesso!';
                        break;
                    case '3':
                        echo 'Curso removido com sucesso!';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="course-grid">
            <?php foreach ($courses as $course): ?>
                <div class="course-card">
                    <h3><?= h($course['nome']) ?></h3>
                    <div class="course-info">
                        <p><strong>Área:</strong> <?= h($course['area']) ?></p>
                        <span class="badge badge-<?= $course['nivel'] ?>">
                            <?php
                            $niveis = [
                                'graduacao' => 'Graduação',
                                'pos_graduacao' => 'Pós-graduação',
                                'mestrado' => 'Mestrado',
                                'doutorado' => 'Doutorado',
                                'outros' => 'Outros'
                            ];
                            echo $niveis[$course['nivel']] ?? $course['nivel'];
                            ?>
                        </span>
                    </div>
                    <div class="actions">
                        <button class="btn" onclick="openEditModal(<?= $course['id'] ?>, '<?= h(addslashes($course['nome'])) ?>', '<?= h(addslashes($course['area'])) ?>', '<?= h($course['nivel']) ?>')">Editar</button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este curso?');">
                            <input type="hidden" name="action" value="delete_course">
                            <input type="hidden" name="id" value="<?= $course['id'] ?>">
                            <button type="submit" class="btn btn-danger">Excluir</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($courses)): ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <p>Nenhum curso cadastrado.</p>
                <p>Clique em "Novo Curso" para começar!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Adicionar -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Novo Curso</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_course">
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="nome" required>
                </div>
                <div class="form-group">
                    <label>Área</label>
                    <input type="text" name="area" required>
                </div>
                <div class="form-group">
                    <label>Nível</label>
                    <select name="nivel" required>
                        <option value="graduacao">Graduação</option>
                        <option value="pos_graduacao">Pós-graduação</option>
                        <option value="mestrado">Mestrado</option>
                        <option value="doutorado">Doutorado</option>
                        <option value="outros">Outros</option>
                    </select>
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
            <h2>Editar Curso</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit_course">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="nome" id="edit_nome" required>
                </div>
                <div class="form-group">
                    <label>Área</label>
                    <input type="text" name="area" id="edit_area" required>
                </div>
                <div class="form-group">
                    <label>Nível</label>
                    <select name="nivel" id="edit_nivel" required>
                        <option value="graduacao">Graduação</option>
                        <option value="pos_graduacao">Pós-graduação</option>
                        <option value="mestrado">Mestrado</option>
                        <option value="doutorado">Doutorado</option>
                        <option value="outros">Outros</option>
                    </select>
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

        function openEditModal(id, nome, area, nivel) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nome').value = nome;
            document.getElementById('edit_area').value = area;
            document.getElementById('edit_nivel').value = nivel;
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