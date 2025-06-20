<?php
echo "<h1>🧪 Teste Final - Método insert() adicionado</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; background: #e8f5e9; padding: 10px; margin: 5px 0; border-radius: 5px; }
.error { color: red; background: #ffebee; padding: 10px; margin: 5px 0; border-radius: 5px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";

try {
    require_once '/opt/lampp/htdocs/CapivaraLearn/includes/config.php';
    
    echo "<div class='success'>✅ Config.php carregado</div>";
    
    $db = Database::getInstance();
    echo "<div class='success'>✅ Database::getInstance() funcionando</div>";
    
    $methods = get_class_methods($db);
    echo "<div class='success'>📋 Métodos disponíveis: " . implode(', ', $methods) . "</div>";
    
    if (method_exists($db, 'insert')) {
        echo "<div class='success'>✅ Método insert() EXISTE!</div>";
    } else {
        echo "<div class='error'>❌ Método insert() NÃO existe</div>";
    }
    
    // Testar UniversityService
    require_once '/opt/lampp/htdocs/CapivaraLearn/includes/services/UniversityService.php';
    
    $service = UniversityService::getInstance();
    echo "<div class='success'>✅ UniversityService carregado</div>";
    
    // Teste real de inserção
    $testData = [
        'nome' => 'Universidade Teste Final - ' . date('H:i:s'),
        'sigla' => 'UTF' . rand(10, 99),
        'cidade' => 'São Paulo',
        'estado' => 'SP'
    ];
    
    echo "<div class='success'>🔄 Tentando criar universidade: " . $testData['nome'] . "</div>";
    
    $result = $service->create($testData);
    
    if ($result) {
        echo "<div class='success'>🎉 SUCESSO! Universidade criada com ID: " . $result . "</div>";
        echo "<div class='success'>✅ PROBLEMA RESOLVIDO DEFINITIVAMENTE!</div>";
    } else {
        echo "<div class='error'>❌ Falha na criação</div>";
    }
    
} catch (Error $e) {
    echo "<div class='error'>❌ ERRO FATAL: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Exceção: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
