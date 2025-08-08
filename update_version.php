<?php
/**
 * Script para atualizar versão do CapivaraLearn
 * Uso: php update_version.php [patch|minor|major]
 */

$version_file = __DIR__ . '/includes/version.php';

if (!file_exists($version_file)) {
    die("❌ Arquivo version.php não encontrado!\n");
}

// Ler arquivo atual
$content = file_get_contents($version_file);

// Extrair versão atual
preg_match("/define\('APP_VERSION', '([^']+)'\)/", $content, $matches);
$current_version = $matches[1] ?? '0.0.0';

// Tipo de atualização (patch por padrão)
$update_type = $argv[1] ?? 'patch';

// Dividir versão em partes
list($major, $minor, $patch) = explode('.', $current_version);

// Incrementar baseado no tipo
switch ($update_type) {
    case 'major':
        $major++;
        $minor = 0;
        $patch = 0;
        break;
    case 'minor':
        $minor++;
        $patch = 0;
        break;
    default: // patch
        $patch++;
        break;
}

$new_version = "$major.$minor.$patch";

// Obter informações do Git
$commit_hash = trim(shell_exec('git rev-parse --short HEAD 2>/dev/null') ?? 'unknown');
$branch_name = trim(shell_exec('git rev-parse --abbrev-ref HEAD 2>/dev/null') ?? 'unknown');
$build_date = date('Y-m-d');

// Extrair build number atual e incrementar
preg_match("/define\('APP_BUILD_NUMBER', '([^']+)'\)/", $content, $build_matches);
$current_build = intval($build_matches[1] ?? 0);
$new_build = sprintf('%03d', $current_build + 1);

// Atualizar conteúdo
$content = preg_replace(
    "/define\('APP_VERSION', '[^']+'\)/",
    "define('APP_VERSION', '$new_version')",
    $content
);

$content = preg_replace(
    "/define\('APP_BUILD_DATE', '[^']+'\)/",
    "define('APP_BUILD_DATE', '$build_date')",
    $content
);

$content = preg_replace(
    "/define\('APP_BUILD_NUMBER', '[^']+'\)/",
    "define('APP_BUILD_NUMBER', '$new_build')",
    $content
);

$content = preg_replace(
    "/define\('APP_GITHUB_BRANCH', '[^']+'\)/",
    "define('APP_GITHUB_BRANCH', '$branch_name')",
    $content
);

$content = preg_replace(
    "/define\('APP_RELEASE_TAG', '[^']+'\)/",
    "define('APP_RELEASE_TAG', 'v$new_version-development')",
    $content
);

// Salvar arquivo
file_put_contents($version_file, $content);

echo "✅ Versão atualizada!\n";
echo "   Versão: $current_version → $new_version\n";
echo "   Build: " . sprintf('%03d', $current_build) . " → $new_build\n";
echo "   Data: $build_date\n";
echo "   Branch: $branch_name\n";
echo "   Commit: $commit_hash\n";
