<?php
session_start();

// Verificar login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Carregar dependências
require_once 'Medoo.php';
require_once __DIR__ . '/includes/version.php';

// Configuração do banco
$database = new Medoo\Medoo([
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'capivaralearn',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
]);

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$import_stats = [];

// Processar upload do arquivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
    try {
        // Verificar se o arquivo foi enviado corretamente
        if ($_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload do arquivo.');
        }

        // Verificar o tipo do arquivo
        $file_info = pathinfo($_FILES['backup_file']['name']);
        if (strtolower($file_info['extension']) !== 'json') {
            throw new Exception('Apenas arquivos JSON são aceitos.');
        }

        // Ler o conteúdo do arquivo
        $json_content = file_get_contents($_FILES['backup_file']['tmp_name']);
        if ($json_content === false) {
            throw new Exception('Não foi possível ler o arquivo.');
        }

        // Decodificar JSON
        $backup_data = json_decode($json_content, true);
        if ($backup_data === null) {
            throw new Exception('Arquivo JSON inválido.');
        }

        // Verificar estrutura do backup
        if (!isset($backup_data['backup_info']) || !isset($backup_data['backup_info']['type']) || 
            $backup_data['backup_info']['type'] !== 'complete_user_data') {
            throw new Exception('Este não é um arquivo de backup de dados de usuário válido.');
        }

        // Iniciar transação
        $database->pdo->beginTransaction();

        $counters = [
            'universidades' => 0,
            'cursos' => 0,
            'disciplinas' => 0,
            'topicos' => 0,
            'unidades' => 0,
            'matriculas' => 0,
            'skipped' => 0
        ];

        // Mapear IDs antigos para novos
        $id_mapping = [
            'universidades' => [],
            'cursos' => [],
            'disciplinas' => [],
            'topicos' => []
        ];

        // Importar universidades
        if (isset($backup_data['universities']) && is_array($backup_data['universities'])) {
            foreach ($backup_data['universities'] as $uni) {
                // Verificar se já existe
                $existing = $database->get("universidades", "id", [
                    "nome" => $uni['nome'],
                    "usuario_id" => $user_id
                ]);

                if (!$existing) {
                    $old_id = $uni['id'];
                    unset($uni['id']);
                    $uni['usuario_id'] = $user_id;
                    $uni['data_criacao'] = date('Y-m-d H:i:s');
                    $uni['data_atualizacao'] = date('Y-m-d H:i:s');

                    $new_id = $database->insert("universidades", $uni);
                    $id_mapping['universidades'][$old_id] = $new_id;
                    $counters['universidades']++;
                } else {
                    $id_mapping['universidades'][$uni['id']] = $existing;
                    $counters['skipped']++;
                }
            }
        }

        // Importar cursos
        if (isset($backup_data['courses']) && is_array($backup_data['courses'])) {
            foreach ($backup_data['courses'] as $curso) {
                // Verificar se já existe
                $existing = $database->get("cursos", "id", [
                    "nome" => $curso['nome'],
                    "usuario_id" => $user_id
                ]);

                if (!$existing) {
                    $old_id = $curso['id'];
                    $old_uni_id = $curso['universidade_id'];
                    
                    unset($curso['id']);
                    $curso['usuario_id'] = $user_id;
                    $curso['universidade_id'] = $id_mapping['universidades'][$old_uni_id] ?? $old_uni_id;
                    $curso['data_criacao'] = date('Y-m-d H:i:s');
                    $curso['data_atualizacao'] = date('Y-m-d H:i:s');

                    $new_id = $database->insert("cursos", $curso);
                    $id_mapping['cursos'][$old_id] = $new_id;
                    $counters['cursos']++;
                } else {
                    $id_mapping['cursos'][$curso['id']] = $existing;
                    $counters['skipped']++;
                }
            }
        }

        // Importar disciplinas
        if (isset($backup_data['subjects']) && is_array($backup_data['subjects'])) {
            foreach ($backup_data['subjects'] as $disc) {
                // Verificar se já existe
                $existing = $database->get("disciplinas", "id", [
                    "nome" => $disc['nome'],
                    "curso_id" => $id_mapping['cursos'][$disc['curso_id']] ?? $disc['curso_id'],
                    "usuario_id" => $user_id
                ]);

                if (!$existing) {
                    $old_id = $disc['id'];
                    $old_curso_id = $disc['curso_id'];
                    
                    unset($disc['id']);
                    $disc['usuario_id'] = $user_id;
                    $disc['curso_id'] = $id_mapping['cursos'][$old_curso_id] ?? $old_curso_id;
                    $disc['data_criacao'] = date('Y-m-d H:i:s');
                    $disc['data_atualizacao'] = date('Y-m-d H:i:s');

                    $new_id = $database->insert("disciplinas", $disc);
                    $id_mapping['disciplinas'][$old_id] = $new_id;
                    $counters['disciplinas']++;
                } else {
                    $id_mapping['disciplinas'][$disc['id']] = $existing;
                    $counters['skipped']++;
                }
            }
        }

        // Importar tópicos
        if (isset($backup_data['topics']) && is_array($backup_data['topics'])) {
            foreach ($backup_data['topics'] as $topico) {
                // Verificar se já existe
                $existing = $database->get("topicos", "id", [
                    "nome" => $topico['nome'],
                    "disciplina_id" => $id_mapping['disciplinas'][$topico['disciplina_id']] ?? $topico['disciplina_id'],
                    "usuario_id" => $user_id
                ]);

                if (!$existing) {
                    $old_id = $topico['id'];
                    $old_disc_id = $topico['disciplina_id'];
                    
                    unset($topico['id']);
                    $topico['usuario_id'] = $user_id;
                    $topico['disciplina_id'] = $id_mapping['disciplinas'][$old_disc_id] ?? $old_disc_id;
                    $topico['data_criacao'] = date('Y-m-d H:i:s');
                    $topico['data_atualizacao'] = date('Y-m-d H:i:s');

                    $new_id = $database->insert("topicos", $topico);
                    $id_mapping['topicos'][$old_id] = $new_id;
                    $counters['topicos']++;
                } else {
                    $id_mapping['topicos'][$topico['id']] = $existing;
                    $counters['skipped']++;
                }
            }
        }

        // Importar unidades de aprendizagem
        if (isset($backup_data['learning_units']) && is_array($backup_data['learning_units'])) {
            foreach ($backup_data['learning_units'] as $unidade) {
                // Verificar se já existe
                $existing = $database->get("unidades_aprendizagem", "id", [
                    "nome" => $unidade['nome'],
                    "topico_id" => $id_mapping['topicos'][$unidade['topico_id']] ?? $unidade['topico_id'],
                    "usuario_id" => $user_id
                ]);

                if (!$existing) {
                    $old_topico_id = $unidade['topico_id'];
                    
                    unset($unidade['id']);
                    $unidade['usuario_id'] = $user_id;
                    $unidade['topico_id'] = $id_mapping['topicos'][$old_topico_id] ?? $old_topico_id;
                    $unidade['data_criacao'] = date('Y-m-d H:i:s');
                    $unidade['data_atualizacao'] = date('Y-m-d H:i:s');

                    $database->insert("unidades_aprendizagem", $unidade);
                    $counters['unidades']++;
                } else {
                    $counters['skipped']++;
                }
            }
        }

        // Importar matrículas
        if (isset($backup_data['enrollments']) && is_array($backup_data['enrollments'])) {
            foreach ($backup_data['enrollments'] as $matricula) {
                // Verificar se já existe
                $existing = $database->get("matriculas", "id", [
                    "curso_id" => $id_mapping['cursos'][$matricula['curso_id']] ?? $matricula['curso_id'],
                    "usuario_id" => $user_id
                ]);

                if (!$existing) {
                    $old_curso_id = $matricula['curso_id'];
                    
                    unset($matricula['id']);
                    $matricula['usuario_id'] = $user_id;
                    $matricula['curso_id'] = $id_mapping['cursos'][$old_curso_id] ?? $old_curso_id;
                    $matricula['data_criacao'] = date('Y-m-d H:i:s');
                    $matricula['data_atualizacao'] = date('Y-m-d H:i:s');

                    $database->insert("matriculas", $matricula);
                    $counters['matriculas']++;
                } else {
                    $counters['skipped']++;
                }
            }
        }

        // Commit da transação
        $database->pdo->commit();

        $import_stats = $counters;
        $success_message = "Backup restaurado com sucesso!";

    } catch (Exception $e) {
        // Rollback em caso de erro
        if ($database->pdo->inTransaction()) {
            $database->pdo->rollback();
        }
        $error_message = "Erro ao restaurar backup: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurar Dados do Usuário - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .bg-gradient-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        }
        .upload-area {
            border: 2px dashed #28a745;
            border-radius: 15px;
            padding: 3rem 2rem;
            text-align: center;
            background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%);
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #20c997;
            background: linear-gradient(135deg, #f0fff0 0%, #d4edda 100%);
        }
        .upload-area.dragover {
            border-color: #007bff;
            background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
        }
        .stats-success {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            border-radius: 10px;
            padding: 1rem;
            color: white;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <div class="card card-custom">
                    <div class="card-body text-center bg-gradient-success text-white">
                        <h1 class="display-6 mb-3">
                            <i class="fas fa-upload me-3"></i>
                            Restaurar Dados do Usuário
                        </h1>
                        <p class="lead mb-0">Importar backup completo dos seus dados acadêmicos</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegação -->
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-white p-3 rounded shadow-sm">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php" class="text-decoration-none">
                                <i class="fas fa-home me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="breadcrumb-item active">Restaurar Dados</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if ($success_message): ?>
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($import_stats)): ?>
        <!-- Estatísticas de Importação -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Resultados da Restauração</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 mb-2">
                                <div class="stats-success text-center">
                                    <h4><?php echo $import_stats['universidades']; ?></h4>
                                    <small>Universidades</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="stats-success text-center">
                                    <h4><?php echo $import_stats['cursos']; ?></h4>
                                    <small>Cursos</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="stats-success text-center">
                                    <h4><?php echo $import_stats['disciplinas']; ?></h4>
                                    <small>Disciplinas</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="stats-success text-center">
                                    <h4><?php echo $import_stats['topicos']; ?></h4>
                                    <small>Tópicos</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="stats-success text-center">
                                    <h4><?php echo $import_stats['unidades']; ?></h4>
                                    <small>Unidades</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="stats-success text-center">
                                    <h4><?php echo $import_stats['matriculas']; ?></h4>
                                    <small>Matrículas</small>
                                </div>
                            </div>
                        </div>
                        <?php if ($import_stats['skipped'] > 0): ?>
                        <div class="mt-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo $import_stats['skipped']; ?> itens foram ignorados por já existirem.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Formulário de Upload -->
        <div class="row">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-upload me-2"></i>Selecionar Arquivo de Backup</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="uploadForm">
                            <div class="upload-area" id="uploadArea">
                                <i class="fas fa-cloud-upload-alt fa-3x text-success mb-3"></i>
                                <h4 class="mb-3">Arraste o arquivo aqui ou clique para selecionar</h4>
                                <input type="file" name="backup_file" id="backup_file" accept=".json" class="d-none" required>
                                <p class="text-muted mb-3">Apenas arquivos JSON de backup são aceitos</p>
                                <button type="button" class="btn btn-outline-success" onclick="document.getElementById('backup_file').click()">
                                    <i class="fas fa-folder-open me-2"></i>Escolher Arquivo
                                </button>
                            </div>
                            
                            <div id="fileInfo" class="mt-4 d-none">
                                <div class="alert alert-info">
                                    <i class="fas fa-file-alt me-2"></i>
                                    Arquivo selecionado: <span id="fileName"></span>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-success btn-lg px-5" id="submitBtn" disabled>
                                    <i class="fas fa-upload me-2"></i>Restaurar Backup
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avisos Importantes -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Avisos Importantes</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-danger">⚠️ Atenção</h6>
                                <ul class="list-unstyled">
                                    <li><small>• Itens duplicados serão ignorados</small></li>
                                    <li><small>• Processo pode demorar alguns segundos</small></li>
                                    <li><small>• Mantenha conexão estável durante importação</small></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-info">ℹ️ Compatibilidade</h6>
                                <ul class="list-unstyled">
                                    <li><small>• Apenas backups do CapivaraLearn v1.1+</small></li>
                                    <li><small>• Arquivos devem estar em formato JSON</small></li>
                                    <li><small>• Estrutura será validada automaticamente</small></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('backup_file');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const submitBtn = document.getElementById('submitBtn');

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileInfo(files[0]);
            }
        });

        // File input change
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                updateFileInfo(e.target.files[0]);
            }
        });

        function updateFileInfo(file) {
            if (file.type === 'application/json' || file.name.endsWith('.json')) {
                fileName.textContent = file.name;
                fileInfo.classList.remove('d-none');
                submitBtn.disabled = false;
            } else {
                alert('Por favor, selecione apenas arquivos JSON.');
                fileInput.value = '';
                fileInfo.classList.add('d-none');
                submitBtn.disabled = true;
            }
        }
    </script>
</body>
</html>
