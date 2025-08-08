<?php
/**
 * CapivaraLearn - Sistema de Versionamento
 * 
 * Define a versão atual da aplicação e informações relacionadas.
 * Seguindo o padrão Semantic Versioning (semver.org)
 * 
 * MAJOR.MINOR.PATCH
 * - MAJOR: mudanças incompatíveis na API
 * - MINOR: novas funcionalidades compatíveis
 * - PATCH: correções de bugs
 */

// Informações da versão atual
define('APP_VERSION', '0.7.0');
define('APP_NAME', 'CapivaraLearn');
define('APP_BUILD_DATE', '2025-08-07');
define('APP_BUILD_NUMBER', '004');
define('APP_ENVIRONMENT', 'development'); // development, staging, production
define('APP_GITHUB_URL', 'https://github.com/carlospintojunior/CapivaraLearn');
define('APP_GITHUB_BRANCH', '#43---Corrigir-cálculo-do-prazo-no-dashboard.-');
define('APP_RELEASE_TAG', 'v0.7.0-development');

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
        $env = (APP_ENVIRONMENT !== 'production') ? ' (' . strtoupper(APP_ENVIRONMENT) . ')' : '';
        $branch = '';
        if (defined('APP_GITHUB_BRANCH') && APP_GITHUB_BRANCH) {
            $branch_short = substr(str_replace(['#', '-'], ['', ' '], APP_GITHUB_BRANCH), 0, 15);
            $branch = '<br><span style="font-size: 0.65rem; opacity: 0.8;">' . $branch_short . '</span>';
        }
        return 'v' . APP_VERSION . $env . $branch;
    }
    
    /**
     * Retorna informações do GitHub
     */
    public static function getGitHubInfo() {
        return [
            'url' => APP_GITHUB_URL ?? '',
            'branch' => APP_GITHUB_BRANCH ?? '',
            'release' => APP_RELEASE_TAG ?? '',
            'issues_url' => (APP_GITHUB_URL ?? '') . '/issues',
            'releases_url' => (APP_GITHUB_URL ?? '') . '/releases'
        ];
    }
}
