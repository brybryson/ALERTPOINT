<?php
session_start();
require_once '../../config/database.php';

// Function to log logout activity
function logLogoutActivity($pdo, $admin_id) {
    try {
        // Get current last_active before updating
        $stmt = $pdo->prepare("SELECT last_active FROM admins_tbl WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $current_last_active = $stmt->fetchColumn();
        
        // Update user status to offline and last_active timestamp
        $stmt = $pdo->prepare("UPDATE admins_tbl SET user_status = 'offline', last_active = NOW() WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        
        // Log the logout activity
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, previous_last_active, ip_address, user_agent, login_status) 
            VALUES (?, ?, ?, ?, 'logout')
        ");
        $stmt->execute([$admin_id, $current_last_active, $ip_address, $user_agent]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error logging logout activity: " . $e->getMessage());
        return false;
    }
}

// Check if admin is logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $admin_id = $_SESSION['admin_id'] ?? null;
    
    if ($admin_id) {
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            
            if ($pdo) {
                logLogoutActivity($pdo, $admin_id);
            }
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
        }
    }
}

// Destroy session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// CHANGE THIS LINE - redirect to login.php instead of Login.html
header('Location: /ALERTPOINT/html/login.php');
exit();
?>