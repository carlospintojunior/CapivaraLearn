<?php
// Debug da detecção de ambiente
echo "<h1>Debug de Ambiente</h1>";

$envFile = __DIR__ . '/includes/environment.ini';
echo "<strong>Arquivo environment.ini:</strong> " . ($envFile) . "<br>";
echo "<strong>Existe:</strong> " . (file_exists($envFile) ? 'SIM' : 'NÃO') . "<br>";

if (file_exists($envFile)) {
    $config = parse_ini_file($envFile, true);
    echo "<strong>Conteúdo:</strong><pre>";
    print_r($config);
    echo "</pre>";
    
    $isProduction = isset($config['environment']['environment']) && 
                   strtolower($config['environment']['environment']) === 'production';
    echo "<strong>Ambiente detectado via INI:</strong> " . ($isProduction ? 'PRODUÇÃO' : 'DESENVOLVIMENTO') . "<br>";
} else {
    $isProduction = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'capivaralearn.com.br') !== false);
    echo "<strong>Ambiente detectado via domínio:</strong> " . ($isProduction ? 'PRODUÇÃO' : 'DESENVOLVIMENTO') . "<br>";
    echo "<strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'não definido') . "<br>";
}

echo "<strong>Final - É Produção:</strong> " . ($isProduction ? 'SIM' : 'NÃO') . "<br>";
echo "<strong>HTTPS atual:</strong> " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'SIM' : 'NÃO') . "<br>";
echo "<strong>PHP SAPI:</strong> " . php_sapi_name() . "<br>";

// Verificar se config está funcionando
try {
    require_once 'includes/config.php';
    echo "<strong>Config carregado:</strong> SIM<br>";
    echo "<strong>APP_ENV:</strong> " . APP_ENV . "<br>";
    echo "<strong>APP_URL:</strong> " . APP_URL . "<br>";
    echo "<strong>DEBUG_MODE:</strong> " . (DEBUG_MODE ? 'SIM' : 'NÃO') . "<br>";
} catch (Exception $e) {
    echo "<strong>Erro ao carregar config:</strong> " . $e->getMessage() . "<br>";
}
?>
