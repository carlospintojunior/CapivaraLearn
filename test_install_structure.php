<?php
/**
 * Teste para validar se a estrutura do banco foi criada corretamente pelo install.php
 * Este script verifica se todas as tabelas e constraints estão presentes
 */

require_once __DIR__ . '/includes/config.php';

function testDatabaseStructure() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "🔍 Testando estrutura do banco de dados...\n\n";
        
        // Lista das tabelas esperadas
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
        $stmt = $pdo->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "📋 Tabelas encontradas:\n";
        foreach ($existingTables as $table) {
            $status = in_array($table, $expectedTables) ? "✅" : "⚠️";
            echo "  $status $table\n";
        }
        
        // Verificar se alguma tabela está faltando
        $missingTables = array_diff($expectedTables, $existingTables);
        if (!empty($missingTables)) {
            echo "\n❌ Tabelas faltando:\n";
            foreach ($missingTables as $table) {
                echo "  - $table\n";
            }
            return false;
        }
        
        echo "\n✅ Todas as tabelas esperadas estão presentes!\n\n";
        
        // Verificar campos usuario_id nas tabelas que devem ter isolamento
        $tablesWithUserId = ['universidades', 'cursos', 'disciplinas', 'topicos', 'universidade_cursos', 'inscricoes'];
        
        echo "🔒 Verificando isolamento por usuário...\n";
        foreach ($tablesWithUserId as $table) {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $hasUserId = false;
            foreach ($columns as $column) {
                if ($column['Field'] === 'usuario_id') {
                    $hasUserId = true;
                    break;
                }
            }
            
            $status = $hasUserId ? "✅" : "❌";
            echo "  $status $table - campo usuario_id\n";
            
            if (!$hasUserId) {
                return false;
            }
        }
        
        echo "\n🔗 Verificando constraints importantes...\n";
        
        // Verificar constraint da tabela universidade_cursos
        $stmt = $pdo->query("SHOW CREATE TABLE universidade_cursos");
        $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
        $createTableSQL = $createTable['Create Table'];
        
        if (strpos($createTableSQL, 'unique_user_univ_curso') !== false) {
            echo "  ✅ universidade_cursos - constraint com usuario_id presente\n";
        } else {
            echo "  ❌ universidade_cursos - constraint sem usuario_id (permite duplicatas entre usuários)\n";
            return false;
        }
        
        // Verificar foreign keys
        $stmt = $pdo->query("
            SELECT 
                TABLE_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE 
                REFERENCED_TABLE_SCHEMA = '" . DB_NAME . "'
                AND TABLE_NAME IN ('" . implode("', '", $expectedTables) . "')
        ");
        
        $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\n🔗 Foreign Keys encontradas: " . count($foreignKeys) . "\n";
        
        foreach ($foreignKeys as $fk) {
            echo "  ✅ {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
        }
        
        echo "\n🎉 Estrutura do banco validada com sucesso!\n";
        echo "📊 Resumo:\n";
        echo "  - " . count($expectedTables) . " tabelas criadas\n";
        echo "  - " . count($tablesWithUserId) . " tabelas com isolamento por usuário\n";
        echo "  - " . count($foreignKeys) . " foreign keys configuradas\n";
        echo "  - Constraints de duplicidade funcionando corretamente\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ Erro ao testar estrutura: " . $e->getMessage() . "\n";
        return false;
    }
}

// Executar teste apenas se chamado diretamente
if (php_sapi_name() === 'cli' || basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    if (testDatabaseStructure()) {
        echo "\n✅ TESTE PASSOU - Estrutura do banco está correta!\n";
        exit(0);
    } else {
        echo "\n❌ TESTE FALHOU - Problemas na estrutura do banco!\n";
        exit(1);
    }
}
?>
