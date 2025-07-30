<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

function base_url($path = '')
{
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    $baseDir = str_replace('\\', '/', realpath(__DIR__));
    $base = str_replace($docRoot, '', $baseDir);
    if ($base === '' || $base === false) $base = '/';
    if (substr($base, -1) !== '/') $base .= '/';
    return $base . ltrim($path, '/');
}

function special_chars($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function asset($path = '')
{
    return base_url('assets/' . ltrim($path, '/'));
}

function view($path = '')
{
    return base_url(str_replace('.', '/', $path) . '.php');
}

function partial($path = '')
{
    return __DIR__ . '/partials/' . ltrim(str_replace('.', '/', $path), '/') . '.php';
}

function layouts($path = '')
{
    return __DIR__ . '/layouts/' . ltrim(str_replace('.', '/', $path), '/') . '.php';
}

function verify_token($token)
{
    return isset($_SESSION['token']) && hash_equals($_SESSION['token'], $token);
}

function is_logged_in()
{
    return isset($_SESSION['id'], $_SESSION['role'], $_SESSION['last_login']);
}

function redirect($path)
{
    header('Location: ' . view($path));
    exit;
}

if (isset($_POST['logout'])) {
    if (!verify_token($_POST['token'])) {
        $_SESSION['error-page'] = true;
        header('Location: ' . view('auth.error.500'));
        exit;
    }

    session_unset();
    session_destroy();

    $_SESSION['logout'] = true;

    header('Location: ' . view('auth.logout'));
    exit;
}

function redirect_based_on_role($role)
{
    $map = [
        '1' => view('admin.index'),
        '2' => view('owner.index'),
        '3' => view('employee.index'),
        '4' => view('consumer.index')
    ];

    $validRoles = ['1', '2', '3', '4'];

    if (in_array($role, $validRoles)) {
        header('Location: ' . $map[$role]);
    } else {
        $_SESSION['error-page'] = true;
        header('Location: ' . view('auth.error.403'));
    }
    exit;
}

function validate_farm_access($farm_id)
{
    global $user;

    if ($user['role_id'] == 1) { // Admin - access to all farms
        return true;
    }

    if ($user['role_id'] == 2) { // Owner
        $mysqli = db_connect();
        $stmt = $mysqli->prepare("SELECT farm_id FROM farms WHERE farm_id = ? AND owner_id = ?");
        $stmt->bind_param("ii", $farm_id, $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    if ($user['role_id'] == 3) { // Employee - access to their assigned farm
        return isset($user['farm_id']) && $user['farm_id'] == $farm_id;
    }

    return false;
}

function db_connect()
{
    static $mysqli = null;

    if ($mysqli === null) {
        // Check if we're in production environment (on the server)
        if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'poultryv2.slsuisa.com') {
            // Production database credentials
            $host = 'localhost'; // Update this with your actual production database host
            $user = 'u347279731_poultryv2'; // Update this with your actual production database username
            $pass = 'Poultyv2025'; // Update this with your actual production database password
            $db   = 'u347279731_poultryv2_db'; // Update this with your actual production database name
        } else {
            // Local development database credentials
            $host = 'localhost';
            $user = 'root';
            $pass = '';
            $db   = 'poultryv2_db';
        }

        $mysqli = new mysqli($host, $user, $pass, $db);

        if ($mysqli->connect_errno) {
            error_log('Database connection failed: ' . $mysqli->connect_error);
            
            // More detailed error handling for debugging
            if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'poultryv2.slsuisa.com') {
                // In production, log more details but show generic message to users
                error_log('Connection details: Host=' . $host . ', User=' . $user . ', DB=' . $db);
                error_log('Server info: ' . json_encode($_SERVER, JSON_UNESCAPED_SLASHES));
                die('Database connection error. Please contact the administrator.');
            } else {
                // In development, show detailed error
                die('Database connection error: ' . $mysqli->connect_error);
            }
        }

        $mysqli->set_charset('utf8mb4');
        $mysqli->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
    }

    return $mysqli;
}

if (is_logged_in()) {
    function getData($id)
    {
        $mysqli = db_connect();
        $stmt = $mysqli->prepare("
            SELECT u.id, u.role_id, u.fullname, u.email, r.role_name, 
                et.type_name, e.farm_id, e.employee_id, f.farm_name
            FROM users u
            INNER JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN employees e ON u.id = e.user_id
            LEFT JOIN employee_types et ON e.type_id = et.type_id
            LEFT JOIN farms f ON e.farm_id = f.farm_id
            WHERE u.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            
            // Get all farms for owner
            if ($user_data['role_id'] == 2) { // Owner role
                $farms_stmt = $mysqli->prepare("SELECT farm_id, farm_name FROM farms WHERE owner_id = ? ORDER BY farm_name");
                $farms_stmt->bind_param("i", $id);
                $farms_stmt->execute();
                $farms_result = $farms_stmt->get_result();
                $user_data['farms'] = $farms_result->fetch_all(MYSQLI_ASSOC);
                
                // Get selected farm or default to first farm
                if (isset($_SESSION['selected_farm_id'])) {
                    $selected_farm_id = $_SESSION['selected_farm_id'];
                    
                    // Verify the farm belongs to this owner
                    $farm_check = false;
                    foreach ($user_data['farms'] as $farm) {
                        if ($farm['farm_id'] == $selected_farm_id) {
                            $farm_check = true;
                            $user_data['owner_farm_id'] = $farm['farm_id'];
                            $user_data['owner_farm_name'] = $farm['farm_name'];
                            break;
                        }
                    }
                    
                    // If selected farm doesn't belong to owner, reset to first farm
                    if (!$farm_check && !empty($user_data['farms'])) {
                        $user_data['owner_farm_id'] = $user_data['farms'][0]['farm_id'];
                        $user_data['owner_farm_name'] = $user_data['farms'][0]['farm_name'];
                        $_SESSION['selected_farm_id'] = $user_data['owner_farm_id'];
                    }
                } else if (!empty($user_data['farms'])) {
                    // Default to first farm if none selected
                    $user_data['owner_farm_id'] = $user_data['farms'][0]['farm_id'];
                    $user_data['owner_farm_name'] = $user_data['farms'][0]['farm_name'];
                    $_SESSION['selected_farm_id'] = $user_data['owner_farm_id'];
                } else {
                    // No farms yet
                    $user_data['owner_farm_id'] = null;
                    $user_data['owner_farm_name'] = null;
                }
            }
            
            return $user_data;
        }
        return null;
    }

    $user = getData($_SESSION['id']);

    if ($user === null) {
        session_unset();
        session_destroy();

        $_SESSION['error-page'] = true;
        header('Location: ' . view('auth.error.403'));
        exit;
    }
}

function get_loyalty_tier($user_id) {
    $mysqli = db_connect();
    
    // Get user's loyalty points
    $stmt = $mysqli->prepare("SELECT points FROM consumer_loyalty WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $loyalty = $result->fetch_assoc();
    $points = $loyalty ? $loyalty['points'] : 0;
    
    // Get user's tier based on points
    $stmt = $mysqli->prepare("SELECT tier_name FROM loyalty_tiers WHERE points_required <= ? ORDER BY points_required DESC LIMIT 1");
    $stmt->bind_param("i", $points);
    $stmt->execute();
    $result = $stmt->get_result();
    $tier = $result->fetch_assoc();
    
    return $tier ? strtolower($tier['tier_name']) : 'bronze';
}

/**
 * Generate a CSRF token hidden input field for form protection
 * 
 * @return string HTML hidden input field with CSRF token
 */
function csrf_token() {
    return '<input type="hidden" id="token" name="token" value="' . $_SESSION['token'] . '">';
}

/**
 * Get current logged in user data
 * 
 * @return array User data including ID, role, and other information
 */
function get_user_data() {
    global $user;
    
    if (!isset($user) || !is_array($user)) {
        return [
            'user_id' => $_SESSION['id'] ?? 0,
            'role_id' => $_SESSION['role'] ?? 0
        ];
    }
    
    return [
        'user_id' => $user['id'] ?? $_SESSION['id'] ?? 0,
        'role_id' => $user['role_id'] ?? $_SESSION['role'] ?? 0
    ];
}

/**
 * Get database connection
 * 
 * @return mysqli Database connection object
 */
function get_database_connection() {
    return db_connect();
}