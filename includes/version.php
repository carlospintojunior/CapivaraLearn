<?php
/**
 * CapivaraLearn - Sistema de Versionamento Dinâmico
 * 
 * Define a versão atual da aplicação e informações relacionadas.
 * Seguindo o padrão Semantic Versioning (semver.org)
 * 
 * MAJOR.MINOR.PATCH
 * - MAJOR: mudanças incompatíveis na API
 * - MINOR: novas funcionalidades compatíveis
 * - PATCH: correções de bugs
 */

/**
 * Função auxiliar para executar comandos Git de forma segura
 */
function executeGitCommand($command) {
    $currentDir = getcwd();
    $projectDir = dirname(__DIR__);
    chdir($projectDir);
    
    $result = @shell_exec($command . ' 2>/dev/null');
    chdir($currentDir);
    
    return $result ? trim($result) : null;
}

// Obter informações dinâmicas do Git
$git_branch = executeGitCommand('git rev-parse --abbrev-ref HEAD') ?: 'unknown';
$git_commit = executeGitCommand('git rev-parse --short HEAD') ?: 'unknown';
$git_tag = executeGitCommand('git describe --tags --exact-match HEAD 2>/dev/null');
$git_last_tag = executeGitCommand('git describe --tags --abbrev=0 2>/dev/null') ?: 'v0.8.0';
$git_commit_count = executeGitCommand('git rev-list --count HEAD') ?: '1';
$git_commit_date = executeGitCommand('git log -1 --format=%ci') ?: date('Y-m-d H:i:s');

// Determinar versão baseada nas tags Git
$version = $git_last_tag;
if (!$git_tag) {
    // Se não estamos numa tag exata, é uma versão de desenvolvimento
    $version .= '-dev.' . $git_commit;
}

// Determinar ambiente baseado na branch
$environment = 'development';
if ($git_branch === 'main' || $git_branch === 'master') {
    $environment = $git_tag ? 'production' : 'staging';
} elseif (strpos($git_branch, 'release/') === 0) {
    $environment = 'staging';
}

// Informações da versão atual (agora dinâmicas)
define('APP_VERSION', ltrim($version, 'v'));
define('APP_NAME', 'CapivaraLearn');
define('APP_BUILD_DATE', date('Y-m-d', strtotime($git_commit_date)));
define('APP_BUILD_NUMBER', $git_commit_count);
define('APP_ENVIRONMENT', $environment);
define('APP_GITHUB_URL', 'https://github.com/carlospintojunior/CapivaraLearn');
define('APP_GITHUB_BRANCH', $git_branch);
define('APP_GIT_COMMIT', $git_commit);
define('APP_RELEASE_TAG', $git_tag ?: ($version . '-' . $environment));

/**
 * Classe para gerenciar versões da aplicação
 */
class AppVersion {
    
    /**
     * Retorna a versão completa da aplicação
     */
    public static function getFull() {
        return APP_NAME . ' v' . APP_VERSION . ' (Build ' . APP_BUILD_NUMBER . ')';
    }
    
    /**
     * Retorna apenas o número da versão
     */
    public static function getVersion() {
        return APP_VERSION;
    }
    
    /**
     * Retorna informações completas para exibição
     */
    public static function getInfo() {
        return [
            'name' => APP_NAME,
            'version' => APP_VERSION,
            'build' => APP_BUILD_NUMBER,
            'date' => APP_BUILD_DATE,
            'environment' => APP_ENVIRONMENT,
            'github_url' => APP_GITHUB_URL ?? '',
            'github_branch' => APP_GITHUB_BRANCH ?? '',
            'release_tag' => APP_RELEASE_TAG ?? '',
            'full' => self::getFull()
        ];
    }
    
    /**
     * Retorna string formatada para footer
     */
    public static function getFooterText() {
        $github_info = '';
        if (defined('APP_GITHUB_BRANCH') && APP_GITHUB_BRANCH) {
            $branch_name = str_replace('#', '', APP_GITHUB_BRANCH);
            $branch_name = str_replace('-', ' ', $branch_name);
            $github_info = ' • ' . $branch_name;
        }
        return APP_NAME . ' v' . APP_VERSION . ' • Build ' . APP_BUILD_NUMBER . $github_info;
    }
    
    /**
     * Retorna string formatada para sidebar
     */
    public static function getSidebarText() {
        $version = 'v' . APP_VERSION;
        
        // Ambiente (apenas se não for produção)
        $env = (APP_ENVIRONMENT !== 'production') ? ' (' . strtoupper(APP_ENVIRONMENT) . ')' : '';
        
        // Branch (formatado e encurtado)
        $branch = '';
        if (defined('APP_GITHUB_BRANCH') && APP_GITHUB_BRANCH && APP_GITHUB_BRANCH !== 'main' && APP_GITHUB_BRANCH !== 'master') {
            $branch_name = APP_GITHUB_BRANCH;
            
            // Limpar e formatar nome da branch
            $branch_name = preg_replace('/^#\d+---/', '', $branch_name); // Remove #123---
            $branch_name = str_replace(['-', '_'], ' ', $branch_name); // Substitui - e _ por espaços
            $branch_name = ucwords(strtolower($branch_name)); // Primeira letra maiúscula, resto minúscula
            
            // Encurtar se muito longo (considerando caracteres UTF-8)
            if (mb_strlen($branch_name, 'UTF-8') > 18) {
                $branch_name = mb_substr($branch_name, 0, 15, 'UTF-8') . '...';
            }
            
            $branch = '<br><span style="font-size: 0.65rem; opacity: 0.8;">' . htmlspecialchars($branch_name, ENT_QUOTES, 'UTF-8') . '</span>';
        }
        
        // Commit hash (apenas em desenvolvimento)
        $commit = '';
        if (APP_ENVIRONMENT === 'development' && defined('APP_GIT_COMMIT') && APP_GIT_COMMIT !== 'unknown') {
            $commit = '<br><span style="font-size: 0.6rem; opacity: 0.6; font-family: monospace;">' . APP_GIT_COMMIT . '</span>';
        }
        
        return $version . $env . $branch . $commit;
    }
    
    /**
     * Retorna informações do GitHub
     */
    public static function getGitHubInfo() {
        return [
            'url' => APP_GITHUB_URL ?? '',
            'branch' => APP_GITHUB_BRANCH ?? '',
            'commit' => APP_GIT_COMMIT ?? '',
            'release' => APP_RELEASE_TAG ?? '',
            'issues_url' => (APP_GITHUB_URL ?? '') . '/issues',
            'releases_url' => (APP_GITHUB_URL ?? '') . '/releases',
            'branch_url' => (APP_GITHUB_URL ?? '') . '/tree/' . (APP_GITHUB_BRANCH ?? ''),
            'commit_url' => (APP_GITHUB_URL ?? '') . '/commit/' . (APP_GIT_COMMIT ?? '')
        ];
    }
    
    /**
     * Retorna informações detalhadas do Git
     */
    public static function getGitInfo() {
        return [
            'branch' => APP_GITHUB_BRANCH ?? 'unknown',
            'commit' => APP_GIT_COMMIT ?? 'unknown',
            'environment' => APP_ENVIRONMENT ?? 'unknown',
            'build_date' => APP_BUILD_DATE ?? date('Y-m-d'),
            'build_number' => APP_BUILD_NUMBER ?? '1',
            'is_clean' => self::isGitClean(),
            'last_commit_message' => self::getLastCommitMessage()
        ];
    }
    
    /**
     * Verifica se o repositório Git está limpo (sem mudanças)
     */
    public static function isGitClean() {
        $status = executeGitCommand('git status --porcelain');
        return empty($status);
    }
    
    /**
     * Retorna a mensagem do último commit
     */
    public static function getLastCommitMessage() {
        return executeGitCommand('git log -1 --pretty=%B') ?: 'Commit message not available';
    }
    
    /**
     * Retorna informações para tooltip/debug
     */
    public static function getDebugInfo() {
        $git_info = self::getGitInfo();
        $clean_status = $git_info['is_clean'] ? 'Clean' : 'Modified';
        
        return [
            'version' => APP_VERSION,
            'environment' => APP_ENVIRONMENT,
            'branch' => APP_GITHUB_BRANCH,
            'commit' => APP_GIT_COMMIT,
            'status' => $clean_status,
            'build_date' => APP_BUILD_DATE,
            'build_number' => APP_BUILD_NUMBER,
            'last_commit' => substr($git_info['last_commit_message'], 0, 50) . '...'
        ];
    }
}
