<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

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
    'data' => null,
    'debug' => [] // Add debug info
];

try {
    // Debug: Log all POST data
    error_log("POST data received: " . print_r($_POST, true));
    error_log("FILES data received: " . print_r($_FILES, true));
    
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
    
    // Validate required fields
    if (!isset($_POST['admin_id']) || empty(trim($_POST['admin_id']))) {
        throw new Exception('Admin ID is required');
    }
    
    if (!isset($_POST['barangay_position']) || empty(trim($_POST['barangay_position']))) {
        throw new Exception('Barangay position is required');
    }
    
    $adminId = trim($_POST['admin_id']);
    $barangayPosition = trim($_POST['barangay_position']);
    
    // Check if photo should be removed
    $removePhoto = isset($_POST['remove_photo']) && $_POST['remove_photo'] === 'true';
    
    // Debug logging
    error_log("Admin ID: " . $adminId);
    error_log("Barangay Position: " . $barangayPosition);
    error_log("Remove Photo Flag: " . ($removePhoto ? 'true' : 'false'));
    error_log("Current Admin ID (performing update): " . $currentAdminId);
    
    $response['debug'][] = "Remove photo flag: " . ($removePhoto ? 'true' : 'false');
    
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
            SELECT admin_id, first_name, middle_name, last_name, picture, barangay_position 
            FROM admins_tbl 
            WHERE admin_id = ? AND account_status = 'active'
        ");
        $checkStmt->execute([$adminId]);
        $existingAdmin = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingAdmin) {
            throw new Exception('Admin not found or not active');
        }
        
        error_log("Existing admin data: " . print_r($existingAdmin, true));
        $response['debug'][] = "Current picture in DB: " . ($existingAdmin['picture'] ?? 'NULL');
        
        // Store original values for logging
        $originalPosition = $existingAdmin['barangay_position'];
        $originalPicture = $existingAdmin['picture'];
        $adminFullName = trim($existingAdmin['first_name'] . ' ' . ($existingAdmin['middle_name'] ? $existingAdmin['middle_name'] . ' ' : '') . $existingAdmin['last_name']);
        
        // Handle profile image operations
        $newPicturePath = $existingAdmin['picture']; // Keep current path by default
        $pictureUpdated = false;
        $pictureAction = 'no_change';
        
        if ($removePhoto) {
            // Remove photo - set to NULL in database
            $newPicturePath = null;
            $pictureUpdated = true;
            $pictureAction = 'removed';
            
            error_log("Photo removal requested - setting picture to NULL");
            $response['debug'][] = "Photo removal requested - setting picture to NULL";
            
            // Delete the old image file
            if (!empty($existingAdmin['picture'])) {
                deleteOldImage($existingAdmin['picture']);
                $response['debug'][] = "Attempted to delete old image: " . $existingAdmin['picture'];
            }
            
        } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            // Upload new image
            $newPicturePath = handleImageUpload($_FILES['profile_image'], $adminId);
            $pictureUpdated = true;
            $pictureAction = 'updated';
            
            error_log("New image uploaded: " . $newPicturePath);
            $response['debug'][] = "New image uploaded: " . $newPicturePath;
            
            // Delete the old image file
            if (!empty($existingAdmin['picture'])) {
                deleteOldImage($existingAdmin['picture']);
                $response['debug'][] = "Deleted old image: " . $existingAdmin['picture'];
            }
        }
        
        // Always update both position and picture to ensure database reflects changes
        $updateStmt = $pdo->prepare("
            UPDATE admins_tbl 
            SET barangay_position = ?, picture = ?, last_active = NOW() 
            WHERE admin_id = ? AND account_status = 'active'
        ");
        $updateResult = $updateStmt->execute([$barangayPosition, $newPicturePath, $adminId]);
        
        error_log("Update query executed with values: position=" . $barangayPosition . ", picture=" . ($newPicturePath ?? 'NULL') . ", admin_id=" . $adminId);
        $response['debug'][] = "Update executed - Position: " . $barangayPosition . ", Picture: " . ($newPicturePath ?? 'NULL');
        
        if (!$updateResult) {
            throw new Exception('Failed to update admin information');
        }
        
        // Check if any rows were affected
        $rowsAffected = $updateStmt->rowCount();
        error_log("Rows affected: " . $rowsAffected);
        $response['debug'][] = "Rows affected: " . $rowsAffected;
        
        if ($rowsAffected === 0) {
            throw new Exception('No changes were made or admin not found');
        }
        
        // Prepare logging details
        $changes = [];
        $positionChanged = $originalPosition !== $barangayPosition;
        
        if ($positionChanged) {
            $changes[] = "Position: '{$originalPosition}' → '{$barangayPosition}'";
        }
        
        if ($pictureUpdated) {
            switch ($pictureAction) {
                case 'removed':
                    $changes[] = "Profile picture: removed";
                    break;
                case 'updated':
                    $changes[] = "Profile picture: updated";
                    break;
            }
        }
        
        // Log the update action using the system logger (similar to archive_admin.php)
        $logDetails = json_encode([
            'updated_admin_id' => $adminId,
            'updated_admin_name' => $adminFullName,
            'updated_admin_position' => $barangayPosition,
            'changes_made' => $changes,
            'position_changed' => $positionChanged,
            'original_position' => $originalPosition,
            'new_position' => $barangayPosition,
            'picture_updated' => $pictureUpdated,
            'picture_action' => $pictureAction,
            'original_picture' => $originalPicture,
            'new_picture' => $newPicturePath,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $currentAdmin['first_name'] . ' ' . $currentAdmin['last_name']
        ]);
        
        // Log the admin update action
        $logSuccess = logAdminAction($pdo, $currentAdminId, 'updated', $adminId, $adminFullName, $logDetails);
        
        if (!$logSuccess) {
            error_log("Warning: Failed to log admin update action");
        } else {
            error_log("Successfully logged admin update action");
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Success response
        $photoActionText = 'No changes';
        if ($removePhoto) {
            $photoActionText = 'removed';
        } elseif ($pictureUpdated && $newPicturePath !== null) {
            $photoActionText = 'updated';
        }
        
        $response['success'] = true;
        $response['message'] = "Admin '{$adminFullName}' updated successfully" . ($pictureUpdated ? " (Profile picture {$photoActionText})" : "");
        $response['data'] = [
            'admin_id' => $adminId,
            'admin_name' => $adminFullName,
            'barangay_position' => $barangayPosition,
            'position_changed' => $positionChanged,
            'picture_updated' => $pictureUpdated,
            'picture_removed' => $removePhoto,
            'picture_action' => $pictureAction,
            'new_picture_path' => $newPicturePath,
            'changes_summary' => implode(', ', $changes),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        error_log("Success response prepared: " . print_r($response, true));
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollback();
        
        error_log("Transaction rolled back due to error: " . $e->getMessage());
        
        // If we uploaded a file during this transaction, clean it up
        if (isset($newPicturePath) && $newPicturePath && $newPicturePath !== $existingAdmin['picture'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $newPicturePath)) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $newPicturePath);
            error_log("Cleaned up uploaded file due to transaction rollback");
        }
        
        throw $e;
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Log error for debugging
    error_log("Update Admin Error: " . $e->getMessage());
    error_log("Update Admin Stack Trace: " . $e->getTraceAsString());
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error occurred';
    
    // Log detailed error for debugging
    error_log("Update Admin PDO Error: " . $e->getMessage());
    error_log("Update Admin PDO Stack Trace: " . $e->getTraceAsString());
}

// Return JSON response
error_log("Update Admin - Final response: " . json_encode($response));
echo json_encode($response);

/**
 * Handle image upload
 */
function handleImageUpload($file, $adminId) {
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, JPEG, and PNG are allowed.');
    }
    
    if ($file['size'] > $maxFileSize) {
        throw new Exception('File size too large. Maximum size is 5MB.');
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/ALERTPOINT/uploads/admin/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    // Generate unique filename
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = 'admin_' . uniqid() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Failed to upload file');
    }
    
    // Return the relative path for database storage
    return '/ALERTPOINT/uploads/admin/' . $fileName;
}

/**
 * Delete old image file
 */
function deleteOldImage($oldPicturePath) {
    if (empty($oldPicturePath) || $oldPicturePath === 'NULL' || strtolower($oldPicturePath) === 'null') {
        error_log("No old image to delete (empty or NULL path)");
        return;
    }
    
    try {
        // Convert path to absolute path
        $absolutePath = '';
        
        if (strpos($oldPicturePath, '/ALERTPOINT/') === 0) {
            $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $oldPicturePath;
        } elseif (strpos($oldPicturePath, '../../') === 0) {
            $absolutePath = $_SERVER['DOCUMENT_ROOT'] . str_replace('../../', '/ALERTPOINT/', $oldPicturePath);
        } else {
            $absolutePath = $_SERVER['DOCUMENT_ROOT'] . '/ALERTPOINT/' . ltrim($oldPicturePath, '/');
        }
        
        error_log("Attempting to delete file at: " . $absolutePath);
        
        // Delete file if it exists
        if (file_exists($absolutePath)) {
            if (unlink($absolutePath)) {
                error_log("Successfully deleted old image: " . $absolutePath);
            } else {
                error_log("Failed to delete file (unlink failed): " . $absolutePath);
            }
        } else {
            error_log("Old image file not found: " . $absolutePath);
        }
    } catch (Exception $e) {
        // Log error but don't throw - deleting old image is not critical
        error_log("Failed to delete old image: " . $e->getMessage());
    }
}
?>