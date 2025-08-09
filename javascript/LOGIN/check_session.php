<?php
// Session check for protected pages
session_start();

function checkAdminSession() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    
    // Check session timeout
    // if (isset($_SESSION['login_time'])) {
    //     $session_duration = 3600; // 1 hour in seconds
    //     $current_time = time();
        
    //     if (($current_time - $_SESSION['login_time']) > $session_duration) {
    //         // Session expired - update user status before destroying session
    //         $admin_id = $_SESSION['admin_id'] ?? null;
    //         if ($admin_id) {
    //             updateUserStatusOnExpiry($admin_id);
    //         }
            
    //         session_destroy();
    //         return false;
    //     }
    // }
    
    return true;
}

function redirectToLogin() {
    // CHANGE THIS LINE - redirect to login.php instead of Login.html
    header('Location: /ALERTPOINT/html/login.php');
    exit();
}

function getCurrentAdmin() {
    if (!checkAdminSession()) {
        return null;
    }
    
    // Return session data - make sure your login script sets these session variables
    return [
        'admin_id' => $_SESSION['admin_id'] ?? '',
        'first_name' => $_SESSION['admin_first_name'] ?? '',
        'middle_name' => $_SESSION['admin_middle_name'] ?? '', 
        'last_name' => $_SESSION['admin_last_name'] ?? '',
        'username' => $_SESSION['admin_username'] ?? '',
        'email' => $_SESSION['admin_email'] ?? '',
        'barangay_position' => $_SESSION['admin_position'] ?? '',
        'picture' => $_SESSION['admin_picture'] ?? '',
        'name' => $_SESSION['admin_name'] ?? '' // Keep for backward compatibility
    ];
}

function getCurrentAdminWithDB($pdo = null) {
    if (!checkAdminSession()) {
        return null;
    }
    
    $admin_id = $_SESSION['admin_id'] ?? null;
    
    if (!$admin_id || !$pdo) {
        // Fallback to session data
        return getCurrentAdmin();
    }
    
    // Fetch complete admin details from database using passed PDO connection
    try {
        $stmt = $pdo->prepare("SELECT admin_id, first_name, middle_name, last_name, username, email, barangay_position, picture FROM admins_tbl WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $adminData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($adminData) {
            return $adminData;
        }
    } catch (Exception $e) {
        error_log("Error fetching admin data: " . $e->getMessage());
    }
    
    // Fallback to session data if database fetch fails
    return getCurrentAdmin();
}

function updateUserStatusOnExpiry($admin_id) {
    try {
        require_once '../../config/database.php';
        $database = new Database();
        $pdo = $database->getConnection();
        
        if ($pdo && $admin_id) {
            $stmt = $pdo->prepare("UPDATE admins_tbl SET user_status = 'offline' WHERE admin_id = ?");
            $stmt->execute([$admin_id]);
        }
    } catch (Exception $e) {
        error_log("Error updating user status on session expiry: " . $e->getMessage());
    }
}

?>