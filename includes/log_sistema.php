<?php
// Função global de log para o sistema CapivaraLearn
function log_sistema($mensagem, $level = 'INFO', $arquivo = 'sistema.log') {
    $logFile = __DIR__ . '/../logs/' . $arquivo;
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $linha = "[$timestamp] $level | $mensagem\n";
    file_put_contents($logFile, $linha, FILE_APPEND | LOCK_EX);
}
?>
