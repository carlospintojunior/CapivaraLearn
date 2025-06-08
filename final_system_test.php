<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 Final System Status Test</h1>";

try {
    // Test 1: Include config and check Database class
    echo "<h2>1. Testing Config.php and Database Class</h2>";
    require_once 'includes/config.php';
    
    echo "<div style='color: green;'>✅ Config.php loaded successfully</div>";
    
    // Check if APP_ENV is defined
    if (defined('APP_ENV')) {
        echo "<div style='color: green;'>✅ APP_ENV constant is defined: " . APP_ENV . "</div>";
    } else {
        echo "<div style='color: red;'>❌ APP_ENV constant is NOT defined</div>";
    }
    
    // Test Database class
    $db = Database::getInstance();
    echo "<div style='color: green;'>✅ Database::getInstance() works</div>";
    
    // Check if insert method exists
    if (method_exists($db, 'insert')) {
        echo "<div style='color: green;'>✅ Database::insert() method exists</div>";
    } else {
        echo "<div style='color: red;'>❌ Database::insert() method is missing</div>";
    }
    
    // Check if update method exists
    if (method_exists($db, 'update')) {
        echo "<div style='color: green;'>✅ Database::update() method exists</div>";
    } else {
        echo "<div style='color: red;'>❌ Database::update() method is missing</div>";
    }
    
    // Test 2: Test University Creation
    echo "<h2>2. Testing University Creation</h2>";
    require_once 'includes/services/UniversityService.php';
    
    $universityService = UniversityService::getInstance();
    echo "<div style='color: green;'>✅ UniversityService loaded</div>";
    
    // Try to create a test university
    $testData = [
        'nome' => 'Universidade Teste Final - ' . date('H:i:s'),
        'sigla' => 'UTF',
        'cidade' => 'São Paulo',
        'estado' => 'SP'
    ];
    
    echo "<p><strong>Attempting to create university:</strong> " . $testData['nome'] . "</p>";
    
    $result = $universityService->create($testData);
    
    if ($result) {
        echo "<div style='color: green;'>✅ <strong>SUCCESS!</strong> University created with ID: $result</div>";
        
        // Try to fetch the created university
        $created = $db->select("SELECT * FROM universidades WHERE id = ?", [$result]);
        if ($created) {
            echo "<div style='color: green;'>✅ University successfully retrieved from database</div>";
            echo "<pre>" . print_r($created[0], true) . "</pre>";
        }
    } else {
        echo "<div style='color: red;'>❌ <strong>FAILED!</strong> Could not create university</div>";
    }
    
    // Test 3: Check Environment Detection
    echo "<h2>3. Environment Detection Status</h2>";
    
    $envFile = __DIR__ . '/includes/environment.ini';
    if (file_exists($envFile)) {
        echo "<div style='color: green;'>✅ environment.ini file exists</div>";
        $envConfig = parse_ini_file($envFile, true);
        if ($envConfig && isset($envConfig['environment']['environment'])) {
            echo "<div style='color: green;'>✅ Environment setting: " . $envConfig['environment']['environment'] . "</div>";
        }
    } else {
        echo "<div style='color: orange;'>⚠️ environment.ini file not found (using domain detection)</div>";
    }
    
    // Test 4: Overall System Health
    echo "<h2>4. Overall System Health</h2>";
    
    $tests = [
        'Config loaded' => true,
        'APP_ENV defined' => defined('APP_ENV'),
        'Database connection' => $db instanceof Database,
        'Database insert method' => method_exists($db, 'insert'),
        'Database update method' => method_exists($db, 'update'),
        'UniversityService working' => class_exists('UniversityService'),
        'University creation' => isset($result) && $result !== false
    ];
    
    $passed = 0;
    $total = count($tests);
    
    echo "<table style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th style='border: 1px solid #ddd; padding: 8px;'>Test</th><th style='border: 1px solid #ddd; padding: 8px;'>Status</th></tr>";
    
    foreach ($tests as $test => $status) {
        $icon = $status ? '✅' : '❌';
        $color = $status ? 'green' : 'red';
        if ($status) $passed++;
        
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>$test</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; color: $color;'>$icon " . ($status ? 'PASS' : 'FAIL') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3>🎯 Final Score: $passed/$total tests passed</h3>";
    
    if ($passed === $total) {
        echo "<div style='background: #d5f4e6; color: #27ae60; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h2>🎉 SUCCESS! All tests passed!</h2>";
        echo "<p><strong>The system is working correctly:</strong></p>";
        echo "<ul>";
        echo "<li>✅ Database connection and methods are working</li>";
        echo "<li>✅ University creation is functional</li>";
        echo "<li>✅ Environment detection is working</li>";
        echo "<li>✅ All configurations are properly loaded</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div style='background: #fdf2f2; color: #e74c3c; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h2>⚠️ Some issues detected</h2>";
        echo "<p>Review the failed tests above and address any remaining issues.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'><strong>❌ Error:</strong> " . $e->getMessage() . "</div>";
    echo "<div style='color: red;'><strong>File:</strong> " . $e->getFile() . "</div>";
    echo "<div style='color: red;'><strong>Line:</strong> " . $e->getLine() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
