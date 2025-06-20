<?php
// Teste isolado sem includes
echo "Starting environment test...<br>";

// Reproduzir exatamente a lógica do config.php
$envFile = __DIR__ . '/includes/environment.ini';
$config = null;

echo "Looking for: " . $envFile . "<br>";

if (file_exists($envFile)) {
    echo "File exists, parsing...<br>";
    $config = parse_ini_file($envFile, true);
    
    if ($config === false) {
        echo "ERROR: Failed to parse INI file<br>";
    } else {
        echo "INI parsed successfully<br>";
        echo "Config content:<pre>";
        print_r($config);
        echo "</pre>";
        
        $isProduction = isset($config['environment']['environment']) && 
                       strtolower($config['environment']['environment']) === 'production';
        echo "Production detected: " . ($isProduction ? 'YES' : 'NO') . "<br>";
    }
} else {
    echo "File does not exist, using domain detection<br>";
    $isProduction = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'capivaralearn.com.br') !== false);
    echo "Domain production: " . ($isProduction ? 'YES' : 'NO') . "<br>";
}

echo "Final result: isProduction = " . ($isProduction ? 'TRUE' : 'FALSE') . "<br>";
echo "Test completed successfully!<br>";
?>
