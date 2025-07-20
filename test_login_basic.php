<?php
echo "Testing basic PHP...";

// Teste 1: sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo " Session OK...";

// Teste 2: config básico
try {
    require_once __DIR__ . '/includes/config.php';
    echo " Config loaded OK...";
} catch (Exception $e) {
    echo " Error loading config: " . $e->getMessage();
    exit;
}

echo " All tests passed!";
?>
