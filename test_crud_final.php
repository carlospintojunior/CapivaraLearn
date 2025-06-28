<?php
/**
 * Teste Final dos CRUDs Simplificados
 * Sistema CapivaraLearn
 */

echo "<h1>Teste dos CRUDs Simplificados</h1>";

// Lista dos CRUDs para testar
$cruds = [
    'universities_simple.php' => 'Universidades',
    'courses_simple.php' => 'Cursos',
    'modules_simple.php' => 'Disciplinas/Módulos',
    'topics_simple.php' => 'Tópicos',
    'enrollments_simple.php' => 'Matrículas'
];

echo "<h2>Status dos Arquivos CRUD:</h2>";
echo "<ul>";

foreach ($cruds as $file => $name) {
    $path = __DIR__ . '/crud/' . $file;
    if (file_exists($path)) {
        echo "<li>✅ <strong>$name</strong> - <a href='crud/$file' target='_blank'>$file</a> (Arquivo existe)</li>";
    } else {
        echo "<li>❌ <strong>$name</strong> - $file (Arquivo não encontrado)</li>";
    }
}

echo "</ul>";

// Testar conexão com banco e estrutura das tabelas
echo "<h2>Estrutura do Banco de Dados:</h2>";

try {
    require_once __DIR__ . '/crud/includes/medoo_config.php';
    
    $tables = ['usuarios', 'universidades', 'cursos', 'disciplinas', 'topicos', 'inscricoes'];
    
    echo "<ul>";
    foreach ($tables as $table) {
        try {
            $count = $database->count($table);
            echo "<li>✅ Tabela <strong>$table</strong>: $count registros</li>";
        } catch (Exception $e) {
            echo "<li>❌ Tabela <strong>$table</strong>: Erro - " . $e->getMessage() . "</li>";
        }
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao conectar com o banco de dados: " . $e->getMessage() . "</p>";
}

echo "<h2>Dashboard e Navegação:</h2>";
echo "<ul>";
echo "<li><a href='dashboard.php' target='_blank'>🏠 Dashboard Principal</a></li>";
echo "<li><a href='crud/universities_simple.php' target='_blank'>🏛️ CRUD Universidades</a></li>";
echo "<li><a href='crud/courses_simple.php' target='_blank'>🎓 CRUD Cursos</a></li>";
echo "<li><a href='crud/modules_simple.php' target='_blank'>📚 CRUD Disciplinas</a></li>";
echo "<li><a href='crud/topics_simple.php' target='_blank'>📝 CRUD Tópicos</a></li>";
echo "<li><a href='crud/enrollments_simple.php' target='_blank'>🎯 CRUD Matrículas</a></li>";
echo "</ul>";

echo "<h2>Instruções de Teste:</h2>";
echo "<ol>";
echo "<li>Acesse o <a href='dashboard.php' target='_blank'>Dashboard</a> e faça login</li>";
echo "<li>Use o menu dropdown (⚙️) para acessar os CRUDs</li>";
echo "<li>Teste cada CRUD criando, editando e excluindo registros</li>";
echo "<li>Verifique os relacionamentos (tópicos dependem de disciplinas, matrículas dependem de universidades e cursos)</li>";
echo "<li>Confirme que todos os dados são isolados por usuário</li>";
echo "</ol>";

echo "<p><strong>Data do teste:</strong> " . date('d/m/Y H:i:s') . "</p>";
?>
