<?php
require_once __DIR__ . '/../../../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$trayId = $data['tray_id'] ?? 0;
$farmId = $data['farm_id'] ?? 0;

if (!$farmId || !$trayId || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $mysqli = db_connect();
    
    $success = false;
    $message = '';
    
    switch ($action) {
        case 'edit':
            $size = $data['size'] ?? '';
            $stockCount = $data['stock_count'] ?? 0;
            
            $stmt = $mysqli->prepare("
                UPDATE trays 
                SET size = ?, stock_count = ? 
                WHERE tray_id = ? AND farm_id = ?
            ");
            $stmt->bind_param("siii", $size, $stockCount, $trayId, $farmId);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $success = true;
                $message = 'Tray updated successfully';
            } else {
                $message = 'Tray not found or no changes made';
            }
            break;
            
        case 'delete':
            $mysqli->begin_transaction();
            
            try {
                // Remove eggs from tray
                $deleteEggsStmt = $mysqli->prepare("
                    DELETE FROM tray_eggs 
                    WHERE tray_id = ?
                ");
                $deleteEggsStmt->bind_param("i", $trayId);
                $deleteEggsStmt->execute();
                
                // Delete tray
                $deleteTrayStmt = $mysqli->prepare("
                    DELETE FROM trays 
                    WHERE tray_id = ? AND farm_id = ?
                ");
                $deleteTrayStmt->bind_param("ii", $trayId, $farmId);
                $deleteTrayStmt->execute();
                
                if ($deleteTrayStmt->affected_rows > 0) {
                    $mysqli->commit();
                    $success = true;
                    $message = 'Tray deleted successfully';
                } else {
                    try {
                        $mysqli->rollback();
                    } catch (Exception $rollbackException) {
                        // Transaction wasn't active or rollback failed
                    }
                    $message = 'Tray not found';
                }
            } catch (Exception $e) {
                try {
                    $mysqli->rollback();
                } catch (Exception $rollbackException) {
                    // Transaction wasn't active or rollback failed
                }
                throw $e;
            }
            break;
            
        case 'mark_sold':
            $stmt = $mysqli->prepare("
                UPDATE trays 
                SET status = 'sold', sold_at = NOW() 
                WHERE tray_id = ? AND farm_id = ?
            ");
            $stmt->bind_param("ii", $trayId, $farmId);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $success = true;
                $message = 'Tray marked as sold';
            } else {
                $message = 'Tray not found';
            }
            break;
            
        case 'mark_expired':
            $stmt = $mysqli->prepare("
                UPDATE trays 
                SET status = 'expired', expired_at = NOW() 
                WHERE tray_id = ? AND farm_id = ?
            ");
            $stmt->bind_param("ii", $trayId, $farmId);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $success = true;
                $message = 'Tray marked as expired';
            } else {
                $message = 'Tray not found';
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
    
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}