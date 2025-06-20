<?php
// Teste sem acessar includes
echo "Test without includes access<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Include path: " . get_include_path() . "<br>";

// Testar se conseguimos listar o diretório includes
$includesDir = __DIR__ . '/includes';
echo "Includes dir: " . $includesDir . "<br>";
echo "Includes dir exists: " . (is_dir($includesDir) ? 'YES' : 'NO') . "<br>";
echo "Includes dir readable: " . (is_readable($includesDir) ? 'YES' : 'NO') . "<br>";

if (is_dir($includesDir)) {
    $files = scandir($includesDir);
    echo "Files in includes: " . implode(', ', $files) . "<br>";
}

echo "Test completed<br>";
?>
