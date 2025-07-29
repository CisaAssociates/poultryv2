<?php
require_once __DIR__ . '/../../../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$farmId = $data['farm_id'] ?? 0;

if (!$farmId) {
    echo json_encode(['success' => false, 'message' => 'Invalid farm ID']);
    exit;
}

try {
    $mysqli = db_connect();
    
    // Get unassigned eggs for this farm
    $stmt = $mysqli->prepare("
        SELECT id, size 
        FROM egg_data 
        WHERE mac IN (SELECT device_mac FROM devices WHERE device_owner_id = ?)
          AND id NOT IN (SELECT egg_id FROM tray_eggs)
    ");
    $stmt->bind_param("i", $farmId);
    $stmt->execute();
    $eggs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Group eggs by size
    $eggGroups = [];
    foreach ($eggs as $egg) {
        $eggGroups[$egg['size']][] = $egg['id'];
    }
    
    // Create or update trays for complete groups (30 eggs)
    $newTrayCount = 0;
    foreach ($eggGroups as $size => $eggIds) {
        $chunks = array_chunk($eggIds, 30);
        foreach ($chunks as $chunk) {
            if (count($chunk) === 30) {
                // Check if pending tray exists for this size
                $checkStmt = $mysqli->prepare("
                    SELECT tray_id FROM trays 
                    WHERE farm_id = ? 
                      AND size = ? 
                      AND status = 'pending'
                    LIMIT 1
                ");
                $checkStmt->bind_param("is", $farmId, $size);
                $checkStmt->execute();
                $existingTray = $checkStmt->get_result()->fetch_assoc();
                
                $mysqli->begin_transaction();
                
                try {
                    if ($existingTray) {
                        $trayId = $existingTray['tray_id'];
                        
                        // Update existing tray stock count
                        $updateStmt = $mysqli->prepare("
                            UPDATE trays 
                            SET stock_count = stock_count + 1 
                            WHERE tray_id = ?
                        ");
                        $updateStmt->bind_param("i", $trayId);
                        $updateStmt->execute();
                    } else {
                        // Create new tray
                        $trayStmt = $mysqli->prepare("
                            INSERT INTO trays (farm_id, device_mac, size, egg_count, stock_count, status)
                            VALUES (?, (SELECT device_mac FROM devices WHERE device_owner_id = ? LIMIT 1), ?, 30, 1, 'pending')
                        ");
                        $trayStmt->bind_param("iis", $farmId, $farmId, $size);
                        $trayStmt->execute();
                        $trayId = $mysqli->insert_id;
                    }
                    
                    // Insert tray eggs
                    $values = [];
                    foreach ($chunk as $eggId) {
                        $values[] = "($trayId, $eggId)";
                    }
                    $valuesStr = implode(',', $values);
                    $mysqli->query("INSERT INTO tray_eggs (tray_id, egg_id) VALUES $valuesStr");
                    
                    $mysqli->commit();
                    $newTrayCount++;
                } catch (Exception $e) {
                    try {
                        $mysqli->rollback();
                    } catch (Exception $rollbackException) {
                        // Transaction wasn't active or rollback failed
                    }
                    error_log("Tray creation/update failed: " . $e->getMessage());
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'new_trays' => $newTrayCount,
        'message' => "Processed $newTrayCount new trays"
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}