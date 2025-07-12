<?php
// Teste simples para verificar se o sistema está funcionando
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Teste CapivaraLearn</title></head><body>";
echo "<h1>Teste do Sistema CapivaraLearn</h1>";

// Teste de log
echo "<h2>Teste de Log</h2>";
$logFile = '/opt/lampp/htdocs/CapivaraLearn/logs/test.log';
$message = date('Y-m-d H:i:s') . " - Teste de log realizado\n";
file_put_contents($logFile, $message, FILE_APPEND);

if (file_exists($logFile)) {
    echo "<p>✅ Log criado com sucesso!</p>";
    echo "<p>Conteúdo do log:</p>";
    echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
} else {
    echo "<p>❌ Erro ao criar log</p>";
}

// Teste de conexão com banco
echo "<h2>Teste de Conexão com Banco</h2>";
try {
    require_once '/opt/lampp/htdocs/CapivaraLearn/includes/config.php';
    
    if ($pdo) {
        echo "<p>✅ Conexão com banco estabelecida!</p>";
        
        // Teste de consulta
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM usuarios");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total de usuários: " . $result['count'] . "</p>";
        
        // Teste de log de atividade
        $stmt = $pdo->prepare("INSERT INTO logs_atividade (usuario_id, acao, detalhes, timestamp) VALUES (?, ?, ?, NOW())");
        $stmt->execute([1, 'test', 'Teste de log de atividade']);
        echo "<p>✅ Log de atividade inserido com sucesso!</p>";
        
        // Buscar logs recentes
        $stmt = $pdo->query("SELECT * FROM logs_atividade ORDER BY timestamp DESC LIMIT 5");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($logs) {
            echo "<h3>Logs de Atividade Recentes:</h3>";
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Usuário</th><th>Ação</th><th>Detalhes</th><th>Timestamp</th></tr>";
            foreach ($logs as $log) {
                echo "<tr>";
                echo "<td>" . $log['id'] . "</td>";
                echo "<td>" . $log['usuario_id'] . "</td>";
                echo "<td>" . $log['acao'] . "</td>";
                echo "<td>" . $log['detalhes'] . "</td>";
                echo "<td>" . $log['timestamp'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Nenhum log encontrado</p>";
        }
    } else {
        echo "<p>❌ Erro na conexão com banco</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
