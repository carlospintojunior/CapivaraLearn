<?php
// Teste direto do environment.ini
$envFile = '/opt/lampp/htdocs/CapivaraLearn/includes/environment.ini';

echo "<h1>Debug Environment.ini</h1>";
echo "File: " . $envFile . "<br>";
echo "Exists: " . (file_exists($envFile) ? 'YES' : 'NO') . "<br>";

if (file_exists($envFile)) {
    echo "Readable: " . (is_readable($envFile) ? 'YES' : 'NO') . "<br>";
    
    $content = file_get_contents($envFile);
    echo "Content length: " . strlen($content) . "<br>";
    echo "Raw content:<pre>" . htmlspecialchars($content) . "</pre>";
    
    $config = parse_ini_file($envFile, true);
    echo "Parse result: " . (is_array($config) ? 'SUCCESS' : 'FAILED') . "<br>";
    
    if (is_array($config)) {
        echo "Parsed config:<pre>";
        print_r($config);
        echo "</pre>";
        
        $envSetting = $config['environment']['environment'] ?? 'NOT_FOUND';
        echo "Environment setting: '" . $envSetting . "'<br>";
        echo "Lowercased: '" . strtolower($envSetting) . "'<br>";
        echo "Is production: " . (strtolower($envSetting) === 'production' ? 'YES' : 'NO') . "<br>";
    }
}
?>
