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
define('APP_VERSION', '1.0.0');
define('APP_NAME', 'CapivaraLearn');
define('APP_BUILD_DATE', '2025-07-19');
define('APP_BUILD_NUMBER', '001');
define('APP_ENVIRONMENT', 'development'); // development, staging, production

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
            'full' => self::getFull()
        ];
    }
    
    /**
     * Retorna string formatada para footer
     */
    public static function getFooterText() {
        return APP_NAME . ' v' . APP_VERSION . ' • Build ' . APP_BUILD_NUMBER;
    }
    
    /**
     * Retorna string formatada para sidebar
     */
    public static function getSidebarText() {
        $env = (APP_ENVIRONMENT !== 'production') ? ' (' . strtoupper(APP_ENVIRONMENT) . ')' : '';
        return 'v' . APP_VERSION . $env;
    }
}
