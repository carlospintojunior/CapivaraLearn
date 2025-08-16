<?php
/**
 * CapivaraLearn - Sistema de Importação de Grade Curricular
 * 
 * Importa estrutura completa de curso de arquivos JSON
 * Inclui: Curso → Disciplinas → Tópicos → Unidades de Aprendizagem
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/Medoo.php';

use Medoo\Medoo;

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar login
if (!isset($_SESSION['user_id'])) {
    header('Location: /CapivaraLearn/login.php');
    exit;
}

// Configurar Medoo
$database = new Medoo([
    'type' => 'mysql',
    'host' => DB_HOST,
    'database' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'charset' => 'utf8mb4'
]);

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Processar importação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import') {
    try {
        // Verificar se arquivo foi enviado
        if (!isset($_FILES['grade_file']) || $_FILES['grade_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload do arquivo. Verifique se o arquivo foi selecionado corretamente.');
        }
        
        $file = $_FILES['grade_file'];
        
        // Verificar tipo do arquivo
        if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
            throw new Exception('Apenas arquivos JSON são aceitos.');
        }
        
        // Verificar tamanho (máximo 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('Arquivo muito grande. Máximo permitido: 10MB.');
        }
        
        // Ler conteúdo do arquivo
        $json_content = file_get_contents($file['tmp_name']);
        if ($json_content === false) {
            throw new Exception('Erro ao ler o arquivo enviado.');
        }
        
        // Decodificar JSON
        $grade_data = json_decode($json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Arquivo JSON inválido: ' . json_last_error_msg());
        }
        
        // Validar estrutura do arquivo
        if (!isset($grade_data['metadata']['tipo']) || $grade_data['metadata']['tipo'] !== 'grade_curricular_capivaralearn') {
            throw new Exception('Arquivo não é uma grade curricular válida do CapivaraLearn.');
        }
        
        // Verificar campos obrigatórios
        if (!isset($grade_data['universidade'], $grade_data['curso'], $grade_data['disciplinas'])) {
            throw new Exception('Estrutura do arquivo é inválida. Faltam dados essenciais.');
        }
        
        // Iniciar transação
        $database->pdo->beginTransaction();
        
        // 1. Criar ou encontrar universidade
        $univ_data = $grade_data['universidade'];
        $universidade_id = $database->get('universidades', 'id', [
            'nome' => $univ_data['nome'],
            'usuario_id' => $user_id
        ]);
        
        if (!$universidade_id) {
            $database->insert('universidades', [
                'nome' => $univ_data['nome'],
                'sigla' => $univ_data['sigla'] ?? '',
                'pais' => $univ_data['pais'] ?? 'Brasil',
                'cidade' => $univ_data['cidade'] ?? '',
                'estado' => $univ_data['estado'] ?? '',
                'site' => $univ_data['site'] ?? '',
                'usuario_id' => $user_id
            ]);
            $universidade_id = $database->id();
        }
        
        // 2. Criar curso
        $curso_data = $grade_data['curso'];
        $nome_curso_original = $curso_data['nome'];
        $nome_curso = $nome_curso_original;
        $counter = 1;
        
        // Verificar se já existe curso com este nome
        while ($database->has('cursos', ['nome' => $nome_curso, 'usuario_id' => $user_id])) {
            $nome_curso = $nome_curso_original . ' (Importado ' . $counter . ')';
            $counter++;
        }
        
        $database->insert('cursos', [
            'nome' => $nome_curso,
            'descricao' => $curso_data['descricao'] ?? '',
            'nivel' => $curso_data['nivel'] ?? '',
            'carga_horaria' => $curso_data['carga_horaria'] ?? 0,
            'universidade_id' => $universidade_id,
            'usuario_id' => $user_id
        ]);
        $curso_id = $database->id();
        
        // 3. Criar disciplinas, tópicos e unidades
        $stats = ['disciplinas' => 0, 'topicos' => 0, 'unidades' => 0, 'disciplinas_existentes' => 0, 'topicos_existentes' => 0, 'unidades_existentes' => 0];
        
        foreach ($grade_data['disciplinas'] as $disc_data) {
            // Verificar se disciplina já existe
            $disciplina_id = $database->get('disciplinas', 'id', [
                'nome' => $disc_data['nome'],
                'curso_id' => $curso_id,
                'usuario_id' => $user_id
            ]);
            
            if (!$disciplina_id) {
                // Criar disciplina se não existir
                $database->insert('disciplinas', [
                    'nome' => $disc_data['nome'],
                    'codigo' => $disc_data['codigo'] ?? '',
                    'descricao' => $disc_data['descricao'] ?? '',
                    'carga_horaria' => $disc_data['carga_horaria'] ?? 0,
                    'semestre' => $disc_data['semestre'] ?? 0,
                    'concluido' => 0, // Sempre iniciar como não concluído
                    'curso_id' => $curso_id,
                    'usuario_id' => $user_id
                ]);
                $disciplina_id = $database->id();
                $stats['disciplinas']++;
            } else {
                $stats['disciplinas_existentes']++;
            }
            
            // Criar tópicos da disciplina
            if (isset($disc_data['topicos']) && is_array($disc_data['topicos'])) {
                foreach ($disc_data['topicos'] as $topico_data) {
                    // Verificar se tópico já existe
                    $topico_id = $database->get('topicos', 'id', [
                        'nome' => $topico_data['nome'],
                        'disciplina_id' => $disciplina_id,
                        'usuario_id' => $user_id
                    ]);
                    
                    if (!$topico_id) {
                        // Criar tópico se não existir
                        $database->insert('topicos', [
                            'nome' => $topico_data['nome'],
                            'descricao' => $topico_data['descricao'] ?? '',
                            'data_prazo' => $topico_data['data_prazo'] ?? null,
                            'prioridade' => $topico_data['prioridade'] ?? 'media',
                            'concluido' => 0, // Sempre iniciar como não concluído
                            'disciplina_id' => $disciplina_id,
                            'ordem' => $topico_data['ordem'] ?? 0,
                            'usuario_id' => $user_id
                        ]);
                        $topico_id = $database->id();
                        $stats['topicos']++;
                    } else {
                        $stats['topicos_existentes']++;
                    }
                    
                    // Criar unidades de aprendizagem do tópico
                    if (isset($topico_data['unidades_aprendizagem']) && is_array($topico_data['unidades_aprendizagem'])) {
                        foreach ($topico_data['unidades_aprendizagem'] as $unidade_data) {
                            // Verificar se unidade já existe
                            $unidade_existente = $database->get('unidades_aprendizagem', 'id', [
                                'nome' => $unidade_data['nome'],
                                'topico_id' => $topico_id,
                                'usuario_id' => $user_id
                            ]);
                            
                            if (!$unidade_existente) {
                                // Criar unidade se não existir
                                $database->insert('unidades_aprendizagem', [
                                    'nome' => $unidade_data['nome'],
                                    'descricao' => $unidade_data['descricao'] ?? '',
                                    'tipo' => $unidade_data['tipo'] ?? 'leitura',
                                    'nota' => null, // Não importar notas
                                    'data_prazo' => $unidade_data['data_prazo'] ?? null,
                                    'concluido' => 0, // Sempre iniciar como não concluído
                                    'topico_id' => $topico_id,
                                    'usuario_id' => $user_id
                                ]);
                                $stats['unidades']++;
                            } else {
                                $stats['unidades_existentes']++;
                            }
                        }
                    }
                }
            }
        }
        
        // Confirmar transação
        $database->pdo->commit();
        
        $message = "Grade curricular importada com sucesso!<br>";
        $message .= "<strong>Curso:</strong> " . htmlspecialchars($nome_curso) . "<br>";
        $message .= "<strong>Itens criados:</strong> " . $stats['disciplinas'] . " disciplinas, " . 
                   $stats['topicos'] . " tópicos, " . $stats['unidades'] . " unidades<br>";
        
        // Mostrar itens existentes que foram pulados, se houver
        if ($stats['disciplinas_existentes'] > 0 || $stats['topicos_existentes'] > 0 || $stats['unidades_existentes'] > 0) {
            $message .= "<strong>Itens já existentes (pulados):</strong> " . 
                       $stats['disciplinas_existentes'] . " disciplinas, " . 
                       $stats['topicos_existentes'] . " tópicos, " . 
                       $stats['unidades_existentes'] . " unidades<br>";
        }
        
        $message .= "<strong>Exportado originalmente em:</strong> " . htmlspecialchars($grade_data['metadata']['data_exportacao'] ?? 'Data não informada');
        $messageType = 'success';
        
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        if ($database->pdo->inTransaction()) {
            $database->pdo->rollBack();
        }
        
        $message = 'Erro na importação: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Grade Curricular - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-upload me-2"></i>
                            Importar Grade Curricular
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?>">
                                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                                <?= $message ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <h5><i class="fas fa-info-circle me-2"></i>Como funciona a importação?</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <i class="fas fa-file-upload me-2 text-primary"></i>
                                    Selecione um arquivo JSON gerado pelo sistema de backup
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-university me-2 text-info"></i>
                                    A universidade será criada ou localizada automaticamente
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-graduation-cap me-2 text-success"></i>
                                    Um novo curso será criado com toda a estrutura
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-refresh me-2 text-warning"></i>
                                    Todos os itens serão marcados como "não concluídos"
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-shield-alt me-2 text-danger"></i>
                                    Notas pessoais não são importadas (apenas estrutura)
                                </li>
                            </ul>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="import">
                            
                            <div class="mb-3">
                                <label for="grade_file" class="form-label">
                                    <strong>Selecione o arquivo de grade curricular:</strong>
                                </label>
                                <input type="file" class="form-control" id="grade_file" name="grade_file" 
                                       accept=".json" required>
                                <div class="form-text">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Apenas arquivos JSON gerados pelo sistema de backup são aceitos.
                                    Tamanho máximo: 10MB.
                                </div>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Atenção:</strong> A importação criará um novo curso com toda a estrutura.
                                Certifique-se de que o arquivo é de uma fonte confiável.
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-upload me-2"></i>
                                    Importar Grade Curricular
                                </button>
                                <a href="backup_grade.php" class="btn btn-outline-primary">
                                    <i class="fas fa-download me-2"></i>
                                    Ir para Backup
                                </a>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Voltar ao Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-tools me-2"></i>
                            Ferramentas de Manutenção
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-primary">
                                    <div class="card-body text-center">
                                        <i class="fas fa-download fa-2x text-primary mb-2"></i>
                                        <h6>Backup Grade</h6>
                                        <p class="text-muted small">Exportar estrutura curricular</p>
                                        <a href="backup_grade.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-download me-1"></i>Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-info">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-shield fa-2x text-info mb-2"></i>
                                        <h6>Backup Completo</h6>
                                        <p class="text-muted small">Backup de todos os dados</p>
                                        <a href="backup_user_data.php" class="btn btn-info btn-sm">
                                            <i class="fas fa-download me-1"></i>Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-warning">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-cog fa-2x text-warning mb-2"></i>
                                        <h6>Restaurar Dados</h6>
                                        <p class="text-muted small">Restaurar backup completo</p>
                                        <a href="restore_user_data.php" class="btn btn-warning btn-sm">
                                            <i class="fas fa-upload me-1"></i>Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-secondary">
                                    <div class="card-body text-center">
                                        <i class="fas fa-history fa-2x text-secondary mb-2"></i>
                                        <h6>Changelog</h6>
                                        <p class="text-muted small">Histórico de versões</p>
                                        <a href="changelog.php" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-history me-1"></i>Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
