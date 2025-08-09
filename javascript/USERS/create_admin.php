<?php
// Production-safe error reporting
ini_set('display_errors', 0);  // Don't display errors to user
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);  // Still log errors for debugging
error_reporting(E_ALL);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Function to log errors
function logError($message) {
    $logFile = __DIR__ . '/admin_creation_errors.log';
    
    // Check if directory is writable
    if (is_writable(__DIR__)) {
        // Try to write to log file
        $result = error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, $logFile);
        
        // If that fails, try system error log as fallback
        if (!$result) {
            error_log("ALERTPOINT Admin Creation: " . $message);
        }
    } else {
        // Fallback to system error log if directory not writable
        error_log("ALERTPOINT Admin Creation: " . $message);
    }
}

// Function to generate unique username
function generateUniqueUsername($pdo, $firstName) {
    try {
        // Clean the first name: get first word, convert to lowercase, remove special characters
        $cleanFirstName = strtolower(preg_replace('/[^a-z0-9]/i', '', explode(' ', trim($firstName))[0]));
        $baseUsername = "admin_" . $cleanFirstName;
        
        // Check if base username exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins_tbl WHERE username = ?");
        $checkStmt->execute([$baseUsername]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            return $baseUsername; // Username is available
        }
        
        // Find the next available number
        $counter = 2;
        while (true) {
            $newUsername = $baseUsername . $counter;
            $checkStmt->execute([$newUsername]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] == 0) {
                return $newUsername;
            }
            $counter++;
            
            // Safety break to prevent infinite loop
            if ($counter > 1000) {
                return $baseUsername . "_" . uniqid();
            }
        }
    } catch (Exception $e) {
        logError("Error generating unique username: " . $e->getMessage());
        return "admin_" . uniqid();
    }
}

// Log the request for debugging
logError("POST data: " . print_r($_POST, true));
logError("FILES data: " . print_r($_FILES, true));

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Include required files
    $possible_config_paths = [
        '../../config/database.php',    // Most likely correct path
        '../../../config/database.php', // Alternative
        '../../ALERTPOINT/config/database.php',
        '../config/database.php',
        'config/database.php',
        '../../html/config/database.php'
    ];
    
    // Try to find session check file
    $possible_session_paths = [
        '../../javascript/LOGIN/check_session.php',
        '../../../javascript/LOGIN/check_session.php',
        '../../LOGIN/check_session.php',
        '../LOGIN/check_session.php'
    ];
    
    // Try to find system action logger
    $possible_logger_paths = [
        '../../functions/system_action_logger.php',
        '../../../functions/system_action_logger.php',
        '../../system_action_logger.php',
        '../system_action_logger.php'
    ];
    
    $database = null;
    $config_found = false;
    
    // Load database config
    foreach ($possible_config_paths as $config_path) {
        if (file_exists($config_path)) {
            require_once $config_path;
            logError("Using config path: " . $config_path);
            $config_found = true;
            break;
        }
    }
    
    if (!$config_found) {
        logError("Database config not found. Checked paths: " . implode(', ', $possible_config_paths));
        throw new Exception("Database configuration file not found. Checked paths: " . implode(', ', $possible_config_paths));
    }
    
    // Load session check (optional - only if available)
    $session_loaded = false;
    foreach ($possible_session_paths as $session_path) {
        if (file_exists($session_path)) {
            require_once $session_path;
            logError("Using session path: " . $session_path);
            $session_loaded = true;
            break;
        }
    }
    
    // Load system action logger (optional - only if available)
    $logger_loaded = false;
    foreach ($possible_logger_paths as $logger_path) {
        if (file_exists($logger_path)) {
            require_once $logger_path;
            logError("Using logger path: " . $logger_path);
            $logger_loaded = true;
            break;
        }
    }
    
    if (!class_exists('Database')) {
        throw new Exception("Database class not found in config file");
    }
    
    // Get database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    // Check session if available
    $currentAdminId = null;
    if ($session_loaded && function_exists('checkAdminSession') && function_exists('getCurrentAdmin')) {
        if (!checkAdminSession()) {
            throw new Exception("Unauthorized access - please login");
        }
        $currentAdmin = getCurrentAdmin();
        $currentAdminId = $currentAdmin['admin_id'] ?? null;
        logError("Current admin ID: " . $currentAdminId);
    } else {
        logError("Session check not available - proceeding without authentication");
        // For development/testing purposes - in production you should require authentication
        $currentAdminId = 'SYSTEM'; // Default value when no session available
    }
    
    // Validate required fields
    $requiredFields = ['admin_fn', 'admin_ln', 'user_email', 'birth_month', 'birth_day', 'birth_year', 'role', 'username', 'password'];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Required field '$field' is missing or empty");
        }
    }
    
    // Sanitize and validate input data
    $firstName = trim($_POST['admin_fn']);
    $middleName = isset($_POST['admin_mn']) ? trim($_POST['admin_mn']) : '';
    $lastName = trim($_POST['admin_ln']);
    $email = trim($_POST['user_email']);
    $birthMonth = $_POST['birth_month'];
    $birthDay = $_POST['birth_day'];
    $birthYear = $_POST['birth_year'];
    $role = $_POST['role'];
    $customRole = isset($_POST['customRole']) ? trim($_POST['customRole']) : '';
    $username = generateUniqueUsername($pdo, $firstName);
    $password = $_POST['password'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }
    
    // Check if email ends with .com
    if (!preg_match('/\.com$/i', $email)) {
        throw new Exception("Email must end with .com");
    }
    
    // Validate name lengths
    if (strlen($firstName) < 2) {
        throw new Exception("First name must be at least 2 characters long");
    }
    
    if (strlen($lastName) < 2) {
        throw new Exception("Last name must be at least 2 characters long");
    }
    
    // Validate password strength
    if (strlen($password) < 8 || !preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        throw new Exception("Password must be at least 8 characters with letters and numbers");
    }
    
    // Determine the final role
    $finalRole = ($role === 'other' && !empty($customRole)) ? $customRole : $role;
    
    if (empty($finalRole)) {
        throw new Exception("Position/Role is required");
    }
    
    // Create birthdate
    $birthdate = sprintf("%s-%s-%s", $birthYear, str_pad($birthMonth, 2, '0', STR_PAD_LEFT), str_pad($birthDay, 2, '0', STR_PAD_LEFT));
    
    // Validate birthdate
    if (!checkdate($birthMonth, $birthDay, $birthYear)) {
        throw new Exception("Invalid birthdate");
    }
    
    // Check if email already exists
    $checkEmailStmt = $pdo->prepare("SELECT id FROM admins_tbl WHERE user_email = ?");
    $checkEmailStmt->execute([$email]);
    if ($checkEmailStmt->fetch()) {
        throw new Exception("Email address already exists");
    }
    
    // Function to generate admin ID
    function generateAdminId($pdo) {
        try {
            // Get the count of existing admins
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins_tbl");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $result['count'] + 1;
            
            // Generate ID in format: ADM-XXXX (e.g., ADM-0001) to fit varchar(10)
            $adminId = sprintf("ADM-%04d", $count);

            // Check if ID already exists, if yes, increment
            while (true) {
                $checkStmt = $pdo->prepare("SELECT admin_id FROM admins_tbl WHERE admin_id = ?");
                $checkStmt->execute([$adminId]);
                if (!$checkStmt->fetch()) {
                    break;
                }
                $count++;
                $adminId = sprintf("ADM-%04d", $count);
            }
            
            return $adminId;
        } catch (Exception $e) {
            logError("Error generating admin ID: " . $e->getMessage());
            return "ADMIN-" . date('Y') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }
    
    // Function to handle file upload
    function handleProfileImageUpload($file) {
        // Use ONLY the working path from diagnostics
        $possibleUploadDirs = [
            '../../uploads/admin/',    // This works! ✅
        ];
        
        $uploadDir = null;
        foreach ($possibleUploadDirs as $dir) {
            if (!file_exists($dir)) {
                if (mkdir($dir, 0755, true)) {
                    $uploadDir = $dir;
                    break;
                }
            } else {
                $uploadDir = $dir;
                break;
            }
        }
        
        if (!$uploadDir) {
            throw new Exception("Failed to create or access upload directory: " . implode(', ', $possibleUploadDirs));
        }
        
        // Validate file
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Invalid file type. Only PNG, JPG, and JPEG are allowed.");
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception("File size too large. Maximum 5MB allowed.");
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'admin_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception("Failed to upload file to: " . $filepath);
        }
        
        // Log successful upload
        logError("File uploaded successfully to: " . $filepath);
        
        return $filepath; // Return full path for database storage
    }
    
    // Handle profile image upload
    $profileImagePath = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        try {
            $profileImagePath = handleProfileImageUpload($_FILES['profile_image']);
            logError("Profile image uploaded successfully: " . $profileImagePath);
        } catch (Exception $e) {
            logError("Profile image upload error: " . $e->getMessage());
            throw new Exception("Profile image upload failed: " . $e->getMessage());
        }
    }
    
    // Generate admin ID
    $adminId = generateAdminId($pdo);
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Prepare insert statement
    $insertQuery = "INSERT INTO admins_tbl (admin_id, picture, first_name, middle_name, last_name, barangay_position, birthdate, user_email, username, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";    
    $insertStmt = $pdo->prepare($insertQuery);
    
    // Execute insert
    $success = $insertStmt->execute([
        $adminId,
        $profileImagePath,
        $firstName,
        $middleName,
        $lastName,
        $finalRole,
        $birthdate,
        $email,
        $username,
        $hashedPassword
    ]);
    
    if (!$success) {
        $errorInfo = $insertStmt->errorInfo();
        throw new Exception("Database insert failed: " . $errorInfo[2]);
    }
    
    // LOG THE ADMIN CREATION - This is the key addition
    if ($logger_loaded && function_exists('logAdminCreation') && $currentAdminId) {
        $newAdminName = trim($firstName . ' ' . ($middleName ? $middleName . ' ' : '') . $lastName);
        $logResult = logAdminCreation(
            $pdo, 
            $currentAdminId, 
            $adminId, 
            $newAdminName, 
            $email, 
            $finalRole
        );
        
        if ($logResult) {
            logError("Admin creation logged successfully for: $newAdminName (ID: $adminId)");
        } else {
            logError("Failed to log admin creation for admin_id: $adminId");
        }
    } else {
        logError("System action logging not available - admin creation not logged");
    }
    
    // Log successful creation
    logError("Admin created successfully: ID=$adminId, Email=$email, Username=$username");
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Admin account created successfully',
        'admin_id' => $adminId,
        'username' => $username,
        'data' => [
            'id' => $adminId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'username' => $username,
            'role' => $finalRole
        ]
    ]);
    
} catch (PDOException $e) {
    // Database error
    logError("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'error_type' => 'database',
        'debug_info' => [
            'error_code' => $e->getCode(),
            'file' => __FILE__,
            'line' => __LINE__
        ]
    ]);
    
} catch (Exception $e) {
    // General error
    logError("General error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'validation',
        'debug_info' => [
            'file' => __FILE__,
            'line' => __LINE__,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Error $e) {
    // Fatal error
    logError("Fatal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A fatal error occurred: ' . $e->getMessage(),
        'error_type' => 'fatal',
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>