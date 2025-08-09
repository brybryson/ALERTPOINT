<?php
// Create this as a new file: ../functions/system_action_logger.php

function logSystemAction($pdo, $adminId, $actionType, $actionSummary, $actionDetails = null, $targetType = null, $targetId = null, $targetName = null) {
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO system_action_logs 
            (admin_id, action_type, action_summary, action_details, target_type, target_id, target_name, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $adminId,
            $actionType,
            $actionSummary,
            $actionDetails,
            $targetType,
            $targetId,
            $targetName,
            $ipAddress,
            $userAgent
        ]);
    } catch (Exception $e) {
        error_log("System action logging failed: " . $e->getMessage());
        return false;
    }
}

// Specific logging functions for common actions
function logUserAction($pdo, $adminId, $action, $userId, $userName, $details = null) {
    $actionSummary = ucfirst($action) . " user: " . $userName;
    logSystemAction($pdo, $adminId, 'user_management', $actionSummary, $details, 'user', $userId, $userName);
}

function logAdminAction($pdo, $adminId, $action, $targetAdminId, $targetAdminName, $details = null) {
    $actionSummary = ucfirst($action) . " admin: " . $targetAdminName;
    logSystemAction($pdo, $adminId, 'admin_management', $actionSummary, $details, 'admin', $targetAdminId, $targetAdminName);
}

// NEW: Specific function for admin creation logging
function logAdminCreation($pdo, $creatorAdminId, $newAdminId, $newAdminName, $newAdminEmail, $newAdminRole) {
    $actionSummary = "Created new admin account: " . $newAdminName;
    $actionDetails = json_encode([
        'admin_id' => $newAdminId,
        'admin_name' => $newAdminName,
        'email' => $newAdminEmail,
        'role' => $newAdminRole,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    return logSystemAction(
        $pdo, 
        $creatorAdminId, 
        'admin_management', 
        $actionSummary, 
        $actionDetails, 
        'admin', 
        $newAdminId, 
        $newAdminName
    );
}

function logSystemMaintenance($pdo, $adminId, $action, $details = null) {
    $actionSummary = ucfirst($action);
    logSystemAction($pdo, $adminId, 'system_maintenance', $actionSummary, $details, 'system');
}

function logReportGeneration($pdo, $adminId, $reportType, $details = null) {
    $actionSummary = "Generated " . $reportType . " report";
    logSystemAction($pdo, $adminId, 'report_generation', $actionSummary, $details, 'report');
}

function logDataExport($pdo, $adminId, $exportType, $filters = null, $recordCount = null) {
    $actionSummary = "Exported " . $exportType . " data";
    $details = "Filters applied: " . ($filters ?: 'None');
    if ($recordCount !== null) {
        $details .= " | Records exported: " . $recordCount;
    }
    logSystemAction($pdo, $adminId, 'data_export', $actionSummary, $details, 'data');
}
?>