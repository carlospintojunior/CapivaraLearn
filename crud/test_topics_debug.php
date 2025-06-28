<?php
echo "Testando tópicos...\n";
try {
    require_once __DIR__ . '/includes/crud_header.php';
    echo "Header carregado com sucesso\n";
    
    require_once __DIR__ . '/includes/medoo_config.php';
    echo "Medoo config carregado com sucesso\n";
    
    // Testar conexão
    $test = $database->info();
    echo "Conexão OK: " . json_encode($test) . "\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>
