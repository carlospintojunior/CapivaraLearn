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
                header('Location: manage_universities.php?success=4');
                exit;
                
            case 'remove_course_from_university':
                $universityService->removeCourse($_POST['university_id'], $_POST['course_id']);
                header('Location: manage_universities.php?success=5');
                exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Buscar lista de universidades e cursos
$universities = $universityService->listAll();
$courses = $courseService->listAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">🏛️ Gerenciar Universidades</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUniversityModal">
            <i class="fas fa-plus me-2"></i>Nova Universidade
        </button>
    </div>

    <!-- Mensagens de Sucesso e Erro -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php
            switch ($_GET['success']) {
                case '1': echo 'Universidade adicionada com sucesso!'; break;
                case '2': echo 'Universidade atualizada com sucesso!'; break;
                case '3': echo 'Universidade removida com sucesso!'; break;
                case '4': echo 'Curso adicionado à universidade com sucesso!'; break;
                case '5': echo 'Curso removido da universidade com sucesso!'; break;
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Grid de Universidades -->
    <div class="row g-4">
        <?php foreach ($universities as $university): ?>
            <div class="col-lg-6 col-xl-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 text-truncate">
                            <?= htmlspecialchars($university['nome']) ?>
                        </h5>
                        <span class="badge bg-light text-dark">
                            <?= htmlspecialchars($university['sigla']) ?>
                        </span>
                    </div>
                    
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="row text-muted small">
                                <div class="col-sm-4"><strong>Cidade:</strong></div>
                                <div class="col-sm-8"><?= htmlspecialchars($university['cidade']) ?></div>
                            </div>
                            <div class="row text-muted small">
                                <div class="col-sm-4"><strong>Estado:</strong></div>
                                <div class="col-sm-8"><?= htmlspecialchars($university['estado']) ?></div>
                            </div>
                        </div>

                        <!-- Cursos Oferecidos -->
                        <div class="mb-3">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-graduation-cap me-1"></i>Cursos Oferecidos
                            </h6>
                            <div id="courses-<?= $university['id'] ?>" class="courses-list">
                                <div class="text-center text-muted">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Adicionar Curso -->
                        <div class="mb-3">
                            <div class="input-group input-group-sm">
                                <select class="form-select" id="course-select-<?= $university['id'] ?>">
                                    <option value="">Adicionar curso...</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?= $course['id'] ?>">
                                            <?= htmlspecialchars($course['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-outline-primary" type="button" 
                                        onclick="addCourseToUniversity(<?= $university['id'] ?>)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light d-flex justify-content-between">
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="editUniversity(<?= $university['id'] ?>, '<?= htmlspecialchars(addslashes($university['nome'])) ?>', '<?= htmlspecialchars(addslashes($university['sigla'])) ?>', '<?= htmlspecialchars(addslashes($university['cidade'])) ?>', '<?= htmlspecialchars(addslashes($university['estado'])) ?>')">
                            <i class="fas fa-edit me-1"></i>Editar
                        </button>
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="deleteUniversity(<?= $university['id'] ?>, '<?= htmlspecialchars(addslashes($university['nome'])) ?>')">
                            <i class="fas fa-trash me-1"></i>Excluir
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($universities)): ?>
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-university fa-4x text-muted"></i>
            </div>
            <h4 class="text-muted">Nenhuma universidade cadastrada</h4>
            <p class="text-muted">Clique em "Nova Universidade" para começar!</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Adicionar Universidade -->
<div class="modal fade" id="addUniversityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-university me-2"></i>Nova Universidade
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUniversityForm" method="POST">
                <input type="hidden" name="action" value="add_university">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_nome" class="form-label">Nome da Universidade</label>
                        <input type="text" class="form-control" id="add_nome" name="nome" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_sigla" class="form-label">Sigla</label>
                                <input type="text" class="form-control" id="add_sigla" name="sigla" required maxlength="10">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_estado" class="form-label">Estado</label>
                                <input type="text" class="form-control" id="add_estado" name="estado" required maxlength="2" placeholder="SP">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="add_cidade" class="form-label">Cidade</label>
                        <input type="text" class="form-control" id="add_cidade" name="cidade" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Universidade -->
<div class="modal fade" id="editUniversityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Editar Universidade
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUniversityForm" method="POST">
                <input type="hidden" name="action" value="edit_university">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nome" class="form-label">Nome da Universidade</label>
                        <input type="text" class="form-control" id="edit_nome" name="nome" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_sigla" class="form-label">Sigla</label>
                                <input type="text" class="form-control" id="edit_sigla" name="sigla" required maxlength="10">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_estado" class="form-label">Estado</label>
                                <input type="text" class="form-control" id="edit_estado" name="estado" required maxlength="2">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_cidade" class="form-label">Cidade</label>
                        <input type="text" class="form-control" id="edit_cidade" name="cidade" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Atualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Formulário oculto para ações -->
<form id="actionForm" method="POST" style="display: none;">
    <input type="hidden" name="action" id="formAction">
    <input type="hidden" name="id" id="formId">
    <input type="hidden" name="university_id" id="formUniversityId">
    <input type="hidden" name="course_id" id="formCourseId">
</form>

<script>
// Carregar cursos da universidade via AJAX (simulado)
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($universities as $university): ?>
        loadUniversityCourses(<?= $university['id'] ?>);
    <?php endforeach; ?>
});

function loadUniversityCourses(universityId) {
    // Por enquanto, vamos simular o carregamento
    setTimeout(() => {
        const container = document.getElementById(`courses-${universityId}`);
        
        // Aqui você implementaria uma chamada AJAX real para buscar os cursos
        // Por enquanto, vamos mostrar uma mensagem
        container.innerHTML = `
            <div class="text-muted small">
                <i class="fas fa-info-circle me-1"></i>
                Carregue os cursos via AJAX ou implemente listCourses()
            </div>
        `;
    }, 500);
}

function editUniversity(id, nome, sigla, cidade, estado) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nome').value = nome;
    document.getElementById('edit_sigla').value = sigla;
    document.getElementById('edit_cidade').value = cidade;
    document.getElementById('edit_estado').value = estado;
    
    const modal = new bootstrap.Modal(document.getElementById('editUniversityModal'));
    modal.show();
}

function deleteUniversity(id, nome) {
    if (confirm(`Tem certeza que deseja excluir a universidade "${nome}"?\n\nEsta ação não pode ser desfeita.`)) {
        document.getElementById('formAction').value = 'delete_university';
        document.getElementById('formId').value = id;
        document.getElementById('actionForm').submit();
    }
}

function addCourseToUniversity(universityId) {
    const select = document.getElementById(`course-select-${universityId}`);
    const courseId = select.value;
    
    if (!courseId) {
        alert('Por favor, selecione um curso.');
        return;
    }
    
    if (confirm('Adicionar este curso à universidade?')) {
        document.getElementById('formAction').value = 'add_course_to_university';
        document.getElementById('formUniversityId').value = universityId;
        document.getElementById('formCourseId').value = courseId;
        document.getElementById('actionForm').submit();
    }
}

function removeCourseFromUniversity(universityId, courseId, courseName) {
    if (confirm(`Remover o curso "${courseName}" desta universidade?`)) {
        document.getElementById('formAction').value = 'remove_course_from_university';
        document.getElementById('formUniversityId').value = universityId;
        document.getElementById('formCourseId').value = courseId;
        document.getElementById('actionForm').submit();
    }
}

// Validação de formulários
document.getElementById('addUniversityForm').addEventListener('submit', function(e) {
    const estado = document.getElementById('add_estado').value.toUpperCase();
    if (estado.length !== 2) {
        e.preventDefault();
        alert('O estado deve ter exatamente 2 letras (ex: SP, RJ).');
        return;
    }
    document.getElementById('add_estado').value = estado;
});

document.getElementById('editUniversityForm').addEventListener('submit', function(e) {
    const estado = document.getElementById('edit_estado').value.toUpperCase();
    if (estado.length !== 2) {
        e.preventDefault();
        alert('O estado deve ter exatamente 2 letras (ex: SP, RJ).');
        return;
    }
    document.getElementById('edit_estado').value = estado;
});
</script>

<style>
.courses-list {
    max-height: 120px;
    overflow-y: auto;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 0.5rem;
    background-color: #f8f9fa;
}

.course-item {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 0.25rem 0;
    border-bottom: 1px solid #dee2e6;
}

.course-item:last-child {
    border-bottom: none;
}

.course-item .course-name {
    flex: 1;
    font-size: 0.875rem;
}

.course-item .btn {
    margin-left: 0.5rem;
}

.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
}

.input-group-sm .form-select {
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .col-lg-6 {
        margin-bottom: 1rem;
    }
    
    .card-footer .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>