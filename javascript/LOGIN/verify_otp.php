<?php
// File: /ALERTPOINT/javascript/LOGIN/verify_otp.php
session_start();
require_once '../../config/database.php';

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$email = trim($input['email'] ?? '');
$otp_code = trim($input['otp_code'] ?? '');

// Validate input
if (empty($email) || empty($otp_code)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email and OTP code are required',
        'field' => empty($email) ? 'email' : 'otp_code'
    ]);
    exit;
}

// Validate OTP format (6 digits)
if (!preg_match('/^\d{6}$/', $otp_code)) {
    echo json_encode([
        'success' => false,
        'message' => 'OTP must be 6 digits',
        'field' => 'otp_code'
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
    
    // Get the latest OTP for this email
    $stmt = $pdo->prepare("
        SELECT id, admin_id, otp_code, expires_at, attempts, is_used, status 
        FROM password_reset_otps 
        WHERE user_email = ? 
        AND status = 'pending' 
        ORDER BY request_time DESC 
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $otp_record = $stmt->fetch();
    
    if (!$otp_record) {
        echo json_encode([
            'success' => false,
            'message' => 'No valid OTP found. Please request a new one.',
            'field' => 'otp_code'
        ]);
        exit;
    }
    
    // Check if OTP is expired
    $current_time = new DateTime();
    $expires_at = new DateTime($otp_record['expires_at']);
    
    if ($current_time > $expires_at) {
        // Mark as expired
        $stmt = $pdo->prepare("UPDATE password_reset_otps SET status = 'expired' WHERE id = ?");
        $stmt->execute([$otp_record['id']]);
        
        echo json_encode([
            'success' => false,
            'message' => 'OTP has expired. Please request a new one.',
            'field' => 'otp_code',
            'expired' => true
        ]);
        exit;
    }
    
    // Check if OTP is already used
    if ($otp_record['is_used']) {
        echo json_encode([
            'success' => false,
            'message' => 'This OTP has already been used. Please request a new one.',
            'field' => 'otp_code'
        ]);
        exit;
    }
    
    // Increment attempts
    $new_attempts = $otp_record['attempts'] + 1;
    $stmt = $pdo->prepare("UPDATE password_reset_otps SET attempts = ? WHERE id = ?");
    $stmt->execute([$new_attempts, $otp_record['id']]);
    
    // Check if maximum attempts reached (3 attempts)
    if ($new_attempts > 3) {
        // Block this OTP
        $stmt = $pdo->prepare("UPDATE password_reset_otps SET status = 'blocked' WHERE id = ?");
        $stmt->execute([$otp_record['id']]);
        
        echo json_encode([
            'success' => false,
            'message' => 'Maximum attempts reached. Please request a new OTP.',
            'field' => 'otp_code',
            'blocked' => true
        ]);
        exit;
    }
    
    // Verify OTP code
    if ($otp_code !== $otp_record['otp_code']) {
        $remaining_attempts = 3 - $new_attempts;
        echo json_encode([
            'success' => false,
            'message' => "Invalid OTP code. {$remaining_attempts} attempt(s) remaining.",
            'field' => 'otp_code',
            'remaining_attempts' => $remaining_attempts
        ]);
        exit;
    }
    
    // OTP is valid - mark as used
    $stmt = $pdo->prepare("UPDATE password_reset_otps SET is_used = TRUE, status = 'used' WHERE id = ?");
    $stmt->execute([$otp_record['id']]);
    
    // Store verified session data for password reset
    $_SESSION['password_reset_verified'] = true;
    $_SESSION['password_reset_admin_id'] = $otp_record['admin_id'];
    $_SESSION['password_reset_email'] = $email;
    $_SESSION['password_reset_time'] = time();
    
    echo json_encode([
        'success' => true,
        'message' => 'OTP verified successfully. You can now reset your password.',
        'admin_id' => $otp_record['admin_id']
    ]);
    
} catch (Exception $e) {
    error_log("Verify OTP error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while verifying OTP. Please try again.',
        'field' => 'general'
    ]);
}
?>