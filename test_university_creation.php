<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the configuration and service
require_once 'includes/config.php';
require_once 'includes/services/UniversityService.php';

echo "<h2>Testing University Creation</h2>\n";

try {
    // Create UniversityService instance
    $universityService = new UniversityService();
    
    // Test data
    $testUniversityName = 'Test University ' . date('Y-m-d H:i:s');
    $testUniversityDescription = 'This is a test university created to verify the fix.';
    
    echo "<p>Attempting to create university: <strong>$testUniversityName</strong></p>\n";
    
    // Try to create a university
    $result = $universityService->createUniversity($testUniversityName, $testUniversityDescription);
    
    if ($result) {
        echo "<p style='color: green;'><strong>SUCCESS!</strong> University created successfully.</p>\n";
        echo "<p>University ID: $result</p>\n";
        
        // Try to fetch the created university to verify
        $createdUniversity = $universityService->getUniversityById($result);
        if ($createdUniversity) {
            echo "<p>Verification successful. Retrieved university data:</p>\n";
            echo "<ul>\n";
            echo "<li>ID: " . $createdUniversity['id'] . "</li>\n";
            echo "<li>Name: " . $createdUniversity['name'] . "</li>\n";
            echo "<li>Description: " . $createdUniversity['description'] . "</li>\n";
            echo "<li>Created: " . $createdUniversity['created_at'] . "</li>\n";
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
    $universities = $universityService->getAllUniversities();
    if ($universities && count($universities) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
        echo "<tr><th>ID</th><th>Name</th><th>Description</th><th>Created</th></tr>\n";
        foreach ($universities as $uni) {
            echo "<tr>\n";
            echo "<td>" . htmlspecialchars($uni['id']) . "</td>\n";
            echo "<td>" . htmlspecialchars($uni['name']) . "</td>\n";
            echo "<td>" . htmlspecialchars($uni['description']) . "</td>\n";
            echo "<td>" . htmlspecialchars($uni['created_at']) . "</td>\n";
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
