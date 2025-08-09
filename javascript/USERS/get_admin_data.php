<?php
// Temporary error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Response array
$response = [
    'success' => false,
    'message' => '',
    'admin' => null
];

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    // Get JSON input
    $rawInput = file_get_contents('php://input');
    error_log("Raw input: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    error_log("Decoded input: " . print_r($input, true));
    
    if (!$input || !isset($input['admin_id'])) {
        throw new Exception('Admin ID is required. Input received: ' . $rawInput);
    }
    
    $adminId = trim($input['admin_id']);
    error_log("Admin ID: " . $adminId);
    
    if (empty($adminId)) {
        throw new Exception('Admin ID cannot be empty');
    }
    
    // Check if database connection file exists
    $dbConfigPath = '../../config/database.php';
    if (!file_exists($dbConfigPath)) {
        throw new Exception('Database config file not found at: ' . $dbConfigPath);
    }
    
    // Database connection
    require_once $dbConfigPath;
    
    if (!class_exists('Database')) {
        throw new Exception('Database class not found');
    }
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    error_log("Database connection successful");
    
    // Prepare and execute query
    $sql = "
        SELECT admin_id, first_name, middle_name, last_name, barangay_position, 
               birthdate, user_email, username, picture, role, account_status, 
               user_status, account_created, last_active 
        FROM admins_tbl 
        WHERE admin_id = ? AND account_status = 'active'
    ";
    
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . print_r([$adminId], true));
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Query result: " . print_r($admin, true));
    
    if (!$admin) {
        throw new Exception('Admin not found or not active for ID: ' . $adminId);
    }
    
    // Clean up the data
    $admin['first_name'] = $admin['first_name'] ?? '';
    $admin['middle_name'] = $admin['middle_name'] ?? '';
    $admin['last_name'] = $admin['last_name'] ?? '';
    $admin['barangay_position'] = $admin['barangay_position'] ?? '';
    $admin['birthdate'] = $admin['birthdate'] ?? '';
    $admin['user_email'] = $admin['user_email'] ?? '';
    $admin['username'] = $admin['username'] ?? '';
    $admin['picture'] = $admin['picture'] ?? '';
    $admin['role'] = $admin['role'] ?? '';
    
    error_log("Original picture path: " . $admin['picture']);
    
    // Normalize picture path
    if (!empty($admin['picture']) && $admin['picture'] !== 'NULL' && strtolower($admin['picture']) !== 'null') {
        $picturePath = $admin['picture'];
        
        // Handle relative paths that start with ../../
        if (strpos($picturePath, '../../') === 0) {
            $picturePath = str_replace('../../', '/ALERTPOINT/', $picturePath);
        }
        // Handle paths that already start with /ALERTPOINT/
        elseif (strpos($picturePath, '/ALERTPOINT/') === 0) {
            // Keep as is, it's already properly formatted
        }
        // Handle other relative paths - add /ALERTPOINT/ prefix
        elseif (strpos($picturePath, '/') !== 0 && strpos($picturePath, 'http') !== 0) {
            $picturePath = '/ALERTPOINT/' . $picturePath;
        }
        
        $admin['picture'] = $picturePath;
    } else {
        $admin['picture'] = null;
    }
    
    error_log("Normalized picture path: " . $admin['picture']);
    
    // Success response
    $response['success'] = true;
    $response['message'] = 'Admin data retrieved successfully';
    $response['admin'] = $admin;
    
    error_log("Success response: " . print_r($response, true));
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    error_log("Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
    
    error_log("PDO Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
} catch (Error $e) {
    $response['success'] = false;
    $response['message'] = 'Fatal error: ' . $e->getMessage();
    
    error_log("Fatal Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
}

// Return JSON response
error_log("Final response: " . json_encode($response));
echo json_encode($response);
?>