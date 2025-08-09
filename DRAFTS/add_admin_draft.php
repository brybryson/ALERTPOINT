<?php
// Add timing for debugging
$start_time = microtime(true);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prevent HTML error output and ensure JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Capture any unexpected output
ob_start();

// Debug log function
function debug_log($message) {
    error_log("[DEBUG] " . $message);
}

try {
    debug_log("Starting admin creation process");
    
    // Fix the path to database.php - adjust based on your actual folder structure
    require_once '../../config/database.php';
    
    debug_log("Database file included successfully");
    
    // Clean any output that might have been generated
    ob_clean();
    
} catch (Exception $e) {
    debug_log("Database connection error: " . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error: ' . $e->getMessage()
    ]);
    exit;
}

class AdminHandler {
    private $conn;
    private $upload_dir;
    
    public function __construct() {
        try {
            debug_log("AdminHandler constructor started");
            
            $database = new Database();
            $this->conn = $database->getConnection();
            
            if (!$this->conn) {
                throw new Exception('Database connection failed');
            }
            
            debug_log("Database connection established successfully");
            
            // Fixed upload directory path
            $this->upload_dir = realpath(dirname(__FILE__) . '/../../uploads/admin/');
            
            if (!$this->upload_dir) {
                // If the directory doesn't exist, create it
                $upload_path = dirname(__FILE__) . '/../../uploads/admin/';
                if (!is_dir($upload_path)) {
                    mkdir($upload_path, 0755, true);
                    debug_log("Created upload directory: " . $upload_path);
                }
                $this->upload_dir = realpath($upload_path);
            }
            
            // Ensure trailing slash
            $this->upload_dir = rtrim($this->upload_dir, '/') . '/';
            debug_log("Upload directory set: " . $this->upload_dir);
            
        } catch (Exception $e) {
            debug_log("Constructor error: " . $e->getMessage());
            throw new Exception('Constructor error: ' . $e->getMessage());
        }
    }
    
    public function generateNextAdminId() {
        try {
            debug_log("Generating admin ID");
            $query = "SELECT admin_id FROM admins ORDER BY admin_id DESC LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                debug_log("No existing admins, starting with ADM0001");
                return 'ADM0001';
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $lastId = $result['admin_id'];
            
            // Extract numeric part and increment
            $numericPart = (int)substr($lastId, 3);
            $nextNum = $numericPart + 1;
            $newId = 'ADM' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            
            debug_log("Generated new admin ID: " . $newId);
            return $newId;
        } catch (Exception $e) {
            debug_log("Error generating admin ID: " . $e->getMessage());
            throw new Exception('Error generating admin ID: ' . $e->getMessage());
        }
    }
    
    public function uploadProfileImage($fileData, $adminId) {
        try {
            debug_log("Starting image upload for admin: " . $adminId);
            
            // If no file data provided, return null
            if (empty($fileData) || $fileData === null) {
                debug_log("No image data provided, skipping upload");
                return null;
            }
            
            // Handle base64 image data
            if (strpos($fileData, 'data:image/') === 0) {
                debug_log("Processing base64 image data");
                
                // Extract image data from base64
                $data = explode(',', $fileData);
                if (count($data) != 2) {
                    throw new Exception('Invalid image data format');
                }
                
                $imageData = base64_decode($data[1]);
                if ($imageData === false) {
                    throw new Exception('Failed to decode base64 image data');
                }
                
                debug_log("Image data decoded, size: " . strlen($imageData) . " bytes");
                
                // Validate image data
                if (strlen($imageData) < 100) {
                    throw new Exception('Image data too small, possibly corrupted');
                }
                
                $mimeType = explode(';', explode(':', $data[0])[1])[0];
                
                // Determine file extension
                $extension = '';
                switch ($mimeType) {
                    case 'image/jpeg':
                    case 'image/jpg':
                        $extension = '.jpg';
                        break;
                    case 'image/png':
                        $extension = '.png';
                        break;
                    default:
                        throw new Exception('Unsupported image format: ' . $mimeType);
                }
                
                $filename = $adminId . $extension;
                $filepath = $this->upload_dir . $filename;
                
                debug_log("Saving image to: " . $filepath);
                
                $result = file_put_contents($filepath, $imageData);
                if ($result === false) {
                    throw new Exception('Failed to save image file to: ' . $filepath);
                }
                
                debug_log("Image saved successfully, " . $result . " bytes written");
                
                // Verify file was created and has content
                if (!file_exists($filepath) || filesize($filepath) < 100) {
                    throw new Exception('Image file was not saved properly');
                }
                
                // Return relative path for web access
                return '/ALERTPOINT/uploads/admin/' . $filename;
            }
            
            return null;
        } catch (Exception $e) {
            debug_log('Image upload error: ' . $e->getMessage());
            // Don't throw exception for image upload errors, just log and continue
            return null;
        }
    }
    
    public function validateInput($data) {
        debug_log("Validating input data");
        $errors = [];
        
        // Validate required fields
        $required = ['admin_fn', 'admin_ln', 'user_email', 'birth_month', 'birth_day', 'birth_year', 'role', 'username', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Field $field is required";
            }
        }
        
        // Quick validation checks
        if (!empty($data['admin_fn']) && strlen(trim($data['admin_fn'])) < 2) {
            $errors[] = "First name must be at least 2 characters";
        }
        
        if (!empty($data['admin_ln']) && strlen(trim($data['admin_ln'])) < 2) {
            $errors[] = "Last name must be at least 2 characters";
        }
        
        // Simple email validation
        if (!empty($data['user_email']) && !filter_var($data['user_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        // Simple password validation
        if (!empty($data['password']) && strlen($data['password']) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
        
        debug_log("Validation completed, errors: " . count($errors));
        return $errors;
    }
    
    public function createAdmin($data) {
        try {
            debug_log("Starting admin creation");
            
            // Validate input
            $validation_errors = $this->validateInput($data);
            if (!empty($validation_errors)) {
                throw new Exception('Validation errors: ' . implode(', ', $validation_errors));
            }
            
            debug_log("Input validation passed");
            
            // Quick username check
            debug_log("Checking username uniqueness");
            $check_query = "SELECT admin_id FROM admins WHERE username = ? LIMIT 1";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute([$data['username']]);
            
            if ($check_stmt->rowCount() > 0) {
                throw new Exception('Username already exists');
            }
            
            debug_log("Username is unique");
            
            // Quick email check
            debug_log("Checking email uniqueness");
            $check_email_query = "SELECT admin_id FROM admins WHERE user_email = ? LIMIT 1";
            $check_email_stmt = $this->conn->prepare($check_email_query);
            $check_email_stmt->execute([$data['user_email']]);
            
            if ($check_email_stmt->rowCount() > 0) {
                throw new Exception('Email address already exists');
            }
            
            debug_log("Email is unique");
            
            // Generate admin ID
            $adminId = $this->generateNextAdminId();
            
            // Upload profile image if provided (non-blocking)
            $picturePath = null;
            if (!empty($data['photo']) && $data['photo'] !== null) {
                debug_log("Processing image upload");
                $picturePath = $this->uploadProfileImage($data['photo'], $adminId);
                debug_log("Image upload completed");
            }
            
            // Format birthdate
            $birthdate = $this->formatBirthdate(
                $data['birth_month'],
                $data['birth_day'],
                $data['birth_year']
            );
            
            debug_log("Birthdate formatted: " . $birthdate);
            
            // Hash password
            debug_log("Hashing password");
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Prepare SQL query with simplified approach
            debug_log("Preparing database insert");
            $query = "INSERT INTO admins (
                admin_id, first_name, middle_name, last_name, user_email,
                barangay_position, birthdate, username, password, 
                picture, account_status, user_status, account_created, last_active, role
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'offline', NOW(), NOW(), 'Admin')";
            
            $stmt = $this->conn->prepare($query);
            
            $executeResult = $stmt->execute([
                $adminId,
                $data['admin_fn'],
                $data['admin_mn'] ?? '',
                $data['admin_ln'],
                $data['user_email'],
                $data['role'],
                $birthdate,
                $data['username'],
                $hashedPassword,
                $picturePath
            ]);
            
            if ($executeResult) {
                debug_log("Admin created successfully with ID: " . $adminId);
                return [
                    'success' => true,
                    'message' => 'Admin account created successfully',
                    'data' => [
                        'admin_id' => $adminId,
                        'first_name' => $data['admin_fn'],
                        'middle_name' => $data['admin_mn'] ?? '',
                        'last_name' => $data['admin_ln'],
                        'user_email' => $data['user_email'],
                        'username' => $data['username'],
                        'birthdate' => $birthdate,
                        'role' => $data['role'],
                        'picture' => $picturePath
                    ]
                ];
            } else {
                $errorInfo = $stmt->errorInfo();
                debug_log("Database insert failed: " . print_r($errorInfo, true));
                throw new Exception('Failed to create admin account');
            }
            
        } catch (Exception $e) {
            debug_log("Admin creation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function formatBirthdate($month, $day, $year) {
        $monthNames = [
            '01' => 'January', '02' => 'February', '03' => 'March',
            '04' => 'April', '05' => 'May', '06' => 'June',
            '07' => 'July', '08' => 'August', '09' => 'September',
            '10' => 'October', '11' => 'November', '12' => 'December'
        ];
        
        $monthName = $monthNames[$month] ?? 'Unknown';
        return $monthName . ' ' . (int)$day . ', ' . $year;
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        debug_log("POST request received");
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON data received');
        }
        
        debug_log("JSON data decoded successfully");
        
        $adminHandler = new AdminHandler();
        $result = $adminHandler->createAdmin($input);
        
        // Calculate execution time
        $end_time = microtime(true);
        $execution_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
        
        debug_log("Admin creation completed in " . $execution_time . "ms");
        
        // Add execution time to response for debugging
        $result['execution_time'] = $execution_time . 'ms';
        
        // Clean any remaining output and send JSON
        ob_clean();
        echo json_encode($result);
        
    } catch (Exception $e) {
        debug_log("Request handling error: " . $e->getMessage());
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Only POST method allowed'
    ]);
}

// End output buffering
ob_end_flush();
?>