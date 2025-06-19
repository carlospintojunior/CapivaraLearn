<?php
require_once __DIR__ . '/includes/log_sistema.php';
log_sistema('cleanup.php iniciado', 'INFO');
/**
 * CapivaraLearn - Script de Limpeza
 * Este script remove todas as tabelas do banco de dados e reseta o ambiente
 */

// Conectar diretamente ao MySQL sem especificar banco
try {
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

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
                    log_sistema('Iniciando limpeza completa do sistema via cleanup.php', 'INFO');
                    
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
                    log_sistema('Cookies e sessão limpos via cleanup.php', 'INFO');
                    
                    // Tentar apagar banco inteiro e recriar
                    try {
                        $pdo->exec("DROP DATABASE IF EXISTS capivaralearn");
                        $log .= "✅ Banco de dados 'capivaralearn' removido\n";
                        log_sistema('Banco de dados capivaralearn removido via cleanup.php', 'INFO');
                        
                        $pdo->exec("CREATE DATABASE capivaralearn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        $log .= "✅ Banco de dados 'capivaralearn' recriado\n";
                        log_sistema('Banco de dados capivaralearn recriado via cleanup.php', 'INFO');
                        
                    } catch (Exception $e) {
                        $log .= "❌ Erro: " . $e->getMessage() . "\n";
                        log_sistema('Erro ao limpar banco de dados via cleanup.php: ' . $e->getMessage(), 'ERROR');
                    }
                    
                    // Reabilitar verificações de chave estrangeira
                    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                    
                    echo '<div class="success">✅ Banco de dados limpo com sucesso</div>';
                    log_sistema('Limpeza do banco de dados concluída com sucesso via cleanup.php', 'SUCCESS');
                    
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
                            log_sistema('Diretório de uploads limpo via cleanup.php', 'INFO');
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
                            log_sistema('Arquivos de log limpos via cleanup.php', 'INFO');
                        }
                    }
                    
                    echo '<div class="log">' . nl2br(htmlspecialchars($log)) . '</div>';
                    
                    log_sistema('Limpeza completa do sistema finalizada com sucesso via cleanup.php', 'SUCCESS');
                    
                    echo '<div class="success">';
                    echo '<p><strong>Sistema limpo com sucesso!</strong></p>';
                    echo '<p>Para reinstalar o sistema, execute o arquivo <code>install.php</code>.</p>';
                    echo '</div>';
                    
                } catch (Exception $e) {
                    log_sistema('ERRO CRÍTICO durante limpeza do sistema via cleanup.php: ' . $e->getMessage(), 'CRITICAL');
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
