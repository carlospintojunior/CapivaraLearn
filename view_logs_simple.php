<?php
/**
 * Visualizador de Logs Simples - Sem dependências
 */

$logDir = '/opt/lampp/htdocs/CapivaraLearn/logs';

// Listar todos os arquivos de log
$logFiles = [];
if (is_dir($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'log') {
            $logFiles[] = $file;
        }
    }
}

$selectedFile = $_GET['file'] ?? ($logFiles[0] ?? null);
$action = $_GET['action'] ?? '';

// Processar ações
if ($action === 'clear' && $selectedFile) {
    $filePath = $logDir . '/' . $selectedFile;
    if (file_exists($filePath)) {
        file_put_contents($filePath, '');
        header("Location: view_logs_simple.php?file=" . urlencode($selectedFile));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs - CapivaraLearn</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            line-height: 1.6;
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
            margin: 0;
        }
        .controls {
            margin: 20px 0;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .controls select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
            color: white;
        }
        .btn-primary { background: #3498db; }
        .btn-danger { background: #e74c3c; }
        .btn-success { background: #27ae60; }
        .btn:hover { opacity: 0.8; }
        
        .log-info {
            background: #e8f5e8;
            color: #2d5a2d;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
        }
        
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
        
        .no-logs {
            text-align: center;
            color: #666;
            padding: 40px;
        }
        
        .file-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .file-tab {
            padding: 10px 15px;
            background: #ecf0f1;
            border: none;
            border-radius: 5px 5px 0 0;
            cursor: pointer;
            text-decoration: none;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .file-tab.active {
            background: #3498db;
            color: white;
        }
        
        .auto-refresh {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Logs do Sistema - Visualizador Simples</h1>
            <p>Visualização de logs sem dependências complexas</p>
        </div>
        
        <?php if (empty($logFiles)): ?>
            <div class="no-logs">
                <h3>📂 Nenhum arquivo de log encontrado</h3>
                <p>Diretório: <?= htmlspecialchars($logDir) ?></p>
                <p>Execute alguns testes para gerar logs!</p>
            </div>
        <?php else: ?>
            
            <div class="file-tabs">
                <?php foreach ($logFiles as $file): ?>
                    <a href="view_logs_simple.php?file=<?= urlencode($file) ?>" 
                       class="file-tab <?= $file === $selectedFile ? 'active' : '' ?>">
                        📄 <?= htmlspecialchars($file) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if ($selectedFile): ?>
                <?php
                $filePath = $logDir . '/' . $selectedFile;
                $fileExists = file_exists($filePath);
                $fileSize = $fileExists ? filesize($filePath) : 0;
                $lastModified = $fileExists ? filemtime($filePath) : null;
                ?>
                
                <div class="log-info">
                    <strong>📂 Arquivo:</strong> <?= htmlspecialchars($selectedFile) ?><br>
                    <strong>📅 Modificado:</strong> <?= $lastModified ? date('d/m/Y H:i:s', $lastModified) : 'N/A' ?><br>
                    <strong>📏 Tamanho:</strong> <?= number_format($fileSize / 1024, 2) ?> KB
                </div>
                
                <div class="controls">
                    <a href="view_logs_simple.php?file=<?= urlencode($selectedFile) ?>" class="btn btn-primary">🔄 Atualizar</a>
                    <a href="view_logs_simple.php?file=<?= urlencode($selectedFile) ?>&action=clear" 
                       class="btn btn-danger" 
                       onclick="return confirm('Limpar este arquivo de log?')">🗑️ Limpar</a>
                    <a href="test_smtp_simple.php" class="btn btn-success">🧪 Testar SMTP</a>
                </div>
                
                <div class="auto-refresh">
                    <label>
                        <input type="checkbox" id="autoRefresh"> 
                        🔄 Atualização automática (10s)
                    </label>
                </div>
                
                <?php if ($fileExists && $fileSize > 0): ?>
                    <div class="log-container">
<?= htmlspecialchars(file_get_contents($filePath)) ?>
                    </div>
                <?php else: ?>
                    <div class="no-logs">
                        <p>📝 Arquivo vazio ou não encontrado</p>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
    
    <script>
        // Auto-refresh
        let autoRefreshInterval;
        const autoRefreshCheckbox = document.getElementById('autoRefresh');
        
        if (autoRefreshCheckbox) {
            autoRefreshCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    autoRefreshInterval = setInterval(() => {
                        window.location.reload();
                    }, 10000);
                } else {
                    clearInterval(autoRefreshInterval);
                }
            });
        }
        
        // Auto-scroll para o final
        const logContainer = document.querySelector('.log-container');
        if (logContainer) {
            logContainer.scrollTop = logContainer.scrollHeight;
        }
    </script>
</body>
</html>
