<?php
// Configuração do ambiente para Slim Framework
return [
    'settings' => [
        'displayErrorDetails' => true, // Alterar para false em produção
        'addContentLengthHeader' => false, // Permitir que o servidor web defina o header Content-Length
        'determineRouteBeforeAppMiddleware' => true,
        
        // Configurações do banco de dados
        'db' => [
            'host' => DB_HOST,
            'port' => 3306,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASS,
            'charset' => DB_CHARSET,
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ],
        
        // Configurações de logs
        'logger' => [
            'name' => 'capivaralearn',
            'path' => __DIR__ . '/logs/sistema.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        
        // Configurações de sessão
        'session' => [
            'name' => 'capivaralearn_session',
            'autorefresh' => true,
            'lifetime' => '1 hour',
        ],
        
        // Configurações de template
        'view' => [
            'template_path' => __DIR__ . '/templates',
            'cache_path' => __DIR__ . '/cache',
        ],
    ],
];
