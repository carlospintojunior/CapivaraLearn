<?php
echo "Debug Test - Environment Detection<br>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'não definido') . "<br>";

$envFile = __DIR__ . '/includes/environment.ini';
echo "Environment file exists: " . (file_exists($envFile) ? 'YES' : 'NO') . "<br>";

if (file_exists($envFile)) {
    $config = parse_ini_file($envFile, true);
    echo "Environment setting: " . ($config['environment']['environment'] ?? 'not found') . "<br>";
}
?>
