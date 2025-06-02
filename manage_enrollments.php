<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/services/CourseService.php';
require_once __DIR__ . '/includes/services/UniversityService.php';

// Verificar se usuário está logado
requireLogin();

$courseService = CourseService::getInstance();
$universityService = UniversityService::getInstance();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_enrollment':
                $courseService->enrollStudent(
                    $_POST['user_id'],
                    $_POST['course_id'],
                    $_POST['university_id'],
                    [
                        'data_inicio' => $_POST['data_inicio'] ?? date('Y-m-d'),
                        'situacao' => 'cursando'
                    ]
                );
                header('Location: manage_enrollments.php?success=1');
                exit;
                
            case 'remove_enrollment':
                $courseService->unenrollStudent(
                    $_POST['user_id'],
                    $_POST['course_id'],
                    $_POST['university_id']
                );
                header('Location: manage_enrollments.php?success=2');
                exit;
                
            case 'update_status':
                $courseService->updateEnrollmentStatus(
                    $_POST['user_id'],
                    $_POST['course_id'],
                    $_POST['university_id'],
                    $_POST['situacao'],
                    $_POST['data_fim'] ?? null
                );
                header('Location: manage_enrollments.php?success=3');
                exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Buscar listas necessárias
$enrollments = $courseService->listEnrollments();
$courses = $courseService->listAll();
$users = $courseService->listAvailableStudents();
$universities = $universityService->listAll();
$stats = $courseService->getEnrollmentStats();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Add enrollment.js -->
<script src="assets/js/enrollment.js"></script>

<h1 class="mb-4">Gerenciar Matrículas</h1>

<!-- Statistics Section -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total de Matrículas</h5>
                <p class="card-text h2"><?php echo number_format($stats['total_matriculas']); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Cursando</h5>
                <p class="card-text h2"><?php echo number_format($stats['total_cursando']); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Concluídos</h5>
                <p class="card-text h2"><?php echo number_format($stats['total_concluidos']); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Trancados</h5>
                <p class="card-text h2"><?php echo number_format($stats['total_trancados']); ?></p>
            </div>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        switch ($_GET['success']) {
            case 1:
                echo 'Matrícula realizada com sucesso!';
                break;
            case 2:
                echo 'Matrícula cancelada com sucesso!';
                break;
        }
        ?>
    </div>
<?php endif; ?>

<div class="mb-3">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEnrollmentModal">
        Nova Matrícula
    </button>
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Aluno</th>
                <th>Curso</th>
                <th>Data de Matrícula</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($enrollments as $enrollment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($enrollment['id']); ?></td>
                    <td><?php echo htmlspecialchars($enrollment['nome_aluno']); ?></td>
                    <td><?php echo htmlspecialchars($enrollment['nome_curso']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($enrollment['data_matricula'])); ?></td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                Ações
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="#" class="dropdown-item" 
                                       data-bs-toggle="modal" 
                                       data-bs-target="#viewHistoryModal"
                                       data-enrollment-id="<?php echo $enrollment['id']; ?>"
                                       onclick="loadEnrollmentHistory(<?php echo $enrollment['id']; ?>)">
                                        📋 Ver Histórico
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" class="dropdown-item-form" onsubmit="return confirm('Confirma alteração para Cursando?');">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="user_id" value="<?php echo $enrollment['user_id']; ?>">
                                        <input type="hidden" name="course_id" value="<?php echo $enrollment['course_id']; ?>">
                                        <input type="hidden" name="university_id" value="<?php echo $enrollment['universidade_id']; ?>">
                                        <input type="hidden" name="situacao" value="cursando">
                                        <button type="submit" class="dropdown-item text-success">✓ Marcar como Cursando</button>
                                    </form>
                                </li>
                                <li>
                                    <form method="POST" class="dropdown-item-form" onsubmit="return confirm('Confirma o trancamento da matrícula?');">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="user_id" value="<?php echo $enrollment['user_id']; ?>">
                                        <input type="hidden" name="course_id" value="<?php echo $enrollment['course_id']; ?>">
                                        <input type="hidden" name="university_id" value="<?php echo $enrollment['universidade_id']; ?>">
                                        <input type="hidden" name="situacao" value="trancado">
                                        <button type="submit" class="dropdown-item text-warning">⏸ Trancar Matrícula</button>
                                    </form>
                                </li>
                                <li>
                                    <form method="POST" class="dropdown-item-form" onsubmit="return confirm('Confirmar conclusão do curso?');">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="user_id" value="<?php echo $enrollment['user_id']; ?>">
                                        <input type="hidden" name="course_id" value="<?php echo $enrollment['course_id']; ?>">
                                        <input type="hidden" name="university_id" value="<?php echo $enrollment['universidade_id']; ?>">
                                        <input type="hidden" name="situacao" value="concluido">
                                        <input type="hidden" name="data_fim" value="<?php echo date('Y-m-d'); ?>">
                                        <button type="submit" class="dropdown-item text-info">🎓 Marcar como Concluído</button>
                                    </form>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" class="dropdown-item-form" onsubmit="return confirm('Tem certeza que deseja cancelar esta matrícula?');">
                                        <input type="hidden" name="action" value="remove_enrollment">
                                        <input type="hidden" name="user_id" value="<?php echo $enrollment['user_id']; ?>">
                                        <input type="hidden" name="course_id" value="<?php echo $enrollment['course_id']; ?>">
                                        <input type="hidden" name="university_id" value="<?php echo $enrollment['universidade_id']; ?>">
                                        <button type="submit" class="dropdown-item text-danger">❌ Cancelar Matrícula</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Nova Matrícula -->
<div class="modal fade" id="addEnrollmentModal" tabindex="-1" aria-labelledby="addEnrollmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEnrollmentModalLabel">Nova Matrícula</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="enrollmentForm" method="POST" action="manage_enrollments.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_enrollment">
                    
                    <div class="mb-3">
                        <label for="university_id" class="form-label">Universidade</label>
                        <select class="form-select" id="university_id" name="university_id" required>
                            <option value="">Selecione uma universidade</option>
                            <?php foreach ($universities as $university): ?>
                                <option value="<?php echo htmlspecialchars($university['id']); ?>">
                                    <?php echo htmlspecialchars($university['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="course_id" class="form-label">Curso</label>
                        <select class="form-select" id="course_id" name="course_id" required disabled>
                            <option value="">Selecione um curso</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="user_id" class="form-label">Aluno</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">Selecione um aluno</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo htmlspecialchars($user['id']); ?>">
                                    <?php echo htmlspecialchars($user['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="data_inicio" class="form-label">Data de Início</label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio" required>
                    </div>

                    <div class="mb-3">
                        <label for="data_fim" class="form-label">Data de Término (opcional)</label>
                        <input type="date" class="form-control" id="data_fim" name="data_fim">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Matricular</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Histórico -->
<div class="modal fade" id="viewHistoryModal" tabindex="-1" aria-labelledby="viewHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewHistoryModalLabel">Histórico da Matrícula</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="historyContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
