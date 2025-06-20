<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/services/CourseService.php';
require_once __DIR__ . '/includes/services/UniversityService.php';

// Verificar se usuário está logado
requireLogin();

$courseService = CourseService::getInstance();

echo "<h1>🔍 Debug dos Dados de Matrícula</h1>";
echo "<pre>";

try {
    $enrollments = $courseService->listEnrollments();
    
    echo "=== TOTAL DE MATRÍCULAS ENCONTRADAS ===\n";
    echo "Quantidade: " . count($enrollments) . "\n\n";
    
    if (!empty($enrollments)) {
        echo "=== ESTRUTURA DO PRIMEIRO REGISTRO ===\n";
        echo "Chaves disponíveis:\n";
        foreach (array_keys($enrollments[0]) as $key) {
            echo "- $key\n";
        }
        
        echo "\n=== DADOS COMPLETOS DO PRIMEIRO REGISTRO ===\n";
        print_r($enrollments[0]);
        
        echo "\n=== TODOS OS REGISTROS (RESUMIDO) ===\n";
        foreach ($enrollments as $index => $enrollment) {
            echo "Registro $index:\n";
            foreach ($enrollment as $key => $value) {
                $displayValue = is_null($value) ? '[NULL]' : (is_string($value) ? "'" . $value . "'" : $value);
                echo "  $key: $displayValue\n";
            }
            echo "  ---\n";
        }
    } else {
        echo "❌ Nenhuma matrícula encontrada!\n";
        
        // Vamos verificar se existem dados nas tabelas base
        echo "\n=== VERIFICANDO TABELAS BASE ===\n";
        
        $db = Database::getInstance();
        
        // Verificar usuários
        $users = $db->select("SELECT COUNT(*) as total FROM usuarios");
        echo "Total de usuários: " . $users[0]['total'] . "\n";
        
        // Verificar se existem outras tabelas que podem estar sendo usadas
        $tables = $db->select("SHOW TABLES");
        echo "\nTabelas disponíveis:\n";
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            echo "- $tableName\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";

// Vamos também ver o método listEnrollments se possível
echo "<h2>📋 Código do CourseService::listEnrollments()</h2>";
echo "<p>Vamos verificar se o método existe e como ele está implementado:</p>";
echo "<pre>";

if (class_exists('CourseService')) {
    $reflection = new ReflectionClass('CourseService');
    
    if ($reflection->hasMethod('listEnrollments')) {
        $method = $reflection->getMethod('listEnrollments');
        echo "Método listEnrollments() existe!\n";
        echo "É público: " . ($method->isPublic() ? "SIM" : "NÃO") . "\n";
        echo "É estático: " . ($method->isStatic() ? "SIM" : "NÃO") . "\n";
        
        // Tentar ver o código do arquivo
        $filename = $reflection->getFileName();
        echo "\nArquivo: $filename\n";
        
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            
            // Procurar pelo método listEnrollments
            if (preg_match('/function\s+listEnrollments\s*\([^)]*\)\s*\{([^}]+(?:\{[^}]*\}[^}]*)*)\}/', $content, $matches)) {
                echo "\nCódigo do método:\n";
                echo "function listEnrollments() {\n";
                echo trim($matches[1]) . "\n";
                echo "}\n";
            } else {
                echo "\nNão foi possível extrair o código do método.\n";
            }
        }
    } else {
        echo "❌ Método listEnrollments() NÃO EXISTE!\n";
        echo "Métodos disponíveis:\n";
        foreach ($reflection->getMethods() as $method) {
            if ($method->isPublic()) {
                echo "- " . $method->getName() . "()\n";
            }
        }
    }
} else {
    echo "❌ Classe CourseService não encontrada!\n";
}

echo "</pre>";
?>

<div style="margin-top: 20px; padding: 15px; background: #e8f4f8; border-radius: 8px;">
    <h4>ℹ️ O que este debug vai mostrar:</h4>
    <ul>
        <li>✅ Quantas matrículas existem</li>
        <li>✅ Quais chaves/campos estão disponíveis</li>
        <li>✅ Valores reais de cada campo</li>
        <li>✅ Se há valores NULL causando problemas</li>
        <li>✅ Como o método listEnrollments() está implementado</li>
    </ul>
</div>