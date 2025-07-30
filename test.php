<?php
// Display all PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Test database connection
echo "<h1>PHP Test Page</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test database connection
try {
    $conn = db_connect();
    echo "<p>Database connection successful!</p>";
    
    // Test query
    $result = $conn->query("SELECT 1");
    if ($result) {
        echo "<p>Database query successful!</p>";
    }
} catch (Exception $e) {
    echo "<p>Database connection error: " . $e->getMessage() . "</p>";
}

// Display server information
echo "<h2>Server Information:</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";
?>