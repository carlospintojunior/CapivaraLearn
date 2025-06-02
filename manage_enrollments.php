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
                    $_POST['usuario_id'],
                    $_POST['curso_id'],
                    $_POST['universidade_id']
                );
                header('Location: manage_enrollments.php?success=2');
                exit;
                
            case 'update_status':
                $courseService->updateEnrollmentStatus(
                    $_POST['usuario_id'],
                    $_POST['curso_id'],
                    $_POST['universidade_id'],
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
            case 3:
                echo 'Status da matrícula atualizado com sucesso!';
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

<div class="table-responsive" style="min-height: 400px; max-height: none;">
    <table class="table table-striped">
        <thead class="table-dark sticky-top">
            <tr>
                <th>ID</th>
                <th>Aluno</th>
                <th>Curso</th>
                <th>Universidade</th>
                <th>Situação</th>
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
                    <td>
                        <?php echo htmlspecialchars($enrollment['nome_universidade']); ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($enrollment['universidade_sigla']); ?></small>
                    </td>
                    <td>
                        <?php
                        $situacao = $enrollment['situacao'];
                        $badge_class = match($situacao) {
                            'cursando' => 'bg-success',
                            'concluido' => 'bg-primary',
                            'trancado' => 'bg-warning',
                            default => 'bg-secondary'
                        };
                        echo "<span class='badge $badge_class'>" . ucfirst($situacao) . "</span>";
                        ?>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($enrollment['data_matricula'])); ?></td>
                    <td>
                        <div class="dropdown position-static">
                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                Ações
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="showHistory(<?php echo $enrollment['id']; ?>)">
                                        📋 Ver Histórico
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="#" 
                                       onclick="updateStatus(<?php echo $enrollment['usuario_id']; ?>, <?php echo $enrollment['curso_id']; ?>, <?php echo $enrollment['universidade_id']; ?>, 'cursando', 'Confirma alteração para Cursando?')">
                                        ▶️ Alterar para Cursando
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" 
                                       onclick="updateStatus(<?php echo $enrollment['usuario_id']; ?>, <?php echo $enrollment['curso_id']; ?>, <?php echo $enrollment['universidade_id']; ?>, 'concluido', 'Confirma conclusão do curso?', '<?php echo date('Y-m-d'); ?>')">
                                        ✅ Marcar como Concluído
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" 
                                       onclick="updateStatus(<?php echo $enrollment['usuario_id']; ?>, <?php echo $enrollment['curso_id']; ?>, <?php echo $enrollment['universidade_id']; ?>, 'trancado', 'Confirma o trancamento da matrícula?')">
                                        ⏸️ Trancar Matrícula
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" 
                                       onclick="removeEnrollment(<?php echo $enrollment['usuario_id']; ?>, <?php echo $enrollment['curso_id']; ?>, <?php echo $enrollment['universidade_id']; ?>, 'Tem certeza que deseja cancelar esta matrícula?')">
                                        ❌ Cancelar Matrícula
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Formulário oculto para ações -->
<form id="actionForm" method="POST" style="display: none;">
    <input type="hidden" name="action" id="formAction">
    <input type="hidden" name="usuario_id" id="formUsuarioId">
    <input type="hidden" name="curso_id" id="formCursoId">
    <input type="hidden" name="universidade_id" id="formUniversidadeId">
    <input type="hidden" name="situacao" id="formSituacao">
    <input type="hidden" name="data_fim" id="formDataFim">
</form>

<script>
function updateStatus(usuarioId, cursoId, universidadeId, situacao, confirmMessage, dataFim = '') {
    if (confirm(confirmMessage)) {
        document.getElementById('formAction').value = 'update_status';
        document.getElementById('formUsuarioId').value = usuarioId;
        document.getElementById('formCursoId').value = cursoId;
        document.getElementById('formUniversidadeId').value = universidadeId;
        document.getElementById('formSituacao').value = situacao;
        document.getElementById('formDataFim').value = dataFim;
        document.getElementById('actionForm').submit();
    }
}

function removeEnrollment(usuarioId, cursoId, universidadeId, confirmMessage) {
    if (confirm(confirmMessage)) {
        document.getElementById('formAction').value = 'remove_enrollment';
        document.getElementById('formUsuarioId').value = usuarioId;
        document.getElementById('formCursoId').value = cursoId;
        document.getElementById('formUniversidadeId').value = universidadeId;
        document.getElementById('actionForm').submit();
    }
}

function showHistory(enrollmentId) {
    // Implementar modal de histórico se necessário
    const modal = new bootstrap.Modal(document.getElementById('viewHistoryModal'));
    document.getElementById('historyContent').innerHTML = `
        <div class="alert alert-info">
            <h5>📋 Histórico da Matrícula #${enrollmentId}</h5>
            <p>Esta funcionalidade pode ser implementada para mostrar:</p>
            <ul>
                <li>Histórico de mudanças de status</li>
                <li>Datas importantes</li>
                <li>Notas e observações</li>
            </ul>
        </div>
    `;
    modal.show();
}
</script>

<style>
    .dropdown-item {
        padding: 8px 20px;
        white-space: nowrap;
        cursor: pointer;
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #000;
    }
    
    .dropdown-item.text-danger:hover {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .dropdown-menu {
        min-width: 220px;
        z-index: 1055 !important;
        position: absolute !important;
    }
    
    .dropdown-divider {
        margin: 0.5rem 0;
    }
    
    .badge {
        font-size: 0.75em;
    }
    
    /* Melhorias na tabela */
    .table-responsive {
        overflow-x: auto;
        overflow-y: visible !important;
    }
    
    /* Header fixo quando necessário */
    .sticky-top {
        position: sticky !important;
        top: 0;
        z-index: 1020;
    }
    
    /* Melhor visualização em telas pequenas */
    @media (max-width: 768px) {
        .dropdown-menu {
            min-width: 180px;
        }
        
        .table td, .table th {
            white-space: nowrap;
            font-size: 0.875em;
        }
    }
    
    /* Garantir que dropdowns não sejam cortados */
    .position-static {
        position: static !important;
    }
    
    /* Container principal com espaço suficiente */
    .container-fluid {
        padding-bottom: 100px;
    }
</style>

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