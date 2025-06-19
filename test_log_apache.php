<?php
$logFile = __DIR__ . '/logs/mailservice.log';
$logDir = dirname($logFile);

if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

$mensagem = "Teste de log via Apache em " . date('Y-m-d H:i:s') . "\n";
$result = @file_put_contents($logFile, $mensagem, FILE_APPEND | LOCK_EX);

if ($result !== false) {
    echo "✅ Log gravado com sucesso!<br>";
    echo "Conteúdo atual:<br><pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
} else {
    echo "❌ Falha ao gravar log!<br>";
    echo "Permissões do diretório: " . decoct(fileperms($logDir) & 0777) . "<br>";
    if (file_exists($logFile)) {
        echo "Permissões do arquivo: " . decoct(fileperms($logFile) & 0777) . "<br>";
    }
}
?>
