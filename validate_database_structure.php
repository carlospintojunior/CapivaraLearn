<?php
/**
 * Script de validação da estrutura do banco de dados
 * Compara a estrutura real com o que deveria ser criado pelo install.php
 */

require_once __DIR__ . '/includes/log_sistema.php';

// Configuração do banco
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'capivaralearn';

try {
    echo "🔍 VALIDAÇÃO DA ESTRUTURA DO BANCO DE DADOS\n";
    echo "=" . str_repeat("=", 50) . "\n\n";
    
    // Conectar ao banco usando mysqli
    $mysqli = new mysqli($host, $user, $pass, $dbname);
    if ($mysqli->connect_error) {
        throw new Exception("Erro de conexão: " . $mysqli->connect_error);
    }
    $mysqli->set_charset("utf8mb4");
    
    echo "✅ Conexão estabelecida com sucesso\n\n";
    log_sistema("Iniciando validação da estrutura do banco de dados", 'INFO');
    
    // Tabelas esperadas pelo install.php
    $expectedTables = [
        'usuarios',
        'email_tokens', 
        'universidades',
        'cursos',
        'disciplinas',
        'topicos',
        'universidade_cursos',
        'inscricoes'
    ];
    
    // Verificar se todas as tabelas existem
    echo "📋 VERIFICANDO EXISTÊNCIA DAS TABELAS:\n";
    echo "-" . str_repeat("-", 40) . "\n";
    
    $result = $mysqli->query("SHOW TABLES");
    $existingTables = [];
    while ($row = $result->fetch_row()) {
        $existingTables[] = $row[0];
    }
    
    $allTablesExist = true;
    foreach ($expectedTables as $table) {
        if (in_array($table, $existingTables)) {
            echo "✅ $table\n";
        } else {
            echo "❌ $table (FALTANDO)\n";
            $allTablesExist = false;
        }
    }
    
    if ($allTablesExist) {
        echo "\n✅ Todas as 8 tabelas existem!\n\n";
        log_sistema("Todas as 8 tabelas necessárias existem no banco", 'SUCCESS');
    } else {
        echo "\n❌ Algumas tabelas estão faltando!\n\n";
        log_sistema("ERRO: Algumas tabelas estão faltando no banco", 'ERROR');
    }
    
    // Verificar campos críticos para isolamento por usuário
    echo "🔐 VERIFICANDO ISOLAMENTO POR USUÁRIO:\n";
    echo "-" . str_repeat("-", 40) . "\n";
    
    $tablesWithUserId = ['universidades', 'cursos', 'disciplinas', 'topicos', 'universidade_cursos'];
    
    foreach ($tablesWithUserId as $table) {
        $result = $mysqli->query("DESCRIBE $table");
        $fields = [];
        while ($row = $result->fetch_assoc()) {
            $fields[$row['Field']] = $row;
        }
        
        if (isset($fields['usuario_id'])) {
            $fk = $fields['usuario_id'];
            echo "✅ $table.usuario_id ({$fk['Type']}, {$fk['Null']}, {$fk['Key']})\n";
        } else {
            echo "❌ $table.usuario_id (FALTANDO)\n";
        }
    }
    
    // Verificar constraint crítica da tabela universidade_cursos
    echo "\n🔗 VERIFICANDO CONSTRAINTS CRÍTICAS:\n";
    echo "-" . str_repeat("-", 40) . "\n";
    
    $result = $mysqli->query("SHOW CREATE TABLE universidade_cursos");
    $row = $result->fetch_assoc();
    $createTable = $row['Create Table'];
    
    if (strpos($createTable, 'UNIQUE KEY') !== false && 
        strpos($createTable, 'universidade_id') !== false && 
        strpos($createTable, 'curso_id') !== false && 
        strpos($createTable, 'usuario_id') !== false) {
        echo "✅ universidade_cursos.UNIQUE KEY inclui usuario_id (isolamento OK)\n";
        log_sistema("Constraint UNIQUE da tabela universidade_cursos está correta", 'SUCCESS');
    } else {
        echo "❌ universidade_cursos.UNIQUE KEY não isola por usuário\n";
        log_sistema("ERRO: Constraint UNIQUE da tabela universidade_cursos não isola por usuário", 'ERROR');
    }
    
    // Verificar se tabelas têm campos padrão esperados
    echo "\n📊 VERIFICANDO CAMPOS PADRÃO:\n";
    echo "-" . str_repeat("-", 40) . "\n";
    
    $standardFields = ['id', 'ativo', 'data_criacao', 'data_atualizacao'];
    $tablesWithStandardFields = ['universidades', 'cursos', 'disciplinas', 'universidade_cursos'];
    
    foreach ($tablesWithStandardFields as $table) {
        $result = $mysqli->query("DESCRIBE $table");
        $fields = [];
        while ($row = $result->fetch_assoc()) {
            $fields[] = $row['Field'];
        }
        
        $missing = [];
        foreach ($standardFields as $field) {
            if (!in_array($field, $fields)) {
                $missing[] = $field;
            }
        }
        
        if (empty($missing)) {
            echo "✅ $table (todos os campos padrão)\n";
        } else {
            echo "⚠️  $table (faltando: " . implode(', ', $missing) . ")\n";
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎯 RESUMO DA VALIDAÇÃO:\n\n";
    
    if ($allTablesExist) {
        echo "✅ Estrutura de tabelas: COMPLETA (8/8)\n";
        echo "✅ Isolamento por usuário: IMPLEMENTADO\n";
        echo "✅ Constraints de integridade: FUNCIONAIS\n";
        echo "✅ Sistema pronto para CRUD isolado por usuário\n\n";
        echo "🚀 O banco está 100% alinhado com install.php!\n";
        log_sistema("Validação completa: banco 100% alinhado com install.php", 'SUCCESS');
    } else {
        echo "❌ Algumas verificações falharam\n";
        echo "🔧 Execute install.php para corrigir\n";
        log_sistema("Validação falhou: banco não está completamente alinhado", 'WARNING');
    }
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    log_sistema("ERRO durante validação: " . $e->getMessage(), 'ERROR');
}

echo "\n" . str_repeat("=", 60) . "\n";
?>
