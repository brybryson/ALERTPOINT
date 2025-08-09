<?php
session_start();
require_once '../../config/database.php';

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Function to log admin activity
function logAdminActivity($pdo, $admin_id, $previous_last_active, $ip_address, $user_agent, $login_status = 'success') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, previous_last_active, ip_address, user_agent, login_status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$admin_id, $previous_last_active, $ip_address, $user_agent, $login_status]);
        return true;
    } catch (Exception $e) {
        error_log("Error logging admin activity: " . $e->getMessage());
        return false;
    }
}

// Function to update last_active timestamp
function updateLastActive($pdo, $admin_id) {
    try {
        $stmt = $pdo->prepare("UPDATE admins_tbl SET last_active = NOW() WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        return true;
    } catch (Exception $e) {
        error_log("Error updating last_active: " . $e->getMessage());
        return false;
    }
}

// Function to update user status
function updateUserStatus($pdo, $admin_id, $status) {
    try {
        $stmt = $pdo->prepare("UPDATE admins_tbl SET user_status = ? WHERE admin_id = ?");
        $stmt->execute([$status, $admin_id]);
        return true;
    } catch (Exception $e) {
        error_log("Error updating user_status: " . $e->getMessage());
        return false;
    }
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email and password are required',
        'field' => empty($email) ? 'email' : 'password'
    ]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address',
        'field' => 'email'
    ]);
    exit;
}

try {
    // Connect to database
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Check if email exists in database
    $stmt = $pdo->prepare("
        SELECT admin_id, first_name, middle_name, last_name, user_email, username, password, 
               account_status, user_status, last_active, barangay_position 
        FROM admins_tbl 
        WHERE user_email = ?
    ");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo json_encode([
            'success' => false,
            'message' => 'Account Not Found',
            'field' => 'general'
        ]);
        exit;
    }
    
    // Check account status
    if ($admin['account_status'] === 'inactive') {
        echo json_encode([
            'success' => false,
            'message' => 'Inactive Account. Contact the Admin Personnel to Resolve the Issue',
            'field' => 'general'
        ]);
        exit;
    }
    
    if ($admin['account_status'] === 'suspended') {
        echo json_encode([
            'success' => false,
            'message' => 'Your account has been suspended. Please contact the administrator.',
            'field' => 'general'
        ]);
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $admin['password'])) {
        // Log failed login attempt
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        logAdminActivity($pdo, $admin['admin_id'], $admin['last_active'], $ip_address, $user_agent, 'failed');
        
        echo json_encode([
            'success' => false,
            'message' => 'Password Incorrect',
            'field' => 'general'
        ]);
        exit;
    }
    
    // Login successful - store previous last_active before updating
    $previous_last_active = $admin['last_active'];
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    // Update last_active timestamp AND user_status to online
    updateLastActive($pdo, $admin['admin_id']);
    updateUserStatus($pdo, $admin['admin_id'], 'online');

    // Debug: Check if user_status was updated
    $debug_stmt = $pdo->prepare("SELECT user_status FROM admins_tbl WHERE admin_id = ?");
    $debug_stmt->execute([$admin['admin_id']]);
    $current_status = $debug_stmt->fetchColumn();
    error_log("User status after login update: " . $current_status);

    // Log successful login
    logAdminActivity($pdo, $admin['admin_id'], $previous_last_active, $ip_address, $user_agent, 'success');

    // Create session
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['admin_name'] = trim($admin['first_name'] . ' ' . ($admin['middle_name'] ? $admin['middle_name'] . ' ' : '') . $admin['last_name']);
    $_SESSION['admin_email'] = $admin['user_email'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_position'] = $admin['barangay_position'];
    $_SESSION['login_time'] = time();

    // ADD THESE NEW SESSION VARIABLES FOR CSV EXPORT
    $_SESSION['admin_first_name'] = $admin['first_name'];
    $_SESSION['admin_middle_name'] = $admin['middle_name'] ?? '';
    $_SESSION['admin_last_name'] = $admin['last_name'];
    $_SESSION['admin_picture'] = $admin['picture'] ?? '';
    
    // Generate admin display name
    $displayName = $admin['first_name'];
    if (!empty($admin['middle_name'])) {
        $displayName .= ' ' . $admin['middle_name'];
    }
    if (!empty($admin['last_name'])) {
        $displayName .= ' ' . $admin['last_name'];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful! Redirecting to dashboard...',
        'admin' => [
            'admin_id' => $admin['admin_id'],
            'name' => $displayName,
            'email' => $admin['user_email'],
            'username' => $admin['username'],
            'position' => $admin['barangay_position']
        ],
        'redirect_url' => '/ALERTPOINT/html/Users.php'
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during login. Please try again.',
        'field' => 'general'
    ]);
}
?>