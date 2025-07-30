<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include configuration
try {
    require_once __DIR__ . '/config.php';
    
    // Test database connection
    $conn = db_connect();
    
    // If everything is working, redirect to auth page
    header('Location: ' . view('auth.index'));
    exit;
} catch (Exception $e) {
    // Display error message
    echo '<h1>Error</h1>';
    echo '<p>Error message: ' . $e->getMessage() . '</p>';
    echo '<p>Please check your database connection and server configuration.</p>';
}
?>