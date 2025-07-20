<?php
/**
 * CapivaraLearn - Changelog / Histórico de Versões
 */

require_once __DIR__ . '/includes/config.php';

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar login
if (!isset($_SESSION['user_id'])) {
    header('Location: /CapivaraLearn/login.php');
    exit;
}

// Ler conteúdo do CHANGELOG.md
$changelog_content = '';
$changelog_file = __DIR__ . '/CHANGELOG.md';

if (file_exists($changelog_file)) {
    $changelog_content = file_get_contents($changelog_file);
}

// Função para converter Markdown básico para HTML
function markdownToHtml($text) {
    // Headers
    $text = preg_replace('/^# (.*$)/m', '<h1 class="text-primary mb-3">$1</h1>', $text);
    $text = preg_replace('/^## (.*$)/m', '<h2 class="text-success mt-4 mb-3">$1</h2>', $text);
    $text = preg_replace('/^### (.*$)/m', '<h3 class="text-info mt-3 mb-2">$1</h3>', $text);
    
    // Bold
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    
    // Emojis para badges
    $text = str_replace('✅', '<span class="badge bg-success me-1">✅</span>', $text);
    $text = str_replace('🔧', '<span class="badge bg-warning me-1">🔧</span>', $text);
    $text = str_replace('📝', '<span class="badge bg-info me-1">📝</span>', $text);
    $text = str_replace('🗑️', '<span class="badge bg-danger me-1">🗑️</span>', $text);
    $text = str_replace('🔒', '<span class="badge bg-dark me-1">🔒</span>', $text);
    $text = str_replace('⚡', '<span class="badge bg-warning me-1">⚡</span>', $text);
    
    // Links
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" class="text-decoration-none">$1</a>', $text);
    
    // Code blocks
    $text = preg_replace('/```([^`]+)```/s', '<pre class="bg-light p-3 rounded"><code>$1</code></pre>', $text);
    $text = preg_replace('/`([^`]+)`/', '<code class="bg-light px-1 rounded">$1</code>', $text);
    
    // Lists
    $text = preg_replace('/^- (.*)$/m', '<li class="mb-1">$1</li>', $text);
    
    // Quebras de linha
    $text = nl2br($text);
    
    return $text;
}

$changelog_html = markdownToHtml($changelog_content);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changelog - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .changelog-container {
            max-width: 900px;
        }
        .version-card {
            border-left: 4px solid #28a745;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }
        .version-card.latest {
            border-left-color: #007bff;
            background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%);
        }
        code {
            font-size: 0.9em;
        }
        pre {
            font-size: 0.85em;
        }
        .badge {
            font-size: 0.8em;
        }
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container changelog-container mt-4 mb-5">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-body text-center bg-primary text-white">
                        <h1 class="display-6 mb-3">
                            <i class="fas fa-history me-3"></i>
                            CapivaraLearn Changelog
                        </h1>
                        <p class="lead mb-3">Histórico de versões e atualizações do sistema</p>
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="border-end">
                                    <h4><?php echo APP_VERSION ?? '1.1.0'; ?></h4>
                                    <small>Versão Atual</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border-end">
                                    <h4><?php echo APP_BUILD_DATE ?? '2025-07-19'; ?></h4>
                                    <small>Última Atualização</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border-end">
                                    <h4>Build <?php echo APP_BUILD_NUMBER ?? '002'; ?></h4>
                                    <small>Número do Build</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <h4>
                                    <a href="<?php echo APP_GITHUB_URL ?? 'https://github.com/carlospintojunior/CapivaraLearn'; ?>" 
                                       target="_blank" class="text-white text-decoration-none">
                                        <i class="fab fa-github"></i>
                                    </a>
                                </h4>
                                <small>Repositório</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="row mb-4">
            <div class="col">
                <ul class="nav nav-tabs" id="changelogTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="changelog-tab" data-bs-toggle="tab" data-bs-target="#changelog" type="button" role="tab">
                            <i class="fas fa-list me-2"></i>Changelog Completo
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button" role="tab">
                            <i class="fas fa-chart-line me-2"></i>Resumo de Versões
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Content -->
        <div class="tab-content" id="changelogTabsContent">
            <!-- Changelog Tab -->
            <div class="tab-pane fade show active" id="changelog" role="tabpanel">
                <div class="card shadow-sm version-card latest">
                    <div class="card-body">
                        <?php if ($changelog_content): ?>
                            <?= $changelog_html ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Arquivo CHANGELOG.md não encontrado.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Summary Tab -->
            <div class="tab-pane fade" id="summary" role="tabpanel">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Versão 1.1.0</h5>
                                <small>19 de Julho de 2025</small>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li><span class="badge bg-success me-2">✅</span>Sistema de Backup/Importação</li>
                                    <li><span class="badge bg-warning me-2">🔧</span>Botões Cancelar em CRUDs</li>
                                    <li><span class="badge bg-warning me-2">🔧</span>Campo semestre oculto</li>
                                    <li><span class="badge bg-warning me-2">🔧</span>Correções visuais</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-star me-2"></i>Versão 1.0.0</h5>
                                <small>19 de Julho de 2025</small>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li><span class="badge bg-success me-2">✅</span>Sistema de versionamento</li>
                                    <li><span class="badge bg-success me-2">✅</span>Configuração de fuso horário</li>
                                    <li><span class="badge bg-success me-2">✅</span>Sistema de logs</li>
                                    <li><span class="badge bg-warning me-2">🔧</span>Correções críticas</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Estatísticas do Projeto</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="bg-light p-3 rounded">
                                    <h3 class="text-primary">2</h3>
                                    <small class="text-muted">Versões Lançadas</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="bg-light p-3 rounded">
                                    <h3 class="text-success">15+</h3>
                                    <small class="text-muted">Funcionalidades</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="bg-light p-3 rounded">
                                    <h3 class="text-warning">10+</h3>
                                    <small class="text-muted">Correções</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="bg-light p-3 rounded">
                                    <h3 class="text-info">8</h3>
                                    <small class="text-muted">Tabelas DB</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fab fa-github me-2"></i>GitHub & Desenvolvimento</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <h6><i class="fas fa-code-branch me-2 text-info"></i>Branch Atual</h6>
                                <p class="mb-2">
                                    <code><?php echo APP_GITHUB_BRANCH ?? 'main'; ?></code>
                                </p>
                                <small class="text-muted">Branch de desenvolvimento ativo</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h6><i class="fas fa-tag me-2 text-success"></i>Release Tag</h6>
                                <p class="mb-2">
                                    <code><?php echo APP_RELEASE_TAG ?? 'v1.1.0'; ?></code>
                                </p>
                                <small class="text-muted">Tag da versão atual</small>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4 text-center mb-2">
                                <a href="<?php echo APP_GITHUB_URL ?? 'https://github.com/carlospintojunior/CapivaraLearn'; ?>" 
                                   target="_blank" class="btn btn-outline-dark">
                                    <i class="fab fa-github me-2"></i>Repositório
                                </a>
                            </div>
                            <div class="col-md-4 text-center mb-2">
                                <a href="<?php echo (APP_GITHUB_URL ?? 'https://github.com/carlospintojunior/CapivaraLearn') . '/issues'; ?>" 
                                   target="_blank" class="btn btn-outline-warning">
                                    <i class="fas fa-bug me-2"></i>Issues
                                </a>
                            </div>
                            <div class="col-md-4 text-center mb-2">
                                <a href="<?php echo (APP_GITHUB_URL ?? 'https://github.com/carlospintojunior/CapivaraLearn') . '/releases'; ?>" 
                                   target="_blank" class="btn btn-outline-success">
                                    <i class="fas fa-download me-2"></i>Releases
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="row mt-5">
            <div class="col text-center">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Voltar ao Dashboard
                </a>
                <a href="backup_grade.php" class="btn btn-outline-success ms-2">
                    <i class="fas fa-download me-2"></i>
                    Backup Grade
                </a>
                <a href="import_grade.php" class="btn btn-outline-info ms-2">
                    <i class="fas fa-upload me-2"></i>
                    Importar Grade
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
