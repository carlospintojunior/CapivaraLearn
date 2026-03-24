<?php
/**
 * CapivaraLearn - Consulta de Testes Especiais
 * Página de visualização com barra lateral de regiões e painel de detalhes + vídeo
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/Medoo.php';

use Medoo\Medoo;

requireLogin();

$database = new Medoo([
    'type' => 'mysql',
    'host' => DB_HOST,
    'database' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'charset' => 'utf8mb4'
]);

// Carregar versão
try {
    require_once __DIR__ . '/includes/version.php';
} catch (Exception $e) {}

$user_id = $_SESSION['user_id'];

// Buscar usuário para sidebar
$user = $database->get('usuarios', ['nome'], ['id' => $user_id]);

// Parâmetros de navegação
$categoria_id = intval($_GET['categoria'] ?? 0);
$regiao_id = intval($_GET['regiao'] ?? 0);
$teste_id = intval($_GET['teste'] ?? 0);

// Buscar categorias ativas
$categorias = $database->select('categorias_clinicas', '*', [
    'ativo' => 1,
    'ORDER' => ['ordem' => 'ASC', 'nome' => 'ASC']
]);

// Se nenhuma categoria selecionada, pegar a primeira
if ($categoria_id === 0 && !empty($categorias)) {
    $categoria_id = $categorias[0]['id'];
}

// Buscar regiões da categoria selecionada
$regioes = [];
if ($categoria_id > 0) {
    $regioes = $database->select('regioes_corporais', '*', [
        'categoria_id' => $categoria_id,
        'ativo' => 1,
        'ORDER' => ['ordem' => 'ASC', 'nome' => 'ASC']
    ]);
}

// Se nenhuma região selecionada, pegar a primeira
if ($regiao_id === 0 && !empty($regioes)) {
    $regiao_id = $regioes[0]['id'];
}

// Buscar testes da região selecionada
$testes = [];
if ($regiao_id > 0) {
    $testes = $database->select('testes_especiais', '*', [
        'regiao_id' => $regiao_id,
        'ativo' => 1,
        'ORDER' => ['ordem' => 'ASC', 'nome' => 'ASC']
    ]);
}

// Se nenhum teste selecionado, pegar o primeiro
if ($teste_id === 0 && !empty($testes)) {
    $teste_id = $testes[0]['id'];
}

// Buscar teste selecionado
$testeAtual = null;
if ($teste_id > 0) {
    $testeAtual = $database->get('testes_especiais', '*', ['id' => $teste_id, 'ativo' => 1]);
}

// Nome da categoria e região atuais
$categoriaAtual = $database->get('categorias_clinicas', ['nome', 'icone'], ['id' => $categoria_id]);
$regiaoAtual = $database->get('regioes_corporais', ['nome', 'descricao', 'icone'], ['id' => $regiao_id]);

function getClinicalMediaUrl(string $type, ?string $filename): ?string {
    if (empty($filename)) {
        return null;
    }

    $baseDir = __DIR__ . '/public/assets/' . $type . '/testes_especiais/';
    if (!is_file($baseDir . $filename)) {
        return null;
    }

    return appPath('public/assets/' . $type . '/testes_especiais/' . rawurlencode($filename));
}

$testeVideoUrl = $testeAtual ? getClinicalMediaUrl('videos', $testeAtual['video_filename'] ?? null) : null;
$testeImagemUrl = $testeAtual ? getClinicalMediaUrl('images', $testeAtual['imagem_filename'] ?? null) : null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testes Especiais - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; min-height: 100vh; }

        /* Top bar */
        .top-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 48px;
            background: #1a252f;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.2rem;
            z-index: 200;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .top-bar a { color: rgba(255,255,255,0.85); text-decoration: none; font-size: 0.9rem; }
        .top-bar a:hover { color: white; }
        .top-bar .brand { font-weight: 700; font-size: 1rem; }

        /* Layout principal */
        .app-layout { display: flex; min-height: 100vh; padding-top: 48px; }

        /* Sidebar esquerda - Navegação */
        .sidebar-nav {
            width: 280px;
            min-width: 280px;
            background: linear-gradient(180deg, #2c3e50, #3498db);
            color: white;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            position: fixed;
            top: 48px;
            left: 0;
            bottom: 0;
            z-index: 100;
        }

        .sidebar-header {
            padding: 1.2rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.15);
        }

        .sidebar-header h5 { margin-bottom: 0.3rem; font-weight: 700; }
        .sidebar-header small { opacity: 0.7; }

        .sidebar-section {
            padding: 0.6rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-section-title {
            padding: 0.4rem 1rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.5;
            font-weight: 600;
        }

        .sidebar-link {
            display: block;
            padding: 0.55rem 1rem;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            font-size: 0.88rem;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .sidebar-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: rgba(255,255,255,0.4);
        }

        .sidebar-link.active {
            background: rgba(255,255,255,0.18);
            color: white;
            font-weight: 600;
            border-left-color: #f39c12;
        }

        .sidebar-link i { width: 20px; text-align: center; margin-right: 8px; }

        .sidebar-back {
            padding: 0.8rem 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }

        .sidebar-back a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.85rem;
        }

        .sidebar-back a:hover { color: white; }

        /* Sidebar testes - painel intermediário */
        .sidebar-tests {
            width: 260px;
            min-width: 260px;
            background: #fff;
            border-right: 1px solid #dee2e6;
            overflow-y: auto;
            position: fixed;
            top: 48px;
            left: 280px;
            bottom: 0;
            z-index: 90;
        }

        .tests-header {
            padding: 1rem;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .tests-header h6 { margin: 0; color: #2c3e50; font-weight: 700; }
        .tests-header small { color: #6c757d; }

        .test-item {
            display: block;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f0f0f0;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
        }

        .test-item:hover { background: #f8f9fa; color: #2c3e50; }

        .test-item.active {
            background: #e8f4f8;
            border-left: 3px solid #3498db;
            color: #2c3e50;
            font-weight: 600;
        }

        .test-item .test-name { font-size: 0.9rem; display: block; }
        .test-item .test-alt { font-size: 0.75rem; color: #6c757d; }

        /* Conteúdo principal */
        .main-content {
            margin-left: 540px;
            flex: 1;
            padding: 2rem;
        }

        /* Detalhes do teste */
        .test-detail-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .test-detail-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 1.5rem 2rem;
        }

        .test-detail-header h2 { font-weight: 700; margin-bottom: 0.3rem; }
        .test-detail-header .alt-name { opacity: 0.8; font-size: 1rem; }

        .test-detail-body { padding: 2rem; }

        .detail-section {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .detail-section:last-child { border-bottom: none; margin-bottom: 0; }

        .detail-section h5 {
            color: #2c3e50;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 0.6rem;
        }

        .detail-section h5 i { color: #3498db; margin-right: 6px; width: 18px; }

        .detail-section p {
            color: #555;
            line-height: 1.7;
            margin: 0;
        }

        .positive-box {
            background: #fff3cd;
            border-left: 4px solid #f39c12;
            padding: 0.8rem 1rem;
            border-radius: 0 6px 6px 0;
            color: #856404;
        }

        .indication-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 0.8rem 1rem;
            border-radius: 0 6px 6px 0;
            color: #155724;
        }

        .video-container {
            border-radius: 10px;
            overflow: hidden;
            background: #000;
            margin-top: 1rem;
        }

        .video-container video {
            width: 100%;
            max-height: 420px;
            display: block;
        }

        .image-container {
            margin-top: 1rem;
            text-align: center;
        }

        .image-container img {
            max-width: 100%;
            max-height: 400px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Estado vazio */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #adb5bd;
        }

        .empty-state i { font-size: 4rem; margin-bottom: 1rem; }

        /* Responsivo - Mobile */
        @media (max-width: 992px) {
            .top-bar { padding: 0 0.8rem; }

            .app-layout {
                flex-direction: column;
                padding-top: 48px;
            }

            /* Sidebars viram offcanvas no mobile */
            .sidebar-nav {
                position: fixed;
                top: 48px;
                left: -300px;
                width: 280px;
                min-width: unset;
                transition: left 0.3s ease;
                z-index: 150;
                overflow-y: auto;
                flex-direction: column;
            }

            .sidebar-nav.mobile-open {
                left: 0;
            }

            .sidebar-tests {
                position: fixed;
                top: 48px;
                left: -300px;
                width: 280px;
                min-width: unset;
                transition: left 0.3s ease;
                z-index: 140;
                overflow-y: auto;
                max-height: unset;
                bottom: 0;
            }

            .sidebar-tests.mobile-open {
                left: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
                min-height: calc(100vh - 48px);
            }

            /* Overlay para fechar menus */
            .mobile-overlay {
                display: none;
                position: fixed;
                top: 48px;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.4);
                z-index: 130;
            }

            .mobile-overlay.active {
                display: block;
            }

            /* Barra de ações mobile */
            .mobile-actions {
                display: flex;
                gap: 0.5rem;
                padding: 0.6rem 1rem;
                background: #fff;
                border-bottom: 1px solid #dee2e6;
                overflow-x: auto;
                white-space: nowrap;
            }

            .mobile-actions .btn {
                font-size: 0.8rem;
                padding: 0.35rem 0.7rem;
                flex-shrink: 0;
            }

            /* Card de detalhe menor no mobile */
            .test-detail-header { padding: 1rem; }
            .test-detail-header h2 { font-size: 1.2rem; }
            .test-detail-body { padding: 1rem; }
            .detail-section { margin-bottom: 1rem; padding-bottom: 1rem; }
            .video-container video { max-height: 260px; }

            /* Nav buttons empilhados no mobile */
            .test-nav-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }
            .test-nav-buttons .btn {
                min-width: unset;
                font-size: 0.85rem;
            }

            /* Header sidebar escondido no mobile (já tem top-bar) */
            .sidebar-header { padding: 0.8rem; }
            .sidebar-header h5 { font-size: 1rem; }
            .sidebar-back { padding: 0.6rem 1rem; }
        }

        /* Esconder ações mobile no desktop */
        @media (min-width: 993px) {
            .mobile-actions { display: none !important; }
            .mobile-overlay { display: none !important; }
        }

        /* Contador de testes por região */
        .region-count {
            background: rgba(255,255,255,0.2);
            font-size: 0.7rem;
            padding: 0.15rem 0.5rem;
            border-radius: 10px;
            float: right;
        }

        /* Navegação entre testes */
        .test-nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
        }

        .test-nav-buttons .btn { min-width: 140px; }
    </style>
</head>
<body>
    <!-- Top bar com navegação -->
    <div class="top-bar">
        <a href="dashboard.php" title="Voltar ao Dashboard">
            <i class="fas fa-arrow-left me-2"></i>Dashboard
        </a>
        <span class="brand"><i class="fas fa-stethoscope me-2"></i>Testes Especiais</span>
        <?php if (($_SESSION['user_role'] ?? 'user') === 'admin'): ?>
        <a href="crud/clinical_tests_admin.php" title="Administrar Testes">
            <i class="fas fa-cog me-1"></i>Admin
        </a>
        <?php else: ?>
        <span></span>
        <?php endif; ?>
    </div>

    <div class="app-layout">
        <!-- SIDEBAR ESQUERDA: Regiões corporais -->
        <aside class="sidebar-nav">
            <div class="sidebar-header">
                <h5><i class="fas fa-stethoscope me-2"></i>Testes Especiais</h5>
                <small>Consulta Clínica</small>
            </div>

            <!-- Categorias -->
            <?php if (count($categorias) > 1): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Categorias</div>
                <?php foreach ($categorias as $cat): ?>
                    <a href="?categoria=<?= $cat['id'] ?>"
                       class="sidebar-link <?= $cat['id'] == $categoria_id ? 'active' : '' ?>">
                        <i class="fas <?= htmlspecialchars($cat['icone']) ?>"></i>
                        <?= htmlspecialchars($cat['nome']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Regiões da categoria selecionada -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">
                    <?= htmlspecialchars($categoriaAtual['nome'] ?? 'Regiões') ?>
                </div>
                <?php foreach ($regioes as $reg): ?>
                    <?php
                    $countTestes = $database->count('testes_especiais', [
                        'regiao_id' => $reg['id'],
                        'ativo' => 1
                    ]);
                    ?>
                    <a href="?categoria=<?= $categoria_id ?>&regiao=<?= $reg['id'] ?>"
                       class="sidebar-link <?= $reg['id'] == $regiao_id ? 'active' : '' ?>">
                        <i class="fas <?= htmlspecialchars($reg['icone'] ?: 'fa-bone') ?>"></i>
                        <?= htmlspecialchars($reg['nome']) ?>
                        <span class="region-count"><?= $countTestes ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="sidebar-back">
                <a href="dashboard.php"><i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard</a>
                <?php if (($_SESSION['user_role'] ?? 'user') === 'admin'): ?>
                <br>
                <a href="crud/clinical_tests_admin.php" class="mt-1 d-inline-block">
                    <i class="fas fa-cog me-2"></i>Administrar Testes
                </a>
                <?php endif; ?>
            </div>
        </aside>

        <!-- SIDEBAR TESTES: Lista de testes da região -->
        <aside class="sidebar-tests">
            <div class="tests-header">
                <h6><i class="fas <?= htmlspecialchars($regiaoAtual['icone'] ?? 'fa-bone') ?> me-2"></i><?= htmlspecialchars($regiaoAtual['nome'] ?? 'Selecione uma região') ?></h6>
                <?php if ($regiaoAtual && $regiaoAtual['descricao']): ?>
                    <small><?= htmlspecialchars($regiaoAtual['descricao']) ?></small>
                <?php endif; ?>
            </div>

            <?php if (empty($testes)): ?>
                <div class="p-3 text-center text-muted">
                    <i class="fas fa-info-circle"></i> Nenhum teste nesta região.
                </div>
            <?php else: ?>
                <?php foreach ($testes as $t): ?>
                    <a href="?categoria=<?= $categoria_id ?>&regiao=<?= $regiao_id ?>&teste=<?= $t['id'] ?>"
                       class="test-item <?= $t['id'] == $teste_id ? 'active' : '' ?>">
                        <span class="test-name"><?= htmlspecialchars($t['nome']) ?></span>
                        <?php if ($t['nome_alternativo']): ?>
                            <span class="test-alt">(<?= htmlspecialchars($t['nome_alternativo']) ?>)</span>
                        <?php endif; ?>
                        <?php if ($t['video_filename']): ?>
                            <span class="test-alt"><i class="fas fa-video text-success"></i> Vídeo disponível</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </aside>

        <!-- CONTEÚDO PRINCIPAL: Detalhes do teste -->
        <main class="main-content">
            <!-- Overlay mobile -->
            <div class="mobile-overlay" id="mobileOverlay" onclick="closeMobilePanels()"></div>

            <!-- Barra de ações mobile -->
            <div class="mobile-actions">
                <button class="btn btn-primary btn-sm" onclick="togglePanel('regions')">
                    <i class="fas fa-bone me-1"></i><?= htmlspecialchars($regiaoAtual['nome'] ?? 'Regiões') ?>
                </button>
                <button class="btn btn-outline-primary btn-sm" onclick="togglePanel('tests')">
                    <i class="fas fa-vial me-1"></i>Testes (<?= count($testes) ?>)
                </button>
                <?php if ($testeAtual): ?>
                <span class="btn btn-light btn-sm disabled" style="opacity:0.8;">
                    <i class="fas fa-stethoscope me-1"></i><?= htmlspecialchars($testeAtual['nome']) ?>
                </span>
                <?php endif; ?>
            </div>
            <?php if ($testeAtual): ?>
                <div class="test-detail-card">
                    <div class="test-detail-header">
                        <h2><?= htmlspecialchars($testeAtual['nome']) ?></h2>
                        <?php if ($testeAtual['nome_alternativo']): ?>
                            <span class="alt-name">(<?= htmlspecialchars($testeAtual['nome_alternativo']) ?>)</span>
                        <?php endif; ?>
                    </div>

                    <div class="test-detail-body">
                        <?php if ($testeAtual['descricao']): ?>
                        <div class="detail-section">
                            <h5><i class="fas fa-info-circle"></i>Descrição</h5>
                            <p><?= nl2br(htmlspecialchars($testeAtual['descricao'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($testeAtual['indicacao']): ?>
                        <div class="detail-section">
                            <h5><i class="fas fa-bullseye"></i>Indicação</h5>
                            <div class="indication-box">
                                <?= nl2br(htmlspecialchars($testeAtual['indicacao'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($testeAtual['tecnica']): ?>
                        <div class="detail-section">
                            <h5><i class="fas fa-hand-holding-medical"></i>Técnica de Execução</h5>
                            <p><?= nl2br(htmlspecialchars($testeAtual['tecnica'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($testeAtual['positivo_quando']): ?>
                        <div class="detail-section">
                            <h5><i class="fas fa-check-circle"></i>Positivo Quando</h5>
                            <div class="positive-box">
                                <?= nl2br(htmlspecialchars($testeAtual['positivo_quando'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($testeAtual['video_filename']): ?>
                        <div class="detail-section">
                            <h5><i class="fas fa-video"></i>Vídeo Demonstrativo</h5>
                            <?php if ($testeVideoUrl): ?>
                            <div class="video-container">
                                <video controls preload="metadata">
                                    <source src="<?= htmlspecialchars($testeVideoUrl, ENT_QUOTES, 'UTF-8') ?>" type="video/mp4">
                                    Seu navegador não suporta a reprodução de vídeo.
                                </video>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                O arquivo de vídeo deste teste não está disponível no servidor no momento.
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($testeAtual['imagem_filename']): ?>
                        <div class="detail-section">
                            <h5><i class="fas fa-image"></i>Imagem Ilustrativa</h5>
                            <?php if ($testeImagemUrl): ?>
                            <div class="image-container">
                                <img src="<?= htmlspecialchars($testeImagemUrl, ENT_QUOTES, 'UTF-8') ?>"
                                     alt="<?= htmlspecialchars($testeAtual['nome']) ?>">
                            </div>
                            <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                O arquivo de imagem deste teste não está disponível no servidor no momento.
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($testeAtual['referencias']): ?>
                        <div class="detail-section">
                            <h5><i class="fas fa-book"></i>Referências</h5>
                            <p><small class="text-muted"><?= nl2br(htmlspecialchars($testeAtual['referencias'])) ?></small></p>
                        </div>
                        <?php endif; ?>

                        <!-- Navegação entre testes -->
                        <?php
                        $currentIdx = -1;
                        foreach ($testes as $idx => $t) {
                            if ($t['id'] == $teste_id) { $currentIdx = $idx; break; }
                        }
                        $prevTeste = ($currentIdx > 0) ? $testes[$currentIdx - 1] : null;
                        $nextTeste = ($currentIdx < count($testes) - 1) ? $testes[$currentIdx + 1] : null;
                        ?>
                        <div class="test-nav-buttons">
                            <?php if ($prevTeste): ?>
                                <a href="?categoria=<?= $categoria_id ?>&regiao=<?= $regiao_id ?>&teste=<?= $prevTeste['id'] ?>"
                                   class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-1"></i><?= htmlspecialchars($prevTeste['nome']) ?>
                                </a>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>

                            <?php if ($nextTeste): ?>
                                <a href="?categoria=<?= $categoria_id ?>&regiao=<?= $regiao_id ?>&teste=<?= $nextTeste['id'] ?>"
                                   class="btn btn-outline-primary">
                                    <?= htmlspecialchars($nextTeste['nome']) ?><i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php elseif (empty($categorias)): ?>
                <div class="empty-state">
                    <i class="fas fa-database d-block"></i>
                    <h4>Nenhum teste cadastrado</h4>
                    <?php if (($_SESSION['user_role'] ?? 'user') === 'admin'): ?>
                    <p>Acesse a <a href="crud/clinical_tests_admin.php">administração</a> para cadastrar categorias, regiões e testes.</p>
                    <?php else: ?>
                    <p>Nenhum conteúdo disponível ainda.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-hand-pointer d-block"></i>
                    <h4>Selecione um teste</h4>
                    <p>Escolha uma região corporal e depois um teste na barra lateral.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePanel(panel) {
    const nav = document.querySelector('.sidebar-nav');
    const tests = document.querySelector('.sidebar-tests');
    const overlay = document.getElementById('mobileOverlay');

    // Fechar ambos primeiro
    nav.classList.remove('mobile-open');
    tests.classList.remove('mobile-open');

    if (panel === 'regions') {
        nav.classList.add('mobile-open');
    } else if (panel === 'tests') {
        tests.classList.add('mobile-open');
    }
    overlay.classList.add('active');
}

function closeMobilePanels() {
    document.querySelector('.sidebar-nav').classList.remove('mobile-open');
    document.querySelector('.sidebar-tests').classList.remove('mobile-open');
    document.getElementById('mobileOverlay').classList.remove('active');
}

// Fechar ao clicar em link da sidebar (mobile)
document.querySelectorAll('.sidebar-link, .test-item').forEach(function(el) {
    el.addEventListener('click', function() {
        if (window.innerWidth <= 992) {
            closeMobilePanels();
        }
    });
});
</script>
</body>
</html>
