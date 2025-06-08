<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting basic test...\n";

try {
    require_once 'includes/config.php';
    echo "Config loaded.\n";
    
    // Test database connection
    global $db;
    if ($db) {
        echo "Database connection exists.\n";
        
        // Test basic query
        $result = $db->select("SELECT COUNT(*) as total FROM universidades");
        if ($result) {
            echo "Query successful. Universities count: " . $result[0]['total'] . "\n";
        } else {
            echo "Query returned no result.\n";
        }
    } else {
        echo "No database connection.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n"; 
    echo "Line: " . $e->getLine() . "\n";
}

echo "Test complete.\n";
?>
