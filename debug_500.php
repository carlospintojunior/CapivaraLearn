<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log');

echo "<h2>🔍 Debug - Teste de Páginas</h2>";
echo "<p>Data/Hora: " . date('Y-m-d H:i:s') . "</p>";

// Testar se as páginas principais estão funcionando
$pages_to_test = [
    'cleanup.php',
    'install.php', 
    'login.php'
];

foreach ($pages_to_test as $page) {
    echo "<h3>Testando: $page</h3>";
    
    if (file_exists($page)) {
        echo "✅ Arquivo existe<br>";
        
        // Verificar sintaxe PHP
        $syntax_check = shell_exec("php -l $page 2>&1");
        if (strpos($syntax_check, 'No syntax errors') !== false) {
            echo "✅ Sintaxe PHP OK<br>";
        } else {
            echo "❌ Erro de sintaxe PHP: <pre>$syntax_check</pre>";
        }
        
        // Verificar permissões
        $perms = substr(sprintf('%o', fileperms($page)), -4);
        echo "📄 Permissões: $perms<br>";
        
        echo "<a href='$page' target='_blank'>🔗 Testar página</a><br>";
    } else {
        echo "❌ Arquivo não encontrado<br>";
    }
    echo "<hr>";
}

// Verificar se logs estão funcionando
echo "<h3>Teste de Log</h3>";
error_log("=== TESTE DE DEBUG - " . date('Y-m-d H:i:s') . " ===");
error_log("Teste de log manual para debug do erro 500");

echo "✅ Log de teste gravado. Verificar em logs/php_errors.log<br>";

// Verificar configuração PHP
echo "<h3>Configuração PHP</h3>";
echo "display_errors: " . ini_get('display_errors') . "<br>";
echo "log_errors: " . ini_get('log_errors') . "<br>";
echo "error_log: " . ini_get('error_log') . "<br>";
?>
