<?php
header('Content-Type: application/json');

require_once '../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Count total active admins
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins_tbl WHERE account_status = 'active'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalActive = $result['count'];
    
    // Count archived/inactive admins
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins_tbl WHERE account_status IN ('inactive', 'suspended')");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalArchived = $result['count'];
    
    echo json_encode([
        'success' => true,
        'totalActive' => $totalActive,
        'totalArchived' => $totalArchived
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>