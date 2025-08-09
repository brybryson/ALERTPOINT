<?php
// admin_actions.php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize PDO variable
$pdo = null;

// Include database connection
try {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ALERTPOINT/config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to establish database connection");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'get_admin':
            getAdminData($pdo);
            break;
        case 'edit_admin':
            editAdmin($pdo);
            break;
        case 'archive_admin':
            archiveAdmin($pdo);
            break;
        case 'restore_admin':
            restoreAdmin($pdo);
            break;
        case 'delete_admin':
            deleteAdmin($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

// Function to get admin data
function getAdminData($pdo) {
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection not available']);
        return;
    }
    
    if (!isset($_POST['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Admin ID is required']);
        return;
    }
    
    $adminId = $_POST['admin_id'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo json_encode(['success' => true, 'admin' => $admin]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Admin not found']);
        }
    } catch (PDOException $e) {
        error_log("Error fetching admin data: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

// Function to edit admin
function editAdmin($pdo) {
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection not available']);
        return;
    }
    
    // Validate required fields
    $requiredFields = ['admin_id', 'first_name', 'last_name', 'barangay_position', 'birthdate'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }
    
    $adminId = $_POST['admin_id'];
    $firstName = trim($_POST['first_name']);
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name']);
    $barangayPosition = trim($_POST['barangay_position']);
    $birthdate = $_POST['birthdate'];
    
    try {
        // Format birthdate
        $formattedBirthdate = date('F j, Y', strtotime($birthdate));
        
        // Handle profile picture upload
        $picturePath = null;
        $removePicture = isset($_POST['remove_picture']) && $_POST['remove_picture'] == '1';
        
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $picturePath = handleImageUpload($_FILES['profile_picture'], $adminId);
            if ($picturePath === false) {
                echo json_encode(['success' => false, 'message' => 'Error uploading profile picture']);
                return;
            }
        }
        
        // Build update query
        if ($picturePath !== null) {
            // Update with new picture
            $stmt = $pdo->prepare("UPDATE admins SET first_name = ?, middle_name = ?, last_name = ?, barangay_position = ?, birthdate = ?, picture = ? WHERE id = ?");
            $result = $stmt->execute([$firstName, $middleName, $lastName, $barangayPosition, $formattedBirthdate, $picturePath, $adminId]);
        } elseif ($removePicture) {
            // Remove picture
            $stmt = $pdo->prepare("UPDATE admins SET first_name = ?, middle_name = ?, last_name = ?, barangay_position = ?, birthdate = ?, picture = NULL WHERE id = ?");
            $result = $stmt->execute([$firstName, $middleName, $lastName, $barangayPosition, $formattedBirthdate, $adminId]);
        } else {
            // Update without changing picture
            $stmt = $pdo->prepare("UPDATE admins SET first_name = ?, middle_name = ?, last_name = ?, barangay_position = ?, birthdate = ? WHERE id = ?");
            $result = $stmt->execute([$firstName, $middleName, $lastName, $barangayPosition, $formattedBirthdate, $adminId]);
        }
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Admin updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update admin']);
        }
        
    } catch (PDOException $e) {
        error_log("Error updating admin: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

// Function to archive admin
function archiveAdmin($pdo) {
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection not available']);
        return;
    }
    
    if (!isset($_POST['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Admin ID is required']);
        return;
    }
    
    $adminId = $_POST['admin_id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE admins SET account_status = 'inactive' WHERE id = ?");
        $result = $stmt->execute([$adminId]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Admin archived successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Admin not found or already archived']);
        }
    } catch (PDOException $e) {
        error_log("Error archiving admin: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

// Function to restore admin
function restoreAdmin($pdo) {
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection not available']);
        return;
    }
    
    if (!isset($_POST['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Admin ID is required']);
        return;
    }
    
    $adminId = $_POST['admin_id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE admins SET account_status = 'active' WHERE id = ?");
        $result = $stmt->execute([$adminId]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Admin restored successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Admin not found or already active']);
        }
    } catch (PDOException $e) {
        error_log("Error restoring admin: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

// Function to delete admin
function deleteAdmin($pdo) {
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection not available']);
        return;
    }
    
    if (!isset($_POST['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Admin ID is required']);
        return;
    }
    
    $adminId = $_POST['admin_id'];
    
    try {
        // First get the admin data to delete profile picture if exists
        $stmt = $pdo->prepare("SELECT picture FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && !empty($admin['picture'])) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $admin['picture'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        
        // Delete the admin record
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        $result = $stmt->execute([$adminId]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Admin deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Admin not found']);
        }
    } catch (PDOException $e) {
        error_log("Error deleting admin: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

// Function to handle image upload
function handleImageUpload($file, $adminId) {
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/ALERTPOINT/uploads/admin_pictures/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            error_log("Failed to create upload directory: " . $uploadDir);
            return false;
        }
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        error_log("Invalid file type: " . $mimeType);
        return false;
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        error_log("File size too large: " . $file['size']);
        return false;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'admin_' . $adminId . '_' . time() . '.' . strtolower($extension);
    $uploadPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Return relative path for database storage
        return '/ALERTPOINT/uploads/admin_pictures/' . $filename;
    } else {
        error_log("Failed to move uploaded file to: " . $uploadPath);
        return false;
    }
}
?>