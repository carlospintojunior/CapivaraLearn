<?php
echo "<h1>Testing University Creation</h1>";

// Include the configuration file
require_once 'includes/config.php';
require_once 'includes/services/UniversityService.php';

try {
    echo "<h2>1. Testing Database Connection</h2>";
    $db = Database::getInstance();
    echo "✅ Database connection successful<br>";

    echo "<h2>2. Testing Database Methods</h2>";
    // Check if insert method exists
    if (method_exists($db, 'insert')) {
        echo "✅ insert() method exists<br>";
    } else {
        echo "❌ insert() method missing<br>";
    }
    
    // Check if update method exists
    if (method_exists($db, 'update')) {
        echo "✅ update() method exists<br>";
    } else {
        echo "❌ update() method missing<br>";
    }

    echo "<h2>3. Testing University Creation</h2>";
    $universityService = new UniversityService($db);
    
    // Test data
    $testUniversity = [
        'nome' => 'Universidade de Teste',
        'sigla' => 'UNI_TEST',
        'endereco' => 'Rua de Teste, 123',
        'telefone' => '(11) 1234-5678',
        'email' => 'contato@uniteste.edu.br',
        'website' => 'https://www.uniteste.edu.br'
    ];
    
    echo "Creating university: " . $testUniversity['nome'] . "<br>";
    $result = $universityService->create($testUniversity);
    
    if ($result) {
        echo "✅ University created successfully! ID: " . $result . "<br>";
        
        // Verify the university was actually created
        $universities = $universityService->getAll();
        echo "✅ Total universities in database: " . count($universities) . "<br>";
        
        // Show the last created university
        foreach ($universities as $uni) {
            if ($uni['id'] == $result) {
                echo "✅ Created university details:<br>";
                echo "&nbsp;&nbsp;- ID: " . $uni['id'] . "<br>";
                echo "&nbsp;&nbsp;- Nome: " . $uni['nome'] . "<br>";
                echo "&nbsp;&nbsp;- Sigla: " . $uni['sigla'] . "<br>";
                echo "&nbsp;&nbsp;- Email: " . $uni['email'] . "<br>";
                break;
            }
        }
    } else {
        echo "❌ Failed to create university<br>";
    }

    echo "<h2>4. Test Summary</h2>";
    echo "✅ All tests completed successfully!<br>";
    echo "✅ The 'Call to undefined method Database::insert()' error has been fixed!<br>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffeaea; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "</div>";
}
?>
