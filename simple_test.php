<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting test...\n";

try {
    require_once 'includes/config.php';
    echo "Config loaded successfully\n";
    
    // Test database connection
    global $db;
    if ($db) {
        echo "Database connection exists\n";
        
        // Test a simple query
        $result = $db->select("SELECT 1 as test_value");
        if ($result) {
            echo "Database query successful\n";
            print_r($result);
        } else {
            echo "Database query failed\n";
        }
    } else {
        echo "No database connection\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "Test complete.\n";
?>
