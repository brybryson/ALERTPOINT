<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require_once '../../config/database.php';
// Include logging function
require_once '../../functions/system_action_logger.php';
// Include session check to get current admin
require_once '../../javascript/LOGIN/check_session.php';

// Response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    // Get current admin performing the action
    $currentAdmin = getCurrentAdmin();
    if (!$currentAdmin) {
        throw new Exception('No valid admin session found');
    }
    $currentAdminId = $currentAdmin['admin_id'];
    
    // Get JSON input
    $rawInput = file_get_contents('php://input');
    error_log("Archive Admin - Raw input: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    error_log("Archive Admin - Decoded input: " . print_r($input, true));
    
    if (!$input || !isset($input['admin_id'])) {
        throw new Exception('Admin ID is required');
    }
    
    $adminId = trim($input['admin_id']);
    
    if (empty($adminId)) {
        throw new Exception('Admin ID cannot be empty');
    }
    
    // Prevent self-archiving
    if ($adminId === $currentAdminId) {
        throw new Exception('You cannot archive your own account');
    }
    
    // Database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Check if admin exists and is active
        $checkStmt = $pdo->prepare("
            SELECT admin_id, first_name, middle_name, last_name, barangay_position, account_status 
            FROM admins_tbl 
            WHERE admin_id = ?
        ");
        $checkStmt->execute([$adminId]);
        $admin = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Archive Admin - Admin data: " . print_r($admin, true));
        
        if (!$admin) {
            throw new Exception('Admin not found with ID: ' . $adminId);
        }
        
        if ($admin['account_status'] !== 'active') {
            throw new Exception('Admin is already archived or inactive (current status: ' . $admin['account_status'] . ')');
        }
        
        // Archive the admin (change status to inactive)
        $archiveStmt = $pdo->prepare("
            UPDATE admins_tbl 
            SET account_status = 'inactive', 
                user_status = 'offline',
                last_active = NOW() 
            WHERE admin_id = ? AND account_status = 'active'
        ");
        
        $archiveResult = $archiveStmt->execute([$adminId]);
        
        if (!$archiveResult) {
            throw new Exception('Failed to execute archive query');
        }
        
        // Check if any rows were affected
        $rowsAffected = $archiveStmt->rowCount();
        error_log("Archive Admin - Rows affected: " . $rowsAffected);
        
        if ($rowsAffected === 0) {
            throw new Exception('No rows updated - admin may have already been archived');
        }
        
        // Prepare admin details for logging
        $fullName = trim($admin['first_name'] . ' ' . ($admin['middle_name'] ? $admin['middle_name'] . ' ' : '') . $admin['last_name']);
        
        // Log the archive action using the system logger
        $logDetails = json_encode([
            'archived_admin_id' => $adminId,
            'archived_admin_name' => $fullName,
            'archived_admin_position' => $admin['barangay_position'],
            'previous_status' => 'active',
            'new_status' => 'inactive',
            'archived_at' => date('Y-m-d H:i:s'),
            'archived_by' => $currentAdmin['first_name'] . ' ' . $currentAdmin['last_name']
        ]);
        
        logAdminAction($pdo, $currentAdminId, 'archived', $adminId, $fullName, $logDetails);
        
        // Commit transaction
        $pdo->commit();
        
        // Success response
        $response['success'] = true;
        $response['message'] = "Admin '{$fullName}' has been archived successfully";
        $response['data'] = [
            'admin_id' => $adminId,
            'full_name' => $fullName,
            'position' => $admin['barangay_position'],
            'previous_status' => 'active',
            'new_status' => 'inactive',
            'archived_at' => date('Y-m-d H:i:s')
        ];
        
        error_log("Archive Admin - Success: " . print_r($response, true));
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollback();
        error_log("Archive Admin - Transaction rolled back: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Log error for debugging
    error_log("Archive Admin Error: " . $e->getMessage());
    error_log("Archive Admin Stack Trace: " . $e->getTraceAsString());
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error occurred while archiving admin';
    
    // Log detailed error for debugging
    error_log("Archive Admin PDO Error: " . $e->getMessage());
    error_log("Archive Admin PDO Stack Trace: " . $e->getTraceAsString());
}

// Return JSON response
error_log("Archive Admin - Final response: " . json_encode($response));
echo json_encode($response);
?>