<?php
// This file is for testing database connectivity
// Remove this file after testing is complete

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection parameters
$host = 'localhost';
$user = 'slsuisac_poultryv2'; // Update with your production username
$pass = 'poultryv2_password'; // Update with your production password
$db   = 'slsuisac_poultryv2_db'; // Update with your production database name

// Test connection
echo "<h1>Database Connection Test</h1>";

try {
    // Create connection
    $mysqli = new mysqli($host, $user, $pass, $db);
    
    // Check connection
    if ($mysqli->connect_errno) {
        echo "<p style='color:red'>Connection failed: " . $mysqli->connect_error . "</p>";
        echo "<p>Connection details: Host=" . $host . ", User=" . $user . ", DB=" . $db . "</p>";
    } else {
        echo "<p style='color:green'>Connection successful!</p>";
        
        // Test query
        $result = $mysqli->query("SHOW TABLES");
        if ($result) {
            echo "<h2>Tables in database:</h2>";
            echo "<ul>";
            while ($row = $result->fetch_array()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
        }
        
        // Close connection
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Exception: " . $e->getMessage() . "</p>";
}
?>