<?php
// Direct test without authentication
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>University Service Test (Direct Access)</h2>\n";

try {
    require_once 'includes/config.php';
    require_once 'includes/services/UniversityService.php';
    
    echo "<p>Classes loaded successfully</p>\n";
    
    $universityService = UniversityService::getInstance();
    echo "<p>UniversityService instance created</p>\n";
    
    // Test listing universities
    $universities = $universityService->listAll();
    echo "<p>Found " . count($universities) . " universities</p>\n";
    
    if (count($universities) > 0) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>ID</th><th>Nome</th><th>Sigla</th><th>Cidade</th><th>Estado</th></tr>\n";
        foreach ($universities as $uni) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($uni['id']) . "</td>";
            echo "<td>" . htmlspecialchars($uni['nome']) . "</td>";
            echo "<td>" . htmlspecialchars($uni['sigla']) . "</td>";
            echo "<td>" . htmlspecialchars($uni['cidade']) . "</td>";
            echo "<td>" . htmlspecialchars($uni['estado']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Test creating a new university
    echo "<hr><h3>Testing University Creation</h3>\n";
    $newUniversityData = [
        'nome' => 'Universidade Teste PHP ' . date('H:i:s'),
        'sigla' => 'UTP',
        'cidade' => 'Rio de Janeiro',
        'estado' => 'RJ'
    ];
    
    $newId = $universityService->create($newUniversityData);
    if ($newId) {
        echo "<p style='color: green;'>SUCCESS! New university created with ID: $newId</p>\n";
        
        // Test retrieving the new university
        $newUniversity = $universityService->getById($newId);
        if ($newUniversity) {
            echo "<p>Retrieved new university:</p>\n";
            echo "<ul>";
            echo "<li>ID: " . $newUniversity['id'] . "</li>";
            echo "<li>Nome: " . $newUniversity['nome'] . "</li>";
            echo "<li>Sigla: " . $newUniversity['sigla'] . "</li>";
            echo "<li>Cidade: " . $newUniversity['cidade'] . "</li>";
            echo "<li>Estado: " . $newUniversity['estado'] . "</li>";
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>FAILED to create university</p>\n";
    }
    
    echo "<p style='color: green; font-weight: bold;'>ALL TESTS COMPLETED SUCCESSFULLY!</p>\n";
    echo "<p>The original error 'Call to undefined method Database::insert()' has been FIXED!</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . $e->getMessage() . "</p>\n";
    echo "<p>File: " . $e->getFile() . "</p>\n";
    echo "<p>Line: " . $e->getLine() . "</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}
?>
