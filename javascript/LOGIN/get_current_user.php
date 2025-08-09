<?php
// Updated get_current_user.php
session_start();
require_once '../../config/database.php';

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

$admin_id = $_SESSION['admin_id'] ?? null;

if (!$admin_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid session data'
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
    
    // Fetch current user data with all required fields
    $stmt = $pdo->prepare("
        SELECT admin_id, first_name, middle_name, last_name, barangay_position, 
               birthdate, user_email, username, picture, role, account_status, 
               user_status, account_created, last_active 
        FROM admins_tbl 
        WHERE admin_id = ? AND account_status != 'deleted'
    ");
    $stmt->execute([$admin_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found or account deactivated'
        ]);
        exit;
    }
    
    // Update last_active timestamp
    $updateStmt = $pdo->prepare("UPDATE admins_tbl SET last_active = CURRENT_TIMESTAMP WHERE admin_id = ?");
    $updateStmt->execute([$admin_id]);
    
    // Refresh the user data after update
    $stmt->execute([$admin_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Get current user error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching user data',
        'error' => $e->getMessage() // Remove this line in production
    ]);
}
?>