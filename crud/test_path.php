<?php
// Arquivo de teste para diagnosticar problemas de inclusão e definição de constantes.

$configPath = __DIR__ . '/../includes/config.php';
echo "<h1>Diagnóstico de Inclusão</h1>";
echo "<p><strong>Arquivo de Teste:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Valor de __DIR__:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Caminho para config.php:</strong> " . $configPath . "</p>";
echo "<hr>";

if (file_exists($configPath)) {
    echo "<p style='color:green;'><strong>SUCESSO:</strong> O arquivo 'config.php' foi encontrado no caminho especificado.</p>";
    
    // Tenta incluir o arquivo
    require_once $configPath;
    
    echo "<p>Verificando constantes após a inclusão...</p>";
    
    if (defined('DB_HOST')) {
        echo "<p style='color:green;'><strong>SUCESSO:</strong> A constante 'DB_HOST' está definida.</p>";
        echo "<p><strong>Valor:</strong> " . DB_HOST . "</p>";
    } else {
        echo "<p style='color:red;'><strong>FALHA:</strong> A constante 'DB_HOST' NÃO foi definida após a inclusão do 'config.php'.</p>";
    }
    
    if (defined('DB_NAME')) {
        echo "<p style='color:green;'><strong>SUCESSO:</strong> A constante 'DB_NAME' está definida.</p>";
        echo "<p><strong>Valor:</strong> " . DB_NAME . "</p>";
    } else {
        echo "<p style='color:red;'><strong>FALHA:</strong> A constante 'DB_NAME' NÃO foi definida.</p>";
    }

} else {
    echo "<p style='color:red;'><strong>FALHA CRÍTICA:</strong> O arquivo 'config.php' NÃO foi encontrado no caminho especificado.</p>";
}
?>
