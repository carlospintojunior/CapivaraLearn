<?php
/**
 * CapivaraLearn - Script de Limpeza
 * Este script remove todas as tabelas do banco de dados e reseta o ambiente
 */

require_once __DIR__ . '/includes/config.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CapivaraLearn - Limpeza do Sistema</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .cleanup {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background: #d5f4e6;
            color: #27ae60;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error {
            background: #fdf2f2;
            color: #e74c3c;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .btn {
            background: #e74c3c;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn:hover {
            background: #c0392b;
        }
        .log {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 15px;
        }
        .checkbox-container {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .checkbox-container label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        .checkbox-container input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
    </style>
</head>
<body>
    <div class="cleanup">
        <div class="header">
            <h1>CapivaraLearn - Limpeza do Sistema</h1>
            <p>Esta ferramenta irá limpar o banco de dados</p>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['confirm']) || $_POST['confirm'] !== 'on') {
                echo '<div class="error">Por favor, confirme que deseja limpar o banco de dados marcando a caixa de confirmação.</div>';
            } else {
                echo '<div class="warning">🔄 Executando limpeza do sistema...</div>';
                
                $log = '';
                
                try {
                    // Limpar cookies e sessão
                    session_start();
                    $_SESSION = array();
                    session_destroy();
                    if (isset($_SERVER['HTTP_COOKIE'])) {
                        $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
                        foreach($cookies as $cookie) {
                            $parts = explode('=', $cookie);
                            $name = trim($parts[0]);
                            setcookie($name, '', time()-3600, '/');
                        }
                    }
                    
                    $db = Database::getInstance();
                    $pdo = $db->getConnection();
                    
                    // Desabilitar verificações de chave estrangeira temporariamente
                    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                    
                    // Listar todas as tabelas
                    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Dropar cada tabela
                    foreach ($tables as $table) {
                        try {
                            $pdo->exec("DROP TABLE IF EXISTS `$table`");
                            $log .= "✅ Tabela '$table' removida\n";
                        } catch (Exception $e) {
                            $log .= "❌ Erro ao remover tabela '$table': " . $e->getMessage() . "\n";
                        }
                    }
                    
                    // Reabilitar verificações de chave estrangeira
                    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                    
                    echo '<div class="success">✅ Banco de dados limpo com sucesso</div>';
                    
                    // Limpar diretório de uploads se solicitado
                    if (isset($_POST['clear_uploads']) && $_POST['clear_uploads'] === 'on') {
                        $uploadsDir = __DIR__ . '/public/assets/uploads';
                        if (is_dir($uploadsDir)) {
                            $files = new RecursiveIteratorIterator(
                                new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS),
                                RecursiveIteratorIterator::CHILD_FIRST
                            );
                            
                            foreach ($files as $fileinfo) {
                                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                                if ($todo($fileinfo->getRealPath())) {
                                    $log .= "✅ Removido: " . $fileinfo->getRealPath() . "\n";
                                } else {
                                    $log .= "❌ Erro ao remover: " . $fileinfo->getRealPath() . "\n";
                                }
                            }
                            
                            echo '<div class="success">✅ Diretório de uploads limpo</div>';
                        }
                    }
                    
                    // Limpar logs se solicitado
                    if (isset($_POST['clear_logs']) && $_POST['clear_logs'] === 'on') {
                        $logsDir = __DIR__ . '/logs';
                        if (is_dir($logsDir)) {
                            $files = glob($logsDir . '/*.log');
                            foreach ($files as $file) {
                                if (unlink($file)) {
                                    $log .= "✅ Log removido: " . basename($file) . "\n";
                                } else {
                                    $log .= "❌ Erro ao remover log: " . basename($file) . "\n";
                                }
                            }
                            echo '<div class="success">✅ Arquivos de log limpos</div>';
                        }
                    }
                    
                    echo '<div class="log">' . nl2br(htmlspecialchars($log)) . '</div>';
                    
                    echo '<div class="success">';
                    echo '<p><strong>Sistema limpo com sucesso!</strong></p>';
                    echo '<p>Para reinstalar o sistema, execute o arquivo <code>install.php</code>.</p>';
                    echo '</div>';
                    
                } catch (Exception $e) {
                    echo '<div class="error">❌ Erro durante a limpeza:</div>';
                    echo '<div class="error">' . htmlspecialchars($e->getMessage()) . '</div>';
                    if (!empty($log)) {
                        echo '<div class="log">' . nl2br(htmlspecialchars($log)) . '</div>';
                    }
                }
            }
        } else {
        ?>
        
        <div class="warning">
            <strong>⚠️ ATENÇÃO!</strong>
            <p>Esta ação irá:</p>
            <ul>
                <li>Remover TODAS as tabelas do banco de dados</li>
                <li>Excluir todos os dados cadastrados</li>
                <li>Resetar o sistema para o estado inicial</li>
            </ul>
            <p>Esta ação é <strong>IRREVERSÍVEL</strong>!</p>
        </div>

        <form method="POST">
            <div class="checkbox-container">
                <label>
                    <input type="checkbox" name="confirm" required>
                    Sim, eu entendo que todos os dados serão perdidos
                </label>
            </div>

            <div class="checkbox-container">
                <label>
                    <input type="checkbox" name="clear_uploads">
                    Também limpar diretório de uploads
                </label>
            </div>

            <div class="checkbox-container">
                <label>
                    <input type="checkbox" name="clear_logs">
                    Também limpar arquivos de log
                </label>
            </div>

            <button type="submit" class="btn">🗑️ Limpar Sistema</button>
        </form>

        <?php } ?>
    </div>
</body>
</html>
