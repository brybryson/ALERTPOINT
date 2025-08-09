<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Include database config - same path as your create_admin.php
    $possible_config_paths = [
        '../../config/database.php',
    ];
    
    $database = null;
    $config_found = false;
    
    foreach ($possible_config_paths as $config_path) {
        if (file_exists($config_path)) {
            require_once $config_path;
            $config_found = true;
            break;
        }
    }
    
    if (!$config_found) {
        throw new Exception("Database configuration file not found");
    }
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['username']) || empty($input['username'])) {
        echo json_encode(['error' => 'Username is required']);
        exit;
    }
    
    $username = trim($input['username']);
    
    // Check if username exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins_tbl WHERE username = ?");
    $stmt->execute([$username]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['exists' => $result['count'] > 0]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
}
?>