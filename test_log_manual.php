<?php
// Teste simples de log manual
echo "<!DOCTYPE html><html><head><title>Teste Log Manual</title></head><body>";
echo "<h1>Teste Manual de Logs</h1>";

// Teste 1: Log simples
echo "<h2>Teste 1: Log Simples</h2>";
$logFile = '/opt/lampp/htdocs/CapivaraLearn/logs/test_manual.log';
$message = date('Y-m-d H:i:s') . " - Login realizado por usuário teste\n";

if (file_put_contents($logFile, $message, FILE_APPEND)) {
    echo "<p>✅ Log manual criado com sucesso!</p>";
    echo "<p>Conteúdo: " . htmlspecialchars($message) . "</p>";
} else {
    echo "<p>❌ Erro ao criar log manual</p>";
}

// Teste 2: Log de atividade no banco
echo "<h2>Teste 2: Log de Atividade no Banco</h2>";
try {
    require_once '/opt/lampp/htdocs/CapivaraLearn/includes/config.php';
    
    $stmt = $pdo->prepare("INSERT INTO logs_atividade (usuario_id, acao, detalhes, timestamp) VALUES (?, ?, ?, NOW())");
    $result = $stmt->execute([1, 'login', 'Login realizado via teste manual']);
    
    if ($result) {
        echo "<p>✅ Log de atividade inserido no banco!</p>";
        
        // Buscar o último log
        $stmt = $pdo->query("SELECT * FROM logs_atividade ORDER BY timestamp DESC LIMIT 1");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($log) {
            echo "<p>Último log: " . json_encode($log) . "</p>";
        }
    } else {
        echo "<p>❌ Erro ao inserir log no banco</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}

// Teste 3: Verificar todos os logs
echo "<h2>Teste 3: Verificar Logs Existentes</h2>";
$logDir = '/opt/lampp/htdocs/CapivaraLearn/logs';
if (is_dir($logDir)) {
    $files = scandir($logDir);
    echo "<p>Arquivos no diretório de logs:</p>";
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filepath = $logDir . '/' . $file;
            $size = filesize($filepath);
            echo "<li>$file (tamanho: $size bytes)</li>";
            
            if ($size > 0) {
                echo "<pre>" . htmlspecialchars(file_get_contents($filepath)) . "</pre>";
            }
        }
    }
    echo "</ul>";
} else {
    echo "<p>❌ Diretório de logs não encontrado</p>";
}

echo "</body></html>";
?>
