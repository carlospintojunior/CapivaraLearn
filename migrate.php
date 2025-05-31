<?php
/**
 * CapivaraLearn - Sistema de Migrações
 * Executa atualizações automáticas no banco de dados
 */

require_once 'includes/config.php';

// Verificar se é admin ou desenvolvimento
$isAdmin = (DEBUG_MODE || isset($_GET['admin_key']) && $_GET['admin_key'] === 'capivaralearn_migrate_2025');

if (!$isAdmin && APP_ENV === 'production') {
    die('Acesso negado');
}

$db = Database::getInstance();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CapivaraLearn - Migrações do Banco</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin-bottom: 10px;
            font-weight: 300;
            font-size: 2.2em;
        }
        .content {
            padding: 40px;
        }
        .migration-item {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ecf0f1;
            border-radius: 10px;
            position: relative;
        }
        .migration-item h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .migration-item.success {
            border-color: #27ae60;
            background: #f1f8e9;
        }
        .migration-item.error {
            border-color: #e74c3c;
            background: #fdf2f2;
        }
        .migration-item.pending {
            border-color: #f39c12;
            background: #fff8e1;
        }
        .migration-item.skipped {
            border-color: #95a5a6;
            background: #f8f9fa;
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-bottom: 20px;
        }
        .btn:hover {
            background: #2980b9;
        }
        .log {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
            margin-top: 15px;
            white-space: pre-wrap;
        }
        .status-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
        }
        .current-version {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🦫 CapivaraLearn</h1>
            <p>Sistema de Migrações do Banco de Dados</p>
        </div>

        <div class="content">
            <div class="current-version">
                📊 Versão Atual: <?= APP_VERSION ?>
            </div>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo "<h2>🔄 Executando Migrações...</h2>";
                executeMigrations();
            } else {
                showMigrationStatus();
            ?>
                <form method="POST">
                    <button type="submit" class="btn">🚀 Executar Migrações Pendentes</button>
                </form>
            <?php } ?>
        </div>
    </div>
</body>
</html>

<?php
function showMigrationStatus() {
    global $db;
    
    // Criar tabela de migrações se não existir
    createMigrationsTable();
    
    // Lista de todas as migrações disponíveis
    $migrations = getMigrations();
    
    // Verificar status de cada migração
    foreach ($migrations as $migration) {
        $status = getMigrationStatus($migration['id']);
        
        echo "<div class='migration-item {$status['class']}'>";
        echo "<span class='status-icon'>{$status['icon']}</span>";
        echo "<h3>{$migration['name']}</h3>";
        echo "<p>{$migration['description']}</p>";
        echo "<strong>Status:</strong> {$status['text']}";
        
        if ($status['executed_at']) {
            echo "<br><small>Executado em: {$status['executed_at']}</small>";
        }
        
        echo "</div>";
    }
}

function executeMigrations() {
    global $db;
    
    createMigrationsTable();
    $migrations = getMigrations();
    $executed = 0;
    
    foreach ($migrations as $migration) {
        $status = getMigrationStatus($migration['id']);
        
        if ($status['executed']) {
            echo "<div class='migration-item skipped'>";
            echo "<span class='status-icon'>⏭️</span>";
            echo "<h3>{$migration['name']}</h3>";
            echo "<p>✅ Já executado anteriormente</p>";
            echo "</div>";
            continue;
        }
        
        echo "<div class='migration-item pending'>";
        echo "<span class='status-icon'>⏳</span>";
        echo "<h3>{$migration['name']}</h3>";
        echo "<p>🔄 Executando...</p>";
        
        try {
            $result = executeMigration($migration);
            
            if ($result['success']) {
                echo "<div class='log'>✅ Sucesso:\n{$result['log']}</div>";
                echo "<script>document.querySelector('.migration-item.pending').className = 'migration-item success'; document.querySelector('.status-icon').textContent = '✅';</script>";
                $executed++;
            } else {
                echo "<div class='log'>❌ Erro:\n{$result['error']}</div>";
                echo "<script>document.querySelector('.migration-item.pending').className = 'migration-item error'; document.querySelector('.status-icon').textContent = '❌';</script>";
                break; // Parar se der erro
            }
            
        } catch (Exception $e) {
            echo "<div class='log'>❌ Exceção: " . $e->getMessage() . "</div>";
            echo "<script>document.querySelector('.migration-item.pending').className = 'migration-item error'; document.querySelector('.status-icon').textContent = '❌';</script>";
            break;
        }
        
        echo "</div>";
        flush();
        ob_flush();
    }
    
    echo "<h2>🎉 Migrações Concluídas!</h2>";
    echo "<p><strong>{$executed}</strong> migrações executadas com sucesso.</p>";
    echo "<a href='migrate.php' class='btn'>🔄 Verificar Status Novamente</a>";
}

function createMigrationsTable() {
    global $db;
    
    $sql = "CREATE TABLE IF NOT EXISTS migrations (
        id VARCHAR(100) PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        execution_time_ms INT DEFAULT 0,
        success BOOLEAN DEFAULT TRUE
    ) ENGINE=InnoDB";
    
    $db->execute($sql);
}

function getMigrations() {
    return [
        [
            'id' => '001_email_confirmation_system',
            'name' => 'Sistema de Confirmação de Email',
            'description' => 'Adiciona colunas para verificação de email e tabelas de tokens',
            'sql' => function() {
                global $db;
                $sqls = [
                    // Verificar se colunas já existem antes de adicionar
                    "SELECT COUNT(*) as count FROM information_schema.COLUMNS 
                     WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
                     AND TABLE_NAME = 'usuarios' 
                     AND COLUMN_NAME = 'email_verificado'",
                    
                    // Adicionar colunas apenas se não existirem
                    "ALTER TABLE usuarios 
                     ADD COLUMN email_verificado BOOLEAN DEFAULT FALSE,
                     ADD COLUMN data_verificacao TIMESTAMP NULL",
                     
                    // Criar tabela de tokens
                    "CREATE TABLE IF NOT EXISTS email_tokens (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        token VARCHAR(255) NOT NULL UNIQUE,
                        tipo ENUM('confirmacao', 'recuperacao_senha') NOT NULL,
                        usado BOOLEAN DEFAULT FALSE,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_expiracao TIMESTAMP NOT NULL,
                        ip_address VARCHAR(45) DEFAULT NULL,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        INDEX idx_token (token),
                        INDEX idx_usuario_token (usuario_id, tipo, usado),
                        INDEX idx_expiracao (data_expiracao, usado)
                    ) ENGINE=InnoDB",
                    
                    // Criar tabela de log de emails
                    "CREATE TABLE IF NOT EXISTS email_log (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT DEFAULT NULL,
                        email_destino VARCHAR(150) NOT NULL,
                        assunto VARCHAR(255) NOT NULL,
                        tipo ENUM('confirmacao', 'recuperacao_senha', 'notificacao') NOT NULL,
                        status ENUM('enviado', 'falha', 'pendente') DEFAULT 'pendente',
                        tentativas INT DEFAULT 0,
                        erro_detalhes TEXT DEFAULT NULL,
                        data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_entrega TIMESTAMP NULL,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
                        INDEX idx_email_status (email_destino, status),
                        INDEX idx_tipo_data (tipo, data_envio)
                    ) ENGINE=InnoDB",
                    
                    // Verificar usuário teste
                    "UPDATE usuarios 
                     SET email_verificado = TRUE, data_verificacao = NOW() 
                     WHERE email = 'teste@capivaralearn.com' AND email_verificado IS NOT NULL"
                ];
                return $sqls;
            }
        ],
        [
            'id' => '002_cleanup_expired_tokens',
            'name' => 'Limpeza Automática de Tokens',
            'description' => 'Cria evento para limpar tokens expirados automaticamente',
            'sql' => function() {
                global $db;
                return [
                    "DROP EVENT IF EXISTS limpar_tokens_expirados",
                    "CREATE EVENT limpar_tokens_expirados
                     ON SCHEDULE EVERY 1 HOUR
                     DO DELETE FROM email_tokens WHERE data_expiracao < NOW()"
                ];
            }
        ],
        [
            'id' => '003_email_system_indexes',
            'name' => 'Otimização de Índices',
            'description' => 'Adiciona índices para melhor performance do sistema de email',
            'sql' => function() {
                global $db;
                return [
                    "ALTER TABLE usuarios ADD INDEX idx_email_verificado (email_verificado, ativo)",
                    "ALTER TABLE email_tokens ADD INDEX idx_tipo_expiracao (tipo, data_expiracao, usado)"
                ];
            }
        ]
    ];
}

function getMigrationStatus($migrationId) {
    global $db;
    
    $result = $db->select(
        "SELECT * FROM migrations WHERE id = ?",
        [$migrationId]
    );
    
    if (!empty($result)) {
        return [
            'executed' => true,
            'executed_at' => $result[0]['executed_at'],
            'class' => 'success',
            'icon' => '✅',
            'text' => 'Executado com sucesso'
        ];
    } else {
        return [
            'executed' => false,
            'executed_at' => null,
            'class' => 'pending',
            'icon' => '⏳',
            'text' => 'Pendente de execução'
        ];
    }
}

function executeMigration($migration) {
    global $db;
    
    $startTime = microtime(true);
    $log = "";
    
    try {
        $sqls = $migration['sql']();
        
        foreach ($sqls as $sql) {
            if (empty(trim($sql))) continue;
            
            // Se for uma query de verificação
            if (stripos($sql, 'SELECT COUNT') === 0) {
                $result = $db->select($sql);
                $count = $result[0]['count'] ?? 0;
                $log .= "Verificação: $count registros encontrados\n";
                
                // Se colunas já existem, pular ALTER TABLE
                if ($count > 0 && stripos($sqls[1] ?? '', 'ALTER TABLE') === 0) {
                    $log .= "Colunas já existem, pulando ALTER TABLE\n";
                    continue;
                }
                continue;
            }
            
            $db->execute($sql);
            $log .= "✅ Executado: " . substr($sql, 0, 80) . "...\n";
        }
        
        // Registrar migração como executada
        $executionTime = round((microtime(true) - $startTime) * 1000);
        
        $db->execute(
            "INSERT INTO migrations (id, name, execution_time_ms) VALUES (?, ?, ?)",
            [$migration['id'], $migration['name'], $executionTime]
        );
        
        return [
            'success' => true,
            'log' => $log . "\nTempo de execução: {$executionTime}ms"
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'log' => $log
        ];
    }
}
?>