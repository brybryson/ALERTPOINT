<?php
// File: /ALERTPOINT/javascript/LOGIN/reset_password.php
session_start();
require_once '../../config/database.php';

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Check if OTP was verified (session check)
if (!isset($_SESSION['password_reset_verified']) || $_SESSION['password_reset_verified'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please verify OTP first.',
        'field' => 'general'
    ]);
    exit;
}

// Check if session is still valid (10 minutes after OTP verification)
if (isset($_SESSION['password_reset_time'])) {
    $session_duration = 600; // 10 minutes
    $current_time = time();
    
    if (($current_time - $_SESSION['password_reset_time']) > $session_duration) {
        // Clear session
        unset($_SESSION['password_reset_verified']);
        unset($_SESSION['password_reset_admin_id']);
        unset($_SESSION['password_reset_email']);
        unset($_SESSION['password_reset_time']);
        
        echo json_encode([
            'success' => false,
            'message' => 'Password reset session expired. Please verify OTP again.',
            'field' => 'general',
            'session_expired' => true
        ]);
        exit;
    }
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$new_password = $input['new_password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

// Validate input
if (empty($new_password) || empty($confirm_password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Both password fields are required',
        'field' => empty($new_password) ? 'new_password' : 'confirm_password'
    ]);
    exit;
}

// Check if passwords match
if ($new_password !== $confirm_password) {
    echo json_encode([
        'success' => false,
        'message' => 'Passwords do not match',
        'field' => 'confirm_password'
    ]);
    exit;
}

// Validate password strength
if (strlen($new_password) < 8) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 8 characters long',
        'field' => 'new_password'
    ]);
    exit;
}

// Additional password validation
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number',
        'field' => 'new_password'
    ]);
    exit;
}

// REPLACE the reset_password.php file content after the password validation part with this:

try {
    // Connect to database
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    $admin_id = $_SESSION['password_reset_admin_id'];
    $email = $_SESSION['password_reset_email'];
    
    // Debug logging
    error_log("Reset password attempt - Admin ID: $admin_id, Email: $email");
    
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // First, verify the admin exists
    $check_stmt = $pdo->prepare("SELECT admin_id, user_email FROM admins_tbl WHERE admin_id = ? AND user_email = ?");
    $check_stmt->execute([$admin_id, $email]);
    $admin_exists = $check_stmt->fetch();
    
    if (!$admin_exists) {
        error_log("Reset password error: Admin not found - ID: $admin_id, Email: $email");
        echo json_encode([
            'success' => false,
            'message' => 'User not found. Please try the password reset process again.',
            'field' => 'general'
        ]);
        exit;
    }
    
    // Update password in database
    $stmt = $pdo->prepare("UPDATE admins_tbl SET password = ? WHERE admin_id = ? AND user_email = ?");
    $result = $stmt->execute([$hashed_password, $admin_id, $email]);
    
    error_log("Update result: " . ($result ? 'true' : 'false') . ", Rows affected: " . $stmt->rowCount());
    
    if ($result && $stmt->rowCount() > 0) {
        // Clear password reset session
        unset($_SESSION['password_reset_verified']);
        unset($_SESSION['password_reset_admin_id']);
        unset($_SESSION['password_reset_email']);
        unset($_SESSION['password_reset_time']);
        
        // Log the password reset activity
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        try {
            $log_stmt = $pdo->prepare("
                INSERT INTO admin_logs (admin_id, ip_address, user_agent, login_status, created_at) 
                VALUES (?, ?, ?, 'password_reset', NOW())
            ");
            $log_stmt->execute([$admin_id, $ip_address, $user_agent]);
        } catch (Exception $log_error) {
            // Don't fail the password reset if logging fails
            error_log("Failed to log password reset: " . $log_error->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Password has been reset successfully. You can now login with your new password.'
        ]);
        
    } else {
        error_log("Failed to update password - Admin ID: $admin_id, Email: $email");
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update password. Please try again.',
            'field' => 'general'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Reset password error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while resetting password. Please try again.',
        'field' => 'general',
        'debug' => $e->getMessage() // Add this for debugging, remove in production
    ]);
}
?>