<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the configuration and service
require_once 'includes/config.php';
require_once 'includes/services/UniversityService.php';

echo "<h2>Testing University Creation (Corrected)</h2>\n";

try {
    // Create UniversityService instance
    $universityService = new UniversityService();
    
    // Test data using correct Portuguese field names
    $testUniversityData = [
        'nome' => 'Universidade Teste ' . date('Y-m-d H:i:s'),
        'sigla' => 'UT',
        'cidade' => 'São Paulo',
        'estado' => 'SP'
    ];
    
    echo "<p>Attempting to create university: <strong>" . $testUniversityData['nome'] . "</strong></p>\n";
    
    // Try to create a university using correct method name
    $result = $universityService->create($testUniversityData);
    
    if ($result) {
        echo "<p style='color: green;'><strong>SUCCESS!</strong> University created successfully.</p>\n";
        echo "<p>University ID: $result</p>\n";
        
        // Try to fetch the created university to verify using correct method name
        $createdUniversity = $universityService->getById($result);
        if ($createdUniversity) {
            echo "<p>Verification successful. Retrieved university data:</p>\n";
            echo "<ul>\n";
            echo "<li>ID: " . $createdUniversity['id'] . "</li>\n";
            echo "<li>Nome: " . $createdUniversity['nome'] . "</li>\n";
            echo "<li>Sigla: " . $createdUniversity['sigla'] . "</li>\n";
            echo "<li>Cidade: " . $createdUniversity['cidade'] . "</li>\n";
            echo "<li>Estado: " . $createdUniversity['estado'] . "</li>\n";
            echo "<li>Data Cadastro: " . $createdUniversity['data_cadastro'] . "</li>\n";
            echo "</ul>\n";
        }
        
    } else {
        echo "<p style='color: red;'><strong>FAILED!</strong> University creation returned false.</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . $e->getMessage() . "</p>\n";
    echo "<p>File: " . $e->getFile() . "</p>\n";
    echo "<p>Line: " . $e->getLine() . "</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<hr>\n";
echo "<h3>Current Universities in Database:</h3>\n";

try {
    // Use correct method name
    $universities = $universityService->listAll();
    if ($universities && count($universities) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
        echo "<tr><th>ID</th><th>Nome</th><th>Sigla</th><th>Cidade</th><th>Estado</th><th>Data Cadastro</th></tr>\n";
        foreach ($universities as $uni) {
            echo "<tr>\n";
            echo "<td>" . htmlspecialchars($uni['id']) . "</td>\n";
            echo "<td>" . htmlspecialchars($uni['nome']) . "</td>\n";
            echo "<td>" . htmlspecialchars($uni['sigla']) . "</td>\n";
            echo "<td>" . htmlspecialchars($uni['cidade']) . "</td>\n";
            echo "<td>" . htmlspecialchars($uni['estado']) . "</td>\n";
            echo "<td>" . htmlspecialchars($uni['data_cadastro']) . "</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>No universities found in database.</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error fetching universities: " . $e->getMessage() . "</p>\n";
}
?>
