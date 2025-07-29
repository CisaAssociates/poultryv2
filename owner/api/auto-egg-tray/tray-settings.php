<?php
require_once __DIR__ . '/../../../config.php';
header('Content-Type: application/json');

$farmId = $_GET['farm_id'] ?? 0;

if (!$farmId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid farm ID']);
    exit;
}

try {
    $mysqli = db_connect();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get settings
        $stmt = $mysqli->prepare("
            SELECT * 
            FROM tray_settings 
            WHERE farm_id = ?
        ");
        $stmt->bind_param("i", $farmId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['size']] = $row;
        }
        
        echo json_encode($settings);
    } 
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Save settings
        $data = json_decode(file_get_contents('php://input'), true);
        $settings = $data['settings'] ?? [];
        
        if (empty($settings)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }
        
        $mysqli->begin_transaction();
        
        try {
            // Delete existing settings
            $deleteStmt = $mysqli->prepare("DELETE FROM tray_settings WHERE farm_id = ?");
            $deleteStmt->bind_param("i", $farmId);
            $deleteStmt->execute();
            
            // Insert new settings
            foreach ($settings as $size => $config) {
                $insertStmt = $mysqli->prepare("
                    INSERT INTO tray_settings (farm_id, size, default_price, auto_publish)
                    VALUES (?, ?, ?, ?)
                ");
                $autoPublish = $config['auto_publish'] ? 1 : 0;
                $insertStmt->bind_param("isdi", $farmId, $size, $config['price'], $autoPublish);
                $insertStmt->execute();
            }
            
            $mysqli->commit();
            echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
        } catch (Exception $e) {
            try {
                $mysqli->rollback();
            } catch (Exception $rollbackException) {
                // Transaction wasn't active or rollback failed
            }
            throw $e;
        }
    }
    else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}