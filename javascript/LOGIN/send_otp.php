<?php
// File: /ALERTPOINT/javascript/LOGIN/send_otp.php
session_start();

// Add these imports at the top of send_otp.php (after session_start())
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../../vendor/autoload.php'; // Add this line


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

// Validate input
if (empty($email)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email address is required',
        'field' => 'email'
    ]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address',
        'field' => 'email'
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
    
    // Check if email exists in database
    $stmt = $pdo->prepare("SELECT admin_id, first_name, user_email, account_status FROM admins_tbl WHERE user_email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo json_encode([
            'success' => false,
            'message' => 'Email address not found in our system',
            'field' => 'email'
        ]);
        exit;
    }
    
    // Check account status
    if ($admin['account_status'] !== 'active') {
        echo json_encode([
            'success' => false,
            'message' => 'Your account is ' . $admin['account_status'] . '. Please contact the administrator.',
            'field' => 'email'
        ]);
        exit;
    }
    
    // Check for existing pending OTP requests (rate limiting)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempt_count, MAX(request_time) as last_request 
        FROM password_reset_otps 
        WHERE user_email = ? AND request_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$email]);
    $rate_check = $stmt->fetch();
    
    // Check if user has exceeded 3 attempts in the last hour
    if ($rate_check['attempt_count'] >= 3) {
        $last_request_time = new DateTime($rate_check['last_request']);
        $now = new DateTime();
        $time_diff = $now->getTimestamp() - $last_request_time->getTimestamp();
        $minutes_left = 60 - floor($time_diff / 60);
        
        echo json_encode([
            'success' => false,
            'message' => "Too many OTP requests. Please try again in {$minutes_left} minutes.",
            'field' => 'general',
            'rate_limited' => true,
            'minutes_left' => $minutes_left
        ]);
        exit;
    }
    
    // Generate 6-digit OTP
    $otp_code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    
    // Get client info
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Calculate expiration time (10 minutes from now)
    $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // Insert OTP into database
    $stmt = $pdo->prepare("
        INSERT INTO password_reset_otps (admin_id, user_email, otp_code, expires_at, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$admin['admin_id'], $email, $otp_code, $expires_at, $ip_address, $user_agent]);
    
    // Send email with OTP (you'll need to configure this)
    $email_sent = sendOTPEmail($email, $otp_code, $admin['first_name']);
    
    if ($email_sent) {
        echo json_encode([
            'success' => true,
            'message' => 'OTP has been sent to your email address. Please check your inbox.',
            'expires_in' => 300 // 5 minutes in seconds
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send OTP email. Please try again.',
            'field' => 'general'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Send OTP error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while sending OTP. Please try again.',
        'field' => 'general'
    ]);
}

// Function to send OTP email
function sendOTPEmail($to_email, $otp_code, $first_name) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Change to your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'alertpoint.mrc@gmail.com'; // Your email
        $mail->Password   = 'ekoeiclfidgaaxko'; // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('alertpoint.mrc@gmail.com', 'AlertPoint Security Team');
        $mail->addAddress($to_email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP - AlertPoint System';
        
        // HTML email content (same as before)
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #3B82F6, #1E40AF); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .otp-box { background: white; border: 2px solid #3B82F6; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                .otp-code { font-size: 32px; font-weight: bold; color: #1E40AF; letter-spacing: 8px; }
                .warning { background: #FEF3C7; border-left: 4px solid #F59E0B; padding: 15px; margin: 20px 0; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🛡️ AlertPoint Security</h1>
                    <p>Password Reset Request</p>
                </div>
                <div class='content'>
                    <h2>Hello {$first_name},</h2>
                    <p>We received a request to reset your password for your AlertPoint account. To proceed with the password reset, please use the following One-Time Password (OTP):</p>
                    
                    <div class='otp-box'>
                        <div class='otp-code'>{$otp_code}</div>
                        <p><strong>This OTP is valid for 5 minutes only</strong></p>
                    </div>
                    
                    <div class='warning'>
                        <strong>⚠️ Security Notice:</strong>
                        <ul>
                            <li>Never share this OTP with anyone</li>
                            <li>AlertPoint staff will never ask for your OTP</li>
                            <li>If you didn't request this, please contact support immediately</li>
                            <li>This OTP will expire in 5 minutes</li>
                        </ul>
                    </div>
                    
                    <p>If you didn't request a password reset, please ignore this email and contact our support team immediately at <strong>support@alertpoint.gov.ph</strong></p>
                    
                    <p>Stay safe,<br>
                    <strong>AlertPoint Security Team</strong></p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from AlertPoint Emergency Management System<br>
                    Barangay 170, Caloocan City | © 2025 AlertPoint. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>