<?php
/**
 * CapivaraLearn - Administração de Testes Especiais
 * CRUD para gerenciar categorias, regiões corporais e testes clínicos
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../Medoo.php';

use Medoo\Medoo;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /CapivaraLearn/login.php');
    exit;
}

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

// Diretório de vídeos
$videoDir = __DIR__ . '/../public/assets/videos/testes_especiais/';
if (!is_dir($videoDir)) {
    mkdir($videoDir, 0755, true);
}

// Diretório de imagens
$imageDir = __DIR__ . '/../public/assets/images/testes_especiais/';
if (!is_dir($imageDir)) {
    mkdir($imageDir, 0755, true);
}

// Abas: categorias, regioes, testes
$tab = $_GET['tab'] ?? 'testes';

// ===== PROCESSAR AÇÕES POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            // --- CATEGORIAS ---
            case 'create_categoria':
                $nome = trim($_POST['cat_nome'] ?? '');
                $descricao = trim($_POST['cat_descricao'] ?? '');
                $icone = trim($_POST['cat_icone'] ?? 'fa-stethoscope');
                $curso_alvo = trim($_POST['cat_curso_alvo'] ?? 'Fisioterapia');
                $ordem = intval($_POST['cat_ordem'] ?? 0);
                if ($nome === '') throw new Exception('Nome da categoria é obrigatório.');
                $database->insert('categorias_clinicas', [
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'icone' => $icone,
                    'curso_alvo' => $curso_alvo,
                    'ordem' => $ordem,
                    'ativo' => 1
                ]);
                $message = 'Categoria criada com sucesso!';
                $messageType = 'success';
                $tab = 'categorias';
                break;

            case 'update_categoria':
                $id = intval($_POST['cat_id'] ?? 0);
                $nome = trim($_POST['cat_nome'] ?? '');
                if ($nome === '' || $id <= 0) throw new Exception('Dados inválidos.');
                $database->update('categorias_clinicas', [
                    'nome' => $nome,
                    'descricao' => trim($_POST['cat_descricao'] ?? ''),
                    'icone' => trim($_POST['cat_icone'] ?? 'fa-stethoscope'),
                    'curso_alvo' => trim($_POST['cat_curso_alvo'] ?? ''),
                    'ordem' => intval($_POST['cat_ordem'] ?? 0)
                ], ['id' => $id]);
                $message = 'Categoria atualizada!';
                $messageType = 'success';
                $tab = 'categorias';
                break;

            case 'delete_categoria':
                $id = intval($_POST['cat_id'] ?? 0);
                if ($id <= 0) throw new Exception('ID inválido.');
                $database->delete('categorias_clinicas', ['id' => $id]);
                $message = 'Categoria excluída!';
                $messageType = 'success';
                $tab = 'categorias';
                break;

            // --- REGIÕES ---
            case 'create_regiao':
                $nome = trim($_POST['reg_nome'] ?? '');
                $categoria_id = intval($_POST['reg_categoria_id'] ?? 0);
                if ($nome === '' || $categoria_id <= 0) throw new Exception('Nome e categoria são obrigatórios.');
                $database->insert('regioes_corporais', [
                    'categoria_id' => $categoria_id,
                    'nome' => $nome,
                    'descricao' => trim($_POST['reg_descricao'] ?? ''),
                    'icone' => trim($_POST['reg_icone'] ?? 'fa-bone'),
                    'ordem' => intval($_POST['reg_ordem'] ?? 0),
                    'ativo' => 1
                ]);
                $message = 'Região criada com sucesso!';
                $messageType = 'success';
                $tab = 'regioes';
                break;

            case 'update_regiao':
                $id = intval($_POST['reg_id'] ?? 0);
                $nome = trim($_POST['reg_nome'] ?? '');
                $categoria_id = intval($_POST['reg_categoria_id'] ?? 0);
                if ($nome === '' || $id <= 0 || $categoria_id <= 0) throw new Exception('Dados inválidos.');
                $database->update('regioes_corporais', [
                    'categoria_id' => $categoria_id,
                    'nome' => $nome,
                    'descricao' => trim($_POST['reg_descricao'] ?? ''),
                    'icone' => trim($_POST['reg_icone'] ?? 'fa-bone'),
                    'ordem' => intval($_POST['reg_ordem'] ?? 0)
                ], ['id' => $id]);
                $message = 'Região atualizada!';
                $messageType = 'success';
                $tab = 'regioes';
                break;

            case 'delete_regiao':
                $id = intval($_POST['reg_id'] ?? 0);
                if ($id <= 0) throw new Exception('ID inválido.');
                $database->delete('regioes_corporais', ['id' => $id]);
                $message = 'Região excluída!';
                $messageType = 'success';
                $tab = 'regioes';
                break;

            // --- TESTES ---
            case 'create_teste':
                $nome = trim($_POST['teste_nome'] ?? '');
                $regiao_id = intval($_POST['teste_regiao_id'] ?? 0);
                if ($nome === '' || $regiao_id <= 0) throw new Exception('Nome e região são obrigatórios.');

                $videoFilename = null;
                if (!empty($_FILES['teste_video']['name']) && $_FILES['teste_video']['error'] === UPLOAD_ERR_OK) {
                    $videoFilename = handleVideoUpload($_FILES['teste_video'], $videoDir);
                }

                $imagemFilename = null;
                if (!empty($_FILES['teste_imagem']['name']) && $_FILES['teste_imagem']['error'] === UPLOAD_ERR_OK) {
                    $imagemFilename = handleImageUpload($_FILES['teste_imagem'], $imageDir);
                }

                $database->insert('testes_especiais', [
                    'regiao_id' => $regiao_id,
                    'nome' => $nome,
                    'nome_alternativo' => trim($_POST['teste_nome_alt'] ?? '') ?: null,
                    'descricao' => trim($_POST['teste_descricao'] ?? ''),
                    'tecnica' => trim($_POST['teste_tecnica'] ?? ''),
                    'indicacao' => trim($_POST['teste_indicacao'] ?? ''),
                    'positivo_quando' => trim($_POST['teste_positivo'] ?? ''),
                    'video_filename' => $videoFilename,
                    'imagem_filename' => $imagemFilename,
                    'referencias' => trim($_POST['teste_referencias'] ?? ''),
                    'ordem' => intval($_POST['teste_ordem'] ?? 0),
                    'ativo' => 1
                ]);
                $message = 'Teste criado com sucesso!';
                $messageType = 'success';
                $tab = 'testes';
                break;

            case 'update_teste':
                $id = intval($_POST['teste_id'] ?? 0);
                $nome = trim($_POST['teste_nome'] ?? '');
                $regiao_id = intval($_POST['teste_regiao_id'] ?? 0);
                if ($nome === '' || $id <= 0 || $regiao_id <= 0) throw new Exception('Dados inválidos.');

                $updateData = [
                    'regiao_id' => $regiao_id,
                    'nome' => $nome,
                    'nome_alternativo' => trim($_POST['teste_nome_alt'] ?? '') ?: null,
                    'descricao' => trim($_POST['teste_descricao'] ?? ''),
                    'tecnica' => trim($_POST['teste_tecnica'] ?? ''),
                    'indicacao' => trim($_POST['teste_indicacao'] ?? ''),
                    'positivo_quando' => trim($_POST['teste_positivo'] ?? ''),
                    'referencias' => trim($_POST['teste_referencias'] ?? ''),
                    'ordem' => intval($_POST['teste_ordem'] ?? 0)
                ];

                if (!empty($_FILES['teste_video']['name']) && $_FILES['teste_video']['error'] === UPLOAD_ERR_OK) {
                    $old = $database->get('testes_especiais', 'video_filename', ['id' => $id]);
                    if ($old && file_exists($videoDir . $old)) {
                        unlink($videoDir . $old);
                    }
                    $updateData['video_filename'] = handleVideoUpload($_FILES['teste_video'], $videoDir);
                }

                if (!empty($_FILES['teste_imagem']['name']) && $_FILES['teste_imagem']['error'] === UPLOAD_ERR_OK) {
                    $old = $database->get('testes_especiais', 'imagem_filename', ['id' => $id]);
                    if ($old && file_exists($imageDir . $old)) {
                        unlink($imageDir . $old);
                    }
                    $updateData['imagem_filename'] = handleImageUpload($_FILES['teste_imagem'], $imageDir);
                }

                $database->update('testes_especiais', $updateData, ['id' => $id]);
                $message = 'Teste atualizado!';
                $messageType = 'success';
                $tab = 'testes';
                break;

            case 'delete_teste':
                $id = intval($_POST['teste_id'] ?? 0);
                if ($id <= 0) throw new Exception('ID inválido.');
                $teste = $database->get('testes_especiais', ['video_filename', 'imagem_filename'], ['id' => $id]);
                if ($teste) {
                    if ($teste['video_filename'] && file_exists($videoDir . $teste['video_filename'])) {
                        unlink($videoDir . $teste['video_filename']);
                    }
                    if ($teste['imagem_filename'] && file_exists($imageDir . $teste['imagem_filename'])) {
                        unlink($imageDir . $teste['imagem_filename']);
                    }
                }
                $database->delete('testes_especiais', ['id' => $id]);
                $message = 'Teste excluído!';
                $messageType = 'success';
                $tab = 'testes';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// ===== FUNÇÕES DE UPLOAD =====
function handleVideoUpload($file, $dir) {
    $allowed = ['video/mp4', 'video/webm', 'video/ogg'];
    if (!in_array($file['type'], $allowed)) {
        throw new Exception('Formato de vídeo não suportado. Use MP4, WebM ou OGG.');
    }
    if ($file['size'] > 100 * 1024 * 1024) {
        throw new Exception('Vídeo muito grande. Máximo 100MB.');
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'video_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        throw new Exception('Erro ao salvar vídeo.');
    }
    return $filename;
}

function handleImageUpload($file, $dir) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed)) {
        throw new Exception('Formato de imagem não suportado. Use JPG, PNG, GIF ou WebP.');
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('Imagem muito grande. Máximo 10MB.');
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'img_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        throw new Exception('Erro ao salvar imagem.');
    }
    return $filename;
}

// ===== CARREGAR DADOS =====
$categorias = $database->select('categorias_clinicas', '*', ['ORDER' => ['ordem' => 'ASC', 'nome' => 'ASC']]);
$regioes = $database->select('regioes_corporais', [
    '[>]categorias_clinicas' => ['categoria_id' => 'id']
], [
    'regioes_corporais.id',
    'regioes_corporais.categoria_id',
    'regioes_corporais.nome',
    'regioes_corporais.descricao',
    'regioes_corporais.icone',
    'regioes_corporais.ordem',
    'regioes_corporais.ativo',
    'categorias_clinicas.nome(categoria_nome)'
], ['ORDER' => ['categorias_clinicas.ordem' => 'ASC', 'regioes_corporais.ordem' => 'ASC']]);

$testes = $database->select('testes_especiais', [
    '[>]regioes_corporais' => ['regiao_id' => 'id'],
    '[>]categorias_clinicas' => ['regioes_corporais.categoria_id' => 'id']
], [
    'testes_especiais.id',
    'testes_especiais.regiao_id',
    'testes_especiais.nome',
    'testes_especiais.nome_alternativo',
    'testes_especiais.descricao',
    'testes_especiais.video_filename',
    'testes_especiais.imagem_filename',
    'testes_especiais.ordem',
    'testes_especiais.ativo',
    'regioes_corporais.nome(regiao_nome)',
    'categorias_clinicas.nome(categoria_nome)'
], ['ORDER' => ['categorias_clinicas.ordem' => 'ASC', 'regioes_corporais.ordem' => 'ASC', 'testes_especiais.ordem' => 'ASC']]);

// Edição
$editCategoria = null;
$editRegiao = null;
$editTeste = null;

if (isset($_GET['edit_cat'])) {
    $editCategoria = $database->get('categorias_clinicas', '*', ['id' => intval($_GET['edit_cat'])]);
    $tab = 'categorias';
}
if (isset($_GET['edit_reg'])) {
    $editRegiao = $database->get('regioes_corporais', '*', ['id' => intval($_GET['edit_reg'])]);
    $tab = 'regioes';
}
if (isset($_GET['edit_teste'])) {
    $editTeste = $database->get('testes_especiais', '*', ['id' => intval($_GET['edit_teste'])]);
    $tab = 'testes';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Testes Especiais - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-header { border-radius: 10px 10px 0 0 !important; }
        .nav-tabs .nav-link.active { font-weight: 600; }
        .badge-video { font-size: 0.7rem; }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-stethoscope me-2"></i>Administração - Testes Especiais</h2>
                    <p class="text-muted mb-0">Gerencie categorias, regiões corporais e testes clínicos</p>
                </div>
                <div>
                    <a href="../clinical_tests.php" class="btn btn-success me-2" title="Ver página de consulta">
                        <i class="fas fa-eye me-1"></i>Visualizar
                    </a>
                    <a href="../dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Abas -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'testes' ? 'active' : '' ?>" href="?tab=testes">
                <i class="fas fa-vial me-1"></i>Testes (<?= count($testes) ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'regioes' ? 'active' : '' ?>" href="?tab=regioes">
                <i class="fas fa-bone me-1"></i>Regiões (<?= count($regioes) ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'categorias' ? 'active' : '' ?>" href="?tab=categorias">
                <i class="fas fa-folder me-1"></i>Categorias (<?= count($categorias) ?>)
            </a>
        </li>
    </ul>

    <!-- ===== ABA: TESTES ===== -->
    <?php if ($tab === 'testes'): ?>
    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-vial me-2"></i>Testes Especiais
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nome</th>
                                    <th>Região</th>
                                    <th>Mídia</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($testes)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">Nenhum teste cadastrado.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($testes as $t): ?>
                                        <tr>
                                            <td><?= $t['ordem'] ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($t['nome']) ?></strong>
                                                <?php if ($t['nome_alternativo']): ?>
                                                    <br><small class="text-muted">(<?= htmlspecialchars($t['nome_alternativo']) ?>)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-info"><?= htmlspecialchars($t['regiao_nome']) ?></span></td>
                                            <td>
                                                <?php if ($t['video_filename']): ?>
                                                    <span class="badge bg-success badge-video"><i class="fas fa-video"></i> Vídeo</span>
                                                <?php endif; ?>
                                                <?php if ($t['imagem_filename']): ?>
                                                    <span class="badge bg-warning badge-video"><i class="fas fa-image"></i> Img</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?tab=testes&edit_teste=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Excluir este teste?')">
                                                    <input type="hidden" name="action" value="delete_teste">
                                                    <input type="hidden" name="teste_id" value="<?= $t['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-<?= $editTeste ? 'warning' : 'success' ?> text-white">
                    <i class="fas fa-<?= $editTeste ? 'edit' : 'plus' ?> me-2"></i>
                    <?= $editTeste ? 'Editar Teste' : 'Novo Teste' ?>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="<?= $editTeste ? 'update_teste' : 'create_teste' ?>">
                        <?php if ($editTeste): ?>
                            <input type="hidden" name="teste_id" value="<?= $editTeste['id'] ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Região Corporal *</label>
                            <select name="teste_regiao_id" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($regioes as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= ($editTeste && $editTeste['regiao_id'] == $r['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['categoria_nome'] . ' → ' . $r['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nome do Teste *</label>
                            <input type="text" name="teste_nome" class="form-control" required
                                   value="<?= htmlspecialchars($editTeste['nome'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nome Alternativo</label>
                            <input type="text" name="teste_nome_alt" class="form-control"
                                   value="<?= htmlspecialchars($editTeste['nome_alternativo'] ?? '') ?>"
                                   placeholder="Ex: Lata Vazia, FABER...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea name="teste_descricao" class="form-control" rows="2"><?= htmlspecialchars($editTeste['descricao'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Técnica de Execução</label>
                            <textarea name="teste_tecnica" class="form-control" rows="3"
                                      placeholder="Como realizar o teste..."><?= htmlspecialchars($editTeste['tecnica'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Indicação</label>
                            <input type="text" name="teste_indicacao" class="form-control"
                                   value="<?= htmlspecialchars($editTeste['indicacao'] ?? '') ?>"
                                   placeholder="O que o teste avalia...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Positivo Quando</label>
                            <textarea name="teste_positivo" class="form-control" rows="2"
                                      placeholder="Critérios de positividade..."><?= htmlspecialchars($editTeste['positivo_quando'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Vídeo (MP4, WebM, OGG - máx 100MB)</label>
                            <input type="file" name="teste_video" class="form-control" accept="video/mp4,video/webm,video/ogg">
                            <?php if ($editTeste && $editTeste['video_filename']): ?>
                                <small class="text-success"><i class="fas fa-check"></i> Vídeo atual: <?= htmlspecialchars($editTeste['video_filename']) ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Imagem (JPG, PNG, GIF, WebP - máx 10MB)</label>
                            <input type="file" name="teste_imagem" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                            <?php if ($editTeste && $editTeste['imagem_filename']): ?>
                                <small class="text-success"><i class="fas fa-check"></i> Imagem atual: <?= htmlspecialchars($editTeste['imagem_filename']) ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Referências</label>
                            <textarea name="teste_referencias" class="form-control" rows="2"
                                      placeholder="Fontes bibliográficas..."><?= htmlspecialchars($editTeste['referencias'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ordem</label>
                            <input type="number" name="teste_ordem" class="form-control" min="0"
                                   value="<?= $editTeste['ordem'] ?? 0 ?>">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-<?= $editTeste ? 'warning' : 'success' ?>">
                                <i class="fas fa-save me-1"></i><?= $editTeste ? 'Atualizar' : 'Criar' ?> Teste
                            </button>
                            <?php if ($editTeste): ?>
                                <a href="?tab=testes" class="btn btn-outline-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== ABA: REGIÕES ===== -->
    <?php if ($tab === 'regioes'): ?>
    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-bone me-2"></i>Regiões Corporais
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Nome</th><th>Categoria</th><th>Testes</th><th>Ações</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($regioes)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma região cadastrada.</td></tr>
                            <?php else: ?>
                                <?php foreach ($regioes as $r): ?>
                                    <?php $qtd = $database->count('testes_especiais', ['regiao_id' => $r['id']]); ?>
                                    <tr>
                                        <td><?= $r['ordem'] ?></td>
                                        <td><i class="fas <?= htmlspecialchars($r['icone'] ?: 'fa-bone') ?> me-1"></i><?= htmlspecialchars($r['nome']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($r['categoria_nome']) ?></span></td>
                                        <td><span class="badge bg-primary"><?= $qtd ?></span></td>
                                        <td>
                                            <a href="?tab=regioes&edit_reg=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Excluir esta região e todos os testes associados?')">
                                                <input type="hidden" name="action" value="delete_regiao">
                                                <input type="hidden" name="reg_id" value="<?= $r['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-<?= $editRegiao ? 'warning' : 'success' ?> text-white">
                    <i class="fas fa-<?= $editRegiao ? 'edit' : 'plus' ?> me-2"></i>
                    <?= $editRegiao ? 'Editar Região' : 'Nova Região' ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?= $editRegiao ? 'update_regiao' : 'create_regiao' ?>">
                        <?php if ($editRegiao): ?>
                            <input type="hidden" name="reg_id" value="<?= $editRegiao['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Categoria *</label>
                            <select name="reg_categoria_id" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($editRegiao && $editRegiao['categoria_id'] == $c['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nome *</label>
                            <input type="text" name="reg_nome" class="form-control" required
                                   value="<?= htmlspecialchars($editRegiao['nome'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea name="reg_descricao" class="form-control" rows="2"><?= htmlspecialchars($editRegiao['descricao'] ?? '') ?></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Ícone FontAwesome</label>
                                <input type="text" name="reg_icone" class="form-control"
                                       value="<?= htmlspecialchars($editRegiao['icone'] ?? 'fa-bone') ?>" placeholder="fa-bone">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Ordem</label>
                                <input type="number" name="reg_ordem" class="form-control" min="0"
                                       value="<?= $editRegiao['ordem'] ?? 0 ?>">
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-<?= $editRegiao ? 'warning' : 'success' ?>">
                                <i class="fas fa-save me-1"></i><?= $editRegiao ? 'Atualizar' : 'Criar' ?>
                            </button>
                            <?php if ($editRegiao): ?>
                                <a href="?tab=regioes" class="btn btn-outline-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== ABA: CATEGORIAS ===== -->
    <?php if ($tab === 'categorias'): ?>
    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-folder me-2"></i>Categorias Clínicas
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Nome</th><th>Curso</th><th>Regiões</th><th>Ações</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categorias)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma categoria cadastrada.</td></tr>
                            <?php else: ?>
                                <?php foreach ($categorias as $c): ?>
                                    <?php $qtdReg = $database->count('regioes_corporais', ['categoria_id' => $c['id']]); ?>
                                    <tr>
                                        <td><?= $c['ordem'] ?></td>
                                        <td><i class="fas <?= htmlspecialchars($c['icone']) ?> me-1"></i><?= htmlspecialchars($c['nome']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($c['curso_alvo']) ?></span></td>
                                        <td><span class="badge bg-info"><?= $qtdReg ?></span></td>
                                        <td>
                                            <a href="?tab=categorias&edit_cat=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Excluir esta categoria e TODOS os dados associados?')">
                                                <input type="hidden" name="action" value="delete_categoria">
                                                <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-<?= $editCategoria ? 'warning' : 'success' ?> text-white">
                    <i class="fas fa-<?= $editCategoria ? 'edit' : 'plus' ?> me-2"></i>
                    <?= $editCategoria ? 'Editar Categoria' : 'Nova Categoria' ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?= $editCategoria ? 'update_categoria' : 'create_categoria' ?>">
                        <?php if ($editCategoria): ?>
                            <input type="hidden" name="cat_id" value="<?= $editCategoria['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Nome *</label>
                            <input type="text" name="cat_nome" class="form-control" required
                                   value="<?= htmlspecialchars($editCategoria['nome'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea name="cat_descricao" class="form-control" rows="2"><?= htmlspecialchars($editCategoria['descricao'] ?? '') ?></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4">
                                <label class="form-label">Ícone</label>
                                <input type="text" name="cat_icone" class="form-control"
                                       value="<?= htmlspecialchars($editCategoria['icone'] ?? 'fa-stethoscope') ?>">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Curso Alvo</label>
                                <input type="text" name="cat_curso_alvo" class="form-control"
                                       value="<?= htmlspecialchars($editCategoria['curso_alvo'] ?? 'Fisioterapia') ?>">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Ordem</label>
                                <input type="number" name="cat_ordem" class="form-control" min="0"
                                       value="<?= $editCategoria['ordem'] ?? 0 ?>">
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-<?= $editCategoria ? 'warning' : 'success' ?>">
                                <i class="fas fa-save me-1"></i><?= $editCategoria ? 'Atualizar' : 'Criar' ?>
                            </button>
                            <?php if ($editCategoria): ?>
                                <a href="?tab=categorias" class="btn btn-outline-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(el) {
            new bootstrap.Alert(el).close();
        });
    }, 5000);
</script>
</body>
</html>
