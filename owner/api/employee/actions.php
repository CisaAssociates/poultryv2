<?php
require_once __DIR__ . '/../../../config.php';

if (!is_logged_in() || $_SESSION['role'] != 2) {
    header('Location: ' . view('auth.login'));
    exit;
}

if (!verify_token($_POST['token'])) {
    $_SESSION['error'] = 'Invalid security token';
    header('Location: ' . view('owner.employees'));
    exit;
}

$conn = db_connect();
$action = $_POST['action'] ?? '';
$owner_id = $_SESSION['id'];

try {
    switch ($action) {
        case 'add':
            // Validate inputs
            $required = ['fullname', 'email', 'password', 'farm_id', 'type_id', 'hire_date'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("$field is required");
                }
            }
            
            // Check if farm belongs to owner
            $farm_id = intval($_POST['farm_id']);
            $checkFarm = $conn->prepare("SELECT farm_id FROM farms WHERE farm_id = ? AND owner_id = ?");
            $checkFarm->bind_param('ii', $farm_id, $owner_id);
            $checkFarm->execute();
            if (!$checkFarm->get_result()->num_rows) {
                throw new Exception("Invalid farm selection");
            }
            
            // Create new user
            $fullname = special_chars($_POST['fullname']);
            $email = special_chars($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role_id = 3; // Default to farmer role
            
            $userStmt = $conn->prepare(
                "INSERT INTO users (fullname, email, password, role_id) 
                 VALUES (?, ?, ?, ?)"
            );
            $userStmt->bind_param('sssi', $fullname, $email, $password, $role_id);
            $userStmt->execute();

            $user_id = $conn->insert_id;
            
            if (!$user_id) {
                throw new Exception("Failed to create user account");
            }
            
            // Create employee record
            $type_id = intval($_POST['type_id']);
            $hire_date = $_POST['hire_date'];
            $salary = !empty($_POST['salary']) ? floatval($_POST['salary']) : null;
            
            $employeeStmt = $conn->prepare(
                "INSERT INTO employees (user_id, farm_id, type_id, hire_date, salary) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $employeeStmt->bind_param('iiisd', $user_id, $farm_id, $type_id, $hire_date, $salary);
            $employeeStmt->execute();
            
            $_SESSION['success'] = 'Employee added successfully';
            break;
            
        case 'update':
            $required = ['employee_id', 'farm_id', 'type_id', 'status'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("$field is required");
                }
            }
            
            $employee_id = intval($_POST['employee_id']);
            $farm_id = intval($_POST['farm_id']);
            $type_id = intval($_POST['type_id']);
            $status = $_POST['status'];
            $salary = !empty($_POST['salary']) ? floatval($_POST['salary']) : null;
            
            // Verify employee belongs to owner
            $checkEmployee = $conn->prepare(
                "SELECT e.employee_id 
                 FROM employees e
                 JOIN farms f ON e.farm_id = f.farm_id
                 WHERE e.employee_id = ? AND f.owner_id = ?"
            );
            $checkEmployee->bind_param('ii', $employee_id, $owner_id);
            $checkEmployee->execute();
            if (!$checkEmployee->get_result()->num_rows) {
                throw new Exception("Employee not found");
            }
            
            // Verify farm belongs to owner
            $checkFarm = $conn->prepare("SELECT farm_id FROM farms WHERE farm_id = ? AND owner_id = ?");
            $checkFarm->bind_param('ii', $farm_id, $owner_id);
            $checkFarm->execute();
            if (!$checkFarm->get_result()->num_rows) {
                throw new Exception("Invalid farm selection");
            }
            
            // Update employee record
            $updateStmt = $conn->prepare(
                "UPDATE employees 
                 SET farm_id = ?, type_id = ?, status = ?, salary = ?
                 WHERE employee_id = ?"
            );
            $updateStmt->bind_param('iissi', $farm_id, $type_id, $status, $salary, $employee_id);
            $updateStmt->execute();
            
            $_SESSION['success'] = 'Employee updated successfully';
            break;
            
        case 'delete':
            if (empty($_POST['employee_id'])) {
                throw new Exception("Employee ID is required");
            }
            
            $employee_id = intval($_POST['employee_id']);
            
            // Verify employee belongs to owner
            $checkEmployee = $conn->prepare(
                "SELECT e.employee_id 
                 FROM employees e
                 JOIN farms f ON e.farm_id = f.farm_id
                 WHERE e.employee_id = ? AND f.owner_id = ?"
            );
            $checkEmployee->bind_param('ii', $employee_id, $owner_id);
            $checkEmployee->execute();
            if (!$checkEmployee->get_result()->num_rows) {
                throw new Exception("Employee not found");
            }
            
            // Delete employee record (user record remains)
            $deleteStmt = $conn->prepare("DELETE FROM employees WHERE employee_id = ?");
            $deleteStmt->bind_param('i', $employee_id);
            $deleteStmt->execute();
            
            $_SESSION['success'] = 'Employee removed successfully';
            break;
            
        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

$conn->close();
header('Location: ' . view('owner.employees'));
exit;
?>