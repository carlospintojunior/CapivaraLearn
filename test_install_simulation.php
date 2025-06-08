<?php
/**
 * Test the actual install.php generation by simulating a POST request
 */

echo "<h1>Testing Actual Install.php Config Generation</h1>";

// Simulate a form submission to install.php
$postData = array(
    'host' => 'localhost',
    'user' => 'root', 
    'pass' => '',
    'dbname' => 'capivaralearn_test'
);

// Check if we can proceed with installation
try {
    // Connect to test database 
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<div style='color: green;'>✅ MySQL connection successful</div>";
    
    // Create test database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS capivaralearn_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div style='color: green;'>✅ Test database created</div>";
    
    // Now simulate the config generation part from install.php
    echo "<h2>📄 Simulating Config Generation</h2>";
    
    // Set the POST variables to simulate form submission
    $_POST = $postData;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    echo "<p><strong>POST data set:</strong></p>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    echo "<p><a href='install.php' target='_blank' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Open Install.php in New Tab</a></p>";
    
    echo "<hr>";
    echo "<h3>📋 Instructions:</h3>";
    echo "<ol>";
    echo "<li>Click the link above to open install.php</li>";
    echo "<li>Fill the form with:</li>";
    echo "<ul>";
    echo "<li><strong>Host:</strong> localhost</li>";
    echo "<li><strong>User:</strong> root</li>";
    echo "<li><strong>Password:</strong> (leave empty)</li>";
    echo "<li><strong>Database:</strong> capivaralearn_test</li>";
    echo "</ul>";
    echo "<li>Click 'Install CapivaraLearn' and check if the generated config.php includes:</li>";
    echo "<ul>";
    echo "<li>✅ Environment detection logic</li>";
    echo "<li>✅ APP_ENV constant definition</li>";
    echo "<li>✅ Enhanced Database class with insert() and update() methods</li>";
    echo "</ul>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error: " . $e->getMessage() . "</div>";
}
?>
