<?php
/**
 * Visualizador de Logs - CapivaraLearn
 * Interface para visualizar e gerenciar logs do sistema
 */

require_once 'includes/config.php';

// Verificar se é desenvolvimento ou se tem permissão (simplificado para teste)
$allowAccess = true; // Em produção, adicionar autenticação adequada

if (!$allowAccess) {
    die('Acesso negado');
}

$logger = Logger::getInstance();
$action = $_GET['action'] ?? 'view';

// Processar ações
switch ($action) {
    case 'clear':
        $logger->clearLog();
        $logger->info("Log limpo manualmente", ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        header("Location: view_logs.php");
        exit;
        
    case 'test':
        $testResult = $logger->test();
        $logger->info("Teste de log executado", ['resultado' => $testResult]);
        break;
        
    case 'test_email':
        $logger->emailError('teste@exemplo.com', 'Este é um teste de log de email', [
            'config' => ['host' => 'teste.com', 'port' => 587],
            'debug' => 'Output de debug simulado'
        ]);
        break;
}

$logs = $logger->getLastLines(100);
$logFile = $logger->getLogFile();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs do Sistema - CapivaraLearn</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .header {
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0 0 10px 0;
        }
        .actions {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn:hover { opacity: 0.8; }
        
        .log-container {
            background: #1e1e1e;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.4;
            max-height: 600px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .log-info {
            background: #e8f5e8;
            color: #2d5a2d;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
        }
        
        .log-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        
        .stat-card h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        
        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
        }
        
        .auto-refresh {
            margin: 10px 0;
        }
        
        .auto-refresh input[type="checkbox"] {
            margin-right: 8px;
        }
        
        /* Destacar diferentes tipos de log */
        .log-container:has-text("ERROR") { color: #ff6b6b; }
        .log-container:has-text("WARNING") { color: #feca57; }
        .log-container:has-text("INFO") { color: #48cae4; }
        .log-container:has-text("DEBUG") { color: #a8dadc; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Logs do Sistema CapivaraLearn</h1>
            <p>Visualizador em tempo real dos logs da aplicação</p>
        </div>
        
        <div class="log-info">
            <strong>📂 Arquivo de Log:</strong> <?= htmlspecialchars($logFile) ?><br>
            <strong>📅 Última atualização:</strong> <?= file_exists($logFile) ? date('d/m/Y H:i:s', filemtime($logFile)) : 'Arquivo não existe' ?><br>
            <strong>📏 Tamanho:</strong> <?= file_exists($logFile) ? number_format(filesize($logFile) / 1024, 2) . ' KB' : '0 KB' ?>
        </div>
        
        <div class="actions">
            <a href="view_logs.php" class="btn btn-primary">🔄 Atualizar</a>
            <a href="view_logs.php?action=test" class="btn btn-success">🧪 Teste de Log</a>
            <a href="view_logs.php?action=test_email" class="btn btn-warning">📧 Teste Email Log</a>
            <a href="view_logs.php?action=clear" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja limpar todos os logs?')">🗑️ Limpar Logs</a>
        </div>
        
        <div class="auto-refresh">
            <label>
                <input type="checkbox" id="autoRefresh"> 
                🔄 Atualização automática (30s)
            </label>
        </div>
        
        <?php if (isset($testResult)): ?>
        <div class="log-info">
            <strong>🧪 Resultado do Teste:</strong> <?= htmlspecialchars($testResult) ?>
        </div>
        <?php endif; ?>
        
        <h3>📜 Últimas 100 linhas do log:</h3>
        
        <div class="log-container" id="logContent">
<?= htmlspecialchars($logs) ?>
        </div>
    </div>
    
    <script>
        // Auto-refresh opcional
        let autoRefreshInterval;
        const autoRefreshCheckbox = document.getElementById('autoRefresh');
        
        autoRefreshCheckbox.addEventListener('change', function() {
            if (this.checked) {
                autoRefreshInterval = setInterval(() => {
                    window.location.reload();
                }, 30000);
            } else {
                clearInterval(autoRefreshInterval);
            }
        });
        
        // Auto-scroll para o final do log
        const logContainer = document.getElementById('logContent');
        logContainer.scrollTop = logContainer.scrollHeight;
    </script>
</body>
</html>
