<?php
// Teste específico da lógica de ambiente
$envFile = __DIR__ . '/includes/environment.ini';
echo "Environment file: " . $envFile . "<br>";
echo "File exists: " . (file_exists($envFile) ? 'YES' : 'NO') . "<br>";

if (file_exists($envFile)) {
    $config = parse_ini_file($envFile, true);
    echo "Config loaded: " . (is_array($config) ? 'YES' : 'NO') . "<br>";
    echo "Environment value: " . ($config['environment']['environment'] ?? 'NOT FOUND') . "<br>";
    
    $isProduction = isset($config['environment']['environment']) && 
                   strtolower($config['environment']['environment']) === 'production';
    echo "Is Production (via INI): " . ($isProduction ? 'YES' : 'NO') . "<br>";
} else {
    echo "Using domain detection<br>";
    $isProduction = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'capivaralearn.com.br') !== false);
    echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "<br>";
    echo "Is Production (via domain): " . ($isProduction ? 'YES' : 'NO') . "<br>";
}

echo "Final isProduction: " . ($isProduction ? 'YES' : 'NO') . "<br>";
echo "PHP SAPI: " . php_sapi_name() . "<br>";
echo "HTTPS set: " . (isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : 'NOT SET') . "<br>";

// Simular a lógica de redirecionamento
if ($isProduction) {
    echo "PRODUCTION MODE - Would force HTTPS<br>";
    if (php_sapi_name() !== 'cli') {
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            echo "REDIRECT WOULD HAPPEN TO HTTPS<br>";
        } else {
            echo "ALREADY ON HTTPS - OK<br>";
        }
    }
} else {
    echo "DEVELOPMENT MODE - No HTTPS forcing<br>";
}
?>
