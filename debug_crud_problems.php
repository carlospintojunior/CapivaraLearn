<?php
/**
 * Script de debug para testar problemas das páginas CRUD
 */

echo "=== DEBUG PÁGINAS CRUD ===\n\n";

// 1. Testar se sessão está funcionando
session_start();
echo "1. Sessão PHP: ✅ Funcionando\n";

// 2. Testar extensões PHP necessárias
echo "\n2. Extensões PHP:\n";
$extensions = ['pdo', 'pdo_mysql', 'mysqli', 'json'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ $ext carregada\n";
    } else {
        echo "   ❌ $ext NÃO carregada\n";
    }
}

// 3. Testar se arquivos de configuração existem
echo "\n3. Arquivos de configuração:\n";
$files = [
    'includes/config.php' => 'Configuração principal',
    'includes/DatabaseConnection.php' => 'Conexão com banco',
    'includes/services/UniversityService.php' => 'Service Universidades',
    'includes/services/CourseService.php' => 'Service Cursos',
    'includes/services/ModuleService.php' => 'Service Módulos',
    'includes/services/TopicService.php' => 'Service Tópicos'
];

foreach ($files as $file => $desc) {
    if (file_exists($file)) {
        echo "   ✅ $desc ($file)\n";
    } else {
        echo "   ❌ $desc ($file) - NÃO EXISTE\n";
    }
}

// 4. Testar conexão com banco
echo "\n4. Testando conexão com banco:\n";
try {
    // Simular uma conexão direta
    $host = 'localhost';
    $dbname = 'capivaralearn';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    echo "   ✅ Conexão PDO com MySQL funcionando\n";
    
    // Testar algumas tabelas
    $tables = ['usuarios', 'universidades', 'cursos', 'modulos', 'topicos'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "   ✅ Tabela '$table': $count registros\n";
        } catch (Exception $e) {
            echo "   ❌ Tabela '$table': " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Erro de conexão: " . $e->getMessage() . "\n";
}

// 5. Testar carregamento da função requireLogin
echo "\n5. Testando funções:\n";
try {
    require_once 'includes/config.php';
    
    if (function_exists('requireLogin')) {
        echo "   ✅ Função requireLogin() definida\n";
    } else {
        echo "   ❌ Função requireLogin() NÃO encontrada\n";
    }
    
    if (function_exists('isLoggedIn')) {
        echo "   ✅ Função isLoggedIn() definida\n";
    } else {
        echo "   ❌ Função isLoggedIn() NÃO encontrada\n";
    }
    
    if (class_exists('Database')) {
        echo "   ✅ Classe Database definida\n";
    } else {
        echo "   ❌ Classe Database NÃO encontrada\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Erro ao carregar config.php: " . $e->getMessage() . "\n";
}

echo "\n=== DIAGNÓSTICO COMPLETO ===\n";
echo "Execute este script no navegador e via linha de comando para comparar!\n";
?>
