<?php

// ADD THIS AT THE VERY TOP OF Activity_Logs_2.php (before any other PHP code)
require_once '../javascript/LOGIN/check_session.php';

// Check if user is logged in, if not redirect to login
if (!checkAdminSession()) {
    redirectToLogin();
}

// ADD THESE CACHE CONTROL HEADERS RIGHT AFTER SESSION CHECK
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Past date

// Prevent page caching in browser back button
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Database connection
require_once '../config/database.php';

// Add this line after require_once '../config/database.php';
require_once '../functions/system_action_logger.php';

// Add this after your existing require_once statements
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Initialize variables with default values
$systemLogs = [];
$totalSystemLogs = 0;
$totalDataExports = 0;
$totalSystemMaintenance = 0;
$totalUserManagement = 0;
$totalOtherActions = 0;
$pdo = null;
$currentAdmin = null; // Initialize currentAdmin

// Pagination settings
$logsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $logsPerPage;

// Filter settings - Enhanced with date range support
$filterType = isset($_GET['type']) ? $_GET['type'] : 'all';
$filterTimeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : 'today'; // Changed default to 'today'
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Database connection and log fetching
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // NOW get current admin info AFTER database connection is established
    $currentAdmin = function_exists('getCurrentAdminWithDB') && $pdo ? getCurrentAdminWithDB($pdo) : getCurrentAdmin();
    
    if ($pdo) {
        // Build WHERE clause for filters
        $whereConditions = [];
        $params = [];
        
        // Action type filter
        if ($filterType !== 'all') {
            $whereConditions[] = "sal.action_type = :type";
            $params[':type'] = $filterType;
        }
        
        // Timeframe filter
        if ($filterTimeframe !== 'all') {
            switch ($filterTimeframe) {
                case 'today':
                    $whereConditions[] = "DATE(sal.action_date_time) = CURDATE()";
                    break;
                case 'week':
                    $whereConditions[] = "sal.action_date_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $whereConditions[] = "sal.action_date_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'year':
                    $whereConditions[] = "sal.action_date_time >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                    break;
                case 'custom':
                    if (!empty($startDate) && !empty($endDate)) {
                        $whereConditions[] = "DATE(sal.action_date_time) BETWEEN :start_date AND :end_date";
                        $params[':start_date'] = $startDate;
                        $params[':end_date'] = $endDate;
                    } elseif (!empty($startDate)) {
                        $whereConditions[] = "DATE(sal.action_date_time) >= :start_date";
                        $params[':start_date'] = $startDate;
                    } elseif (!empty($endDate)) {
                        $whereConditions[] = "DATE(sal.action_date_time) <= :end_date";
                        $params[':end_date'] = $endDate;
                    }
                    break;
            }
        }
        
        // Search filter
        if (!empty($searchTerm)) {
            $whereConditions[] = "(CONCAT(a.first_name, ' ', COALESCE(a.middle_name, ''), ' ', a.last_name) LIKE :search OR a.username LIKE :search OR sal.admin_id LIKE :search OR sal.action_summary LIKE :search OR sal.action_details LIKE :search)";
            $params[':search'] = '%' . $searchTerm . '%';
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Count total logs for pagination
        $countQuery = "SELECT COUNT(*) as total FROM system_action_logs sal 
                       LEFT JOIN admins_tbl a ON sal.admin_id = a.admin_id 
                       $whereClause";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalSystemLogs = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Fetch system action logs with pagination
        $query = "SELECT sal.*, 
                         a.first_name, a.middle_name, a.last_name, a.username, a.picture,
                         a.barangay_position
                  FROM system_action_logs sal 
                  LEFT JOIN admins_tbl a ON sal.admin_id = a.admin_id 
                  $whereClause
                  ORDER BY sal.action_date_time DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($query);
        
        // Bind pagination parameters
        $stmt->bindValue(':limit', $logsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        // Bind filter parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $systemLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get statistics
        $statsWhereClause = $whereClause; // Use same WHERE clause as main query
        $statsQuery = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN action_type = 'data_export' THEN 1 ELSE 0 END) as data_exports,
                SUM(CASE WHEN action_type = 'system_maintenance' THEN 1 ELSE 0 END) as system_maintenance,
                SUM(CASE WHEN action_type = 'admin_management' THEN 1 ELSE 0 END) as user_management,
                SUM(CASE WHEN action_type NOT IN ('data_export', 'system_maintenance', 'admin_management') THEN 1 ELSE 0 END) as other_actions
            FROM system_action_logs sal 
            LEFT JOIN admins_tbl a ON sal.admin_id = a.admin_id 
            $statsWhereClause";
        $statsStmt = $pdo->prepare($statsQuery);
        $statsStmt->execute($params); // Use same params as main query
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

        $totalSystemLogsAll = $stats['total'];
        $totalDataExports = $stats['data_exports'];
        $totalSystemMaintenance = $stats['system_maintenance'];
        $totalUserManagement = $stats['user_management'];
        $totalOtherActions = $stats['other_actions'];
        
    }
} catch (Exception $e) {
    // Log error and use fallback values
    error_log("Database error in Activity_Logs_2.php: " . $e->getMessage());
    $systemLogs = [];
    $totalSystemLogs = 0;
    $totalDataExports = 0;
    $totalSystemMaintenance = 0;
    $totalUserManagement = 0;
    $totalOtherActions = 0;
    $pdo = null;
}

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    // Apply filters to Excel export query
    try {
        if ($pdo) {
            // Use the same query logic for export but without LIMIT
            $exportQuery = "SELECT sal.*, 
                                   a.first_name, a.middle_name, a.last_name, a.username, a.picture,
                                   a.barangay_position
                            FROM system_action_logs sal 
                            LEFT JOIN admins_tbl a ON sal.admin_id = a.admin_id 
                            $whereClause
                            ORDER BY sal.action_date_time DESC";
            
            $exportStmt = $pdo->prepare($exportQuery);
            foreach ($params as $key => $value) {
                $exportStmt->bindValue($key, $value);
            }
            $exportStmt->execute();
            $systemLogsForExport = $exportStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // LOG THE EXPORT ACTION
            if ($currentAdmin && isset($currentAdmin['admin_id'])) {
                $exportFilters = [
                    'timeframe' => $filterTimeframe,
                    'type' => $filterType,
                    'search' => $searchTerm,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ];
                $filterDetails = json_encode(array_filter($exportFilters));
                
                // Log to system_action_logs
                try {
                    $logQuery = "INSERT INTO system_action_logs (admin_id, action_type, action_summary, action_details, target_type, target_name, ip_address, user_agent) 
                                VALUES (:admin_id, :action_type, :action_summary, :action_details, :target_type, :target_name, :ip_address, :user_agent)";
                    $logStmt = $pdo->prepare($logQuery);
                    $logStmt->execute([
                        ':admin_id' => $currentAdmin['admin_id'],
                        ':action_type' => 'data_export',
                        ':action_summary' => 'Exported system action logs',
                        ':action_details' => "Exported " . count($systemLogsForExport) . " system action log entries with filters: " . $filterDetails,
                        ':target_type' => 'data',
                        ':target_name' => 'system_action_logs',
                        ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ]);
                } catch (Exception $e) {
                    error_log("Failed to log export action: " . $e->getMessage());
                }
            }
        } else {
            $systemLogsForExport = [];
        }
    } catch (Exception $e) {
        $systemLogsForExport = [];
    }

    // Create new Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Get current admin full name (for export header)
    $currentAdminName = 'Unknown Admin';
    $currentAdminId = 'N/A';

    if ($currentAdmin && is_array($currentAdmin)) {
        $currentAdminName = trim(
            ($currentAdmin['first_name'] ?? '') . ' ' . 
            ($currentAdmin['middle_name'] ?? '') . ' ' . 
            ($currentAdmin['last_name'] ?? '')
        );
        $currentAdminId = $currentAdmin['admin_id'] ?? 'N/A';
        
        // Clean up extra spaces
        $currentAdminName = preg_replace('/\s+/', ' ', $currentAdminName);
        if (trim($currentAdminName) === '') {
            $currentAdminName = 'Unknown Admin';
        }
    }
    
    // Format date as "August 7, 2025 1:29 AM"
    $reportDate = date('F j, Y g:i A');
    
    // Generate filter description for header
    $filterDescription = 'All Time';
    if ($filterTimeframe === 'today') {
        $filterDescription = 'Today (' . date('F j, Y') . ')';
    } elseif ($filterTimeframe === 'week') {
        $filterDescription = 'This Week';
    } elseif ($filterTimeframe === 'month') {
        $filterDescription = 'This Month (' . date('F Y') . ')';
    } elseif ($filterTimeframe === 'year') {
        $filterDescription = 'This Year (' . date('Y') . ')';
    } elseif ($filterTimeframe === 'custom') {
        if (!empty($startDate) && !empty($endDate)) {
            $filterDescription = date('F j, Y', strtotime($startDate)) . ' - ' . date('F j, Y', strtotime($endDate));
        } elseif (!empty($startDate)) {
            $filterDescription = 'From ' . date('F j, Y', strtotime($startDate));
        } elseif (!empty($endDate)) {
            $filterDescription = 'Until ' . date('F j, Y', strtotime($endDate));
        }
    }
    
    // Add action type filter info if applied
    $typeFilterInfo = '';
    if ($filterType !== 'all') {
        $typeFilterInfo = ' (Type: ' . ucfirst(str_replace('_', ' ', $filterType)) . ')';
    }
    
    // Add search filter info if applied
    $searchFilterInfo = '';
    if (!empty($searchTerm)) {
        $searchFilterInfo = ' (Search: "' . $searchTerm . '")';
    }
    
    $currentRow = 1;
    
    // Add header information
    $sheet->setCellValue('A' . $currentRow, 'SYSTEM ACTION LOGS STATISTICS');
    $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(16);
    $currentRow += 2;
    
    $sheet->setCellValue('A' . $currentRow, 'Exported by: ' . $currentAdminName);
    $sheet->setCellValue('B' . $currentRow, 'ID: ' . $currentAdminId);
    $currentRow += 2;
    
    $sheet->setCellValue('A' . $currentRow, 'Report Generated: ' . $reportDate);
    $currentRow++;
    
    $sheet->setCellValue('A' . $currentRow, 'Filter Period: ' . $filterDescription . $typeFilterInfo . $searchFilterInfo);
    $currentRow += 2;
    
    // Add statistics
    $sheet->setCellValue('A' . $currentRow, 'Total System Actions');
    $sheet->setCellValue('B' . $currentRow, $totalSystemLogsAll);
    $currentRow++;
    
    $sheet->setCellValue('A' . $currentRow, 'Data Exports');
    $sheet->setCellValue('B' . $currentRow, $totalDataExports);
    $currentRow++;
    
    $sheet->setCellValue('A' . $currentRow, 'System Maintenance');
    $sheet->setCellValue('B' . $currentRow, $totalSystemMaintenance);
    $currentRow++;
    
    $sheet->setCellValue('A' . $currentRow, 'User Management');
    $sheet->setCellValue('B' . $currentRow, $totalUserManagement);
    $currentRow++;
    
    $sheet->setCellValue('A' . $currentRow, 'Other Actions');
    $sheet->setCellValue('B' . $currentRow, $totalOtherActions);
    $currentRow += 3;
    
    // Add table header
    $sheet->setCellValue('A' . $currentRow, 'DETAILED SYSTEM ACTION LOGS');
    $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(14);
    $currentRow++;
    
    // Define headers and their corresponding column widths
    $headers = [
        'A' => ['title' => 'Admin Name', 'width' => 35],
        'B' => ['title' => 'Username', 'width' => 15],
        'C' => ['title' => 'Position', 'width' => 30],
        'D' => ['title' => 'Admin ID', 'width' => 12],
        'E' => ['title' => 'Date Done', 'width' => 25],
        'F' => ['title' => 'Action Type', 'width' => 18],
        'G' => ['title' => 'Details', 'width' => 80],
        'H' => ['title' => 'Browser', 'width' => 12],
        'I' => ['title' => 'Operating System', 'width' => 15],
        'J' => ['title' => 'User Agent', 'width' => 100]
    ];
    
    // Set column headers and widths
    foreach ($headers as $column => $info) {
        $sheet->setCellValue($column . $currentRow, $info['title']);
        $sheet->getColumnDimension($column)->setWidth($info['width']);
        
        // Style the header
        $sheet->getStyle($column . $currentRow)
              ->getFont()
              ->setBold(true);
        
        $sheet->getStyle($column . $currentRow)
              ->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()
              ->setRGB('E5E7EB');
              
        $sheet->getStyle($column . $currentRow)
              ->getBorders()
              ->getAllBorders()
              ->setBorderStyle(Border::BORDER_THIN);
    }
    
    $currentRow++;
    
    // Add data rows
    foreach ($systemLogsForExport as $log) {
        $fullName = getFullName($log['first_name'] ?? '', $log['middle_name'] ?? '', $log['last_name'] ?? '');
        $browser = getBrowserName($log['user_agent'] ?? '');
        $os = getOSName($log['user_agent'] ?? '');
        
        $sheet->setCellValue('A' . $currentRow, $fullName ?: 'Unknown Admin');
        $sheet->setCellValue('B' . $currentRow, $log['username'] ?? 'N/A');
        $sheet->setCellValue('C' . $currentRow, $log['barangay_position'] ?? 'Admin');
        $sheet->setCellValue('D' . $currentRow, $log['admin_id'] ?? 'N/A');
        $sheet->setCellValue('E' . $currentRow, formatDateTime($log['action_date_time']));
        $sheet->setCellValue('F' . $currentRow, ucfirst(str_replace('_', ' ', $log['action_type'] ?? '')));
        $sheet->setCellValue('G' . $currentRow, $log['action_details'] ?? '');
        $sheet->setCellValue('H' . $currentRow, $browser);
        $sheet->setCellValue('I' . $currentRow, $os);
        $sheet->setCellValue('J' . $currentRow, $log['user_agent'] ?? '');
        
        // Add borders to data rows
        foreach (array_keys($headers) as $column) {
            $sheet->getStyle($column . $currentRow)
                  ->getBorders()
                  ->getAllBorders()
                  ->setBorderStyle(Border::BORDER_THIN);
        }
        
        $currentRow++;
    }
    
    // Auto-size columns for better fit (optional, you can remove this if you want exact widths)
    // foreach (array_keys($headers) as $column) {
    //     $sheet->getColumnDimension($column)->setAutoSize(true);
    // }
    
    // Generate filename with filter info
    $filename = 'system_action_logs_';
    
    if ($filterTimeframe === 'today') {
        $filename .= 'today_';
    } elseif ($filterTimeframe === 'week') {
        $filename .= 'this_week_';
    } elseif ($filterTimeframe === 'month') {
        $filename .= 'this_month_';
    } elseif ($filterTimeframe === 'year') {
        $filename .= 'this_year_';
    } elseif ($filterTimeframe === 'custom') {
        if (!empty($startDate) && !empty($endDate)) {
            $filename .= $startDate . '_to_' . $endDate . '_';
        } elseif (!empty($startDate)) {
            $filename .= 'from_' . $startDate . '_';
        } elseif (!empty($endDate)) {
            $filename .= 'until_' . $endDate . '_';
        }
    } elseif ($filterTimeframe === 'all') {
        $filename .= 'all_time_';
    }
    
    $filename .= date('Y-m-d') . '.xlsx';
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Write and output the file
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Handle Clear Old Logs
if (isset($_POST['clear_old_logs']) && $_POST['clear_old_logs'] === 'confirm') {
    try {
        if ($pdo) {
            // Delete logs older than 2 years
            $clearQuery = "DELETE FROM system_action_logs WHERE action_date_time < DATE_SUB(NOW(), INTERVAL 2 YEAR)";
            $clearStmt = $pdo->prepare($clearQuery);
            $clearStmt->execute();
            
            $deletedCount = $clearStmt->rowCount();
            $clearMessage = "Successfully cleared $deletedCount old system action log entries (older than 2 years).";
            
            // LOG THE CLEAR ACTION to system_action_logs
            if ($currentAdmin && isset($currentAdmin['admin_id']) && $deletedCount > 0) {
                try {
                    $logQuery = "INSERT INTO system_action_logs (admin_id, action_type, action_summary, action_details, target_type, target_name, ip_address, user_agent) 
                                VALUES (:admin_id, :action_type, :action_summary, :action_details, :target_type, :target_name, :ip_address, :user_agent)";
                    $logStmt = $pdo->prepare($logQuery);
                    $logStmt->execute([
                        ':admin_id' => $currentAdmin['admin_id'],
                        ':action_type' => 'system_maintenance',
                        ':action_summary' => 'Cleared old system action logs',
                        ':action_details' => "Cleared $deletedCount system action log entries older than 2 years",
                        ':target_type' => 'system',
                        ':target_name' => 'system_action_logs',
                        ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ]);
                } catch (Exception $e) {
                    error_log("Failed to log clear action: " . $e->getMessage());
                }
            }
            
            // Redirect to avoid resubmission
            header("Location: " . $_SERVER['PHP_SELF'] . "?cleared=" . $deletedCount);
            exit;
        }
    } catch (Exception $e) {
        $clearError = "Error clearing logs: " . $e->getMessage();
    }
}

// Show success message if redirected after clearing
if (isset($_GET['cleared'])) {
    $clearMessage = "Successfully cleared " . intval($_GET['cleared']) . " old log entries.";
}

// Calculate pagination
$totalPages = ceil($totalSystemLogs / $logsPerPage);

// Helper functions
function formatDateTime($datetime) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return "Never";
    }
    
    try {
        $date = new DateTime($datetime);
        return $date->format('M j, Y - g:i A');
    } catch (Exception $e) {
        return "Unknown";
    }
}

function getTimeAgo($datetime) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return "Never";
    }
    
    try {
        $now = new DateTime();
        $logTime = new DateTime($datetime);
        $diff = $now->diff($logTime);
        
        if ($diff->days > 0) {
            return $diff->days == 1 ? "1 day ago" : $diff->days . " days ago";
        } elseif ($diff->h > 0) {
            return $diff->h == 1 ? "1 hour ago" : $diff->h . " hours ago";
        } elseif ($diff->i > 1) {
            return $diff->i . " minutes ago";
        } else {
            return "just now";
        }
    } catch (Exception $e) {
        return "Unknown";
    }
}

function getFullName($firstName, $middleName = '', $lastName = '') {
    $fullName = $firstName;
    if (!empty($middleName)) {
        $fullName .= " " . $middleName;
    }
    if (!empty($lastName)) {
        $fullName .= " " . $lastName;
    }
    return $fullName;
}

function getInitials($firstName, $middleName = '', $lastName = '') {
    $initials = '';
    
    if (!empty($firstName)) {
        $initials .= strtoupper(substr($firstName, 0, 1));
    }
    
    if (!empty($middleName)) {
        $initials .= strtoupper(substr($middleName, 0, 1));
    } elseif (!empty($lastName)) {
        $initials .= strtoupper(substr($lastName, 0, 1));
    }
    
    return $initials;
}

function normalizePicturePath($picturePath) {
    if (empty($picturePath) || $picturePath === 'NULL' || strtolower($picturePath) === 'null') {
        return null;
    }
    
    if (strpos($picturePath, '../../') === 0) {
        $picturePath = str_replace('../../', '/ALERTPOINT/', $picturePath);
    }
    elseif (strpos($picturePath, '/ALERTPOINT/') === 0) {
        // Keep as is
    }
    elseif (strpos($picturePath, '/') !== 0 && strpos($picturePath, 'http') !== 0) {
        $picturePath = '/ALERTPOINT/' . $picturePath;
    }
    
    return $picturePath;
}

function getBrowserName($userAgent) {
    if (strpos($userAgent, 'Chrome') !== false) {
        return 'Chrome';
    } elseif (strpos($userAgent, 'Firefox') !== false) {
        return 'Firefox';
    } elseif (strpos($userAgent, 'Safari') !== false) {
        return 'Safari';
    } elseif (strpos($userAgent, 'Edge') !== false) {
        return 'Edge';
    } elseif (strpos($userAgent, 'Opera') !== false) {
        return 'Opera';
    } else {
        return 'Unknown';
    }
}

function getOSName($userAgent) {
    if (strpos($userAgent, 'Windows') !== false) {
        return 'Windows';
    } elseif (strpos($userAgent, 'Mac') !== false) {
        return 'macOS';
    } elseif (strpos($userAgent, 'Linux') !== false) {
        return 'Linux';
    } elseif (strpos($userAgent, 'Android') !== false) {
        return 'Android';
    } elseif (strpos($userAgent, 'iOS') !== false) {
        return 'iOS';
    } else {
        return 'Unknown';
    }
}

function getCurrentServerDate() {
    return date('Y-m-d');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlertPoint System Action Logs</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/ALERTPOINT/css/Users.css">
    <link rel="stylesheet" href="/ALERTPOINT/css/system_action.css">

    <link rel="stylesheet" href="/ALERTPOINT/css/footer.css">
    <link rel="stylesheet" href="/ALERTPOINT/css/nav-bar-2.css">
    <link rel="stylesheet" href="/ALERTPOINT/css/logout.css">

</head>

<body class="min-h-screen bg-gray-100">

   <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <!-- Replaced the icon with an image -->
                    <img src="/ALERTPOINT/ALERTPOINT_LOGO.png" alt="AlertPoint Logo" class="h-11 w-auto mr-3" />
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">AlertPoint</h1>
                        <p class="text-sm text-gray-600">Barangay 170, Caloocan City</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p id="current-time" class="text-sm font-medium text-gray-900"></p>
                        <p class="text-xs text-gray-500">Philippine Standard Time</p>
                    </div>
                    <div class="relative">
                        <i onclick="toggleSettingsDropdown()" class="fas fa-cog text-gray-400 cursor-pointer hover:text-gray-600"></i>
                        <div id="settingsDropdown" class="absolute right-0 mt-2 w-52 bg-white border border-gray-200 rounded-lg shadow-lg z-50 transform scale-95 opacity-0 transition-all duration-200 ease-in-out pointer-events-none">
                            <a href="#" onclick="openProfileModal(); return false;" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2 text-gray-500"></i> Profile
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="#" onclick="confirmLogout(); return false;" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>


    <!-- Navigation -->
    <nav class="bg-white border-b">
        <div class="max-w-8xl mx-auto px-4">
            <div class="flex justify-center space-x-2 md:space-x-6">
                <!-- Dashboard -->
                <a href="/ALERTPOINT/html/dashboard.php"
                    class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500 duration-200">
                    <i class="fas fa-chart-bar text-lg"></i>
                    <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Dashboard</span>
                </a>

                <!-- Alerts -->
                <a href="#"
                    class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500 duration-200">
                    <i class="fas fa-bell text-lg"></i>
                    <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Alerts</span>
                </a>

                <!-- Reports -->
                <a href="#"
                    class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500 duration-200">
                    <i class="fas fa-chart-line text-lg"></i>
                    <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Reports</span>
                </a>

                <!-- Users -->
                <a href="/ALERTPOINT/html/Users.php"
                    class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500 duration-200">
                    <i class="fas fa-users text-lg"></i>
                    <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Users</span>
                </a>

                <!-- Evacuation Plan -->
                <a href="/ALERTPOINT/html/EvacuationPlan.php"
                    class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500 duration-200">
                    <i class="fas fa-route text-lg"></i>
                    <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Evacuation Plan</span>
                </a>

                <!-- Activity Logs -->
                <a href="/ALERTPOINT/html/Activity_Logs.php"
                    class="nav-tab active flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500 duration-200">
                    <i class="fas fa-history text-lg"></i>
                    <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Activity Logs</span>
                </a>

            

                <!-- Settings -->
                <a href="#"
                    class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500 duration-200">
                    <i class="fas fa-cog text-lg"></i>
                    <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Settings</span>
                </a>
            </div>
        </div>
    </nav>



    <!-- Main Content -->
    <main class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-1">System Action Logs</h1>
                    <p class="text-gray-600 text-base">Record and review administrative actions and system operations.</p>
                </div>
                <div class="flex flex-col sm:flex-row sm:space-x-3 space-y-3 sm:space-y-0">
                    <button onclick="openExportLogsModal()" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-md flex items-center justify-center space-x-2 text-sm shadow-md transition-all">
                        <i class="fas fa-download"></i>
                        <span>Export Logs</span>
                    </button>
                    <button onclick="openClearLogsModal()" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-md flex items-center justify-center space-x-2 text-sm shadow-md transition-all">
                        <i class="fas fa-trash-alt"></i>
                        <span>Clear Old Logs</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="stat-card hover-card rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total System Actions</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $totalSystemLogsAll; ?></p>
                        <?php if ($filterTimeframe !== 'all'): ?>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php 
                                switch($filterTimeframe) {
                                    case 'today': echo 'For Today'; break;
                                    case 'week': echo 'This Week'; break;
                                    case 'month': echo 'This Month'; break;
                                    case 'year': echo 'This Year'; break;
                                    case 'custom': 
                                        if (!empty($startDate) && !empty($endDate)) {
                                            echo date('M j', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate));
                                        } elseif (!empty($startDate)) {
                                            echo 'From ' . date('M j, Y', strtotime($startDate));
                                        } elseif (!empty($endDate)) {
                                            echo 'Until ' . date('M j, Y', strtotime($endDate));
                                        }
                                        break;
                                }
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="p-3">
                        <i class="fas fa-cogs text-blue-600 text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card hover-card rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Data Exports</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $totalDataExports; ?></p>
                        <?php if ($filterTimeframe !== 'all'): ?>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php 
                                switch($filterTimeframe) {
                                    case 'today': echo 'For Today'; break;
                                    case 'week': echo 'This Week'; break;
                                    case 'month': echo 'This Month'; break;
                                    case 'year': echo 'This Year'; break;
                                    case 'custom': 
                                        if (!empty($startDate) && !empty($endDate)) {
                                            echo date('M j', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate));
                                        } elseif (!empty($startDate)) {
                                            echo 'From ' . date('M j, Y', strtotime($startDate));
                                        } elseif (!empty($endDate)) {
                                            echo 'Until ' . date('M j, Y', strtotime($endDate));
                                        }
                                        break;
                                }
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="p-3">
                        <i class="fas fa-download text-green-600 text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card hover-card rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">System Maintenance</p>
                        <p class="text-2xl font-bold text-orange-600"><?php echo $totalSystemMaintenance; ?></p>
                        <?php if ($filterTimeframe !== 'all'): ?>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php 
                                switch($filterTimeframe) {
                                    case 'today': echo 'For Today'; break;
                                    case 'week': echo 'This Week'; break;
                                    case 'month': echo 'This Month'; break;
                                    case 'year': echo 'This Year'; break;
                                    case 'custom': 
                                        if (!empty($startDate) && !empty($endDate)) {
                                            echo date('M j', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate));
                                        } elseif (!empty($startDate)) {
                                            echo 'From ' . date('M j, Y', strtotime($startDate));
                                        } elseif (!empty($endDate)) {
                                            echo 'Until ' . date('M j, Y', strtotime($endDate));
                                        }
                                        break;
                                }
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="p-3">
                        <i class="fas fa-tools text-orange-600 text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card hover-card rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">User Management</p>
                        <p class="text-2xl font-bold text-purple-600"><?php echo $totalUserManagement; ?></p>
                        <?php if ($filterTimeframe !== 'all'): ?>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php 
                                switch($filterTimeframe) {
                                    case 'today': echo 'For Today'; break;
                                    case 'week': echo 'This Week'; break;
                                    case 'month': echo 'This Month'; break;
                                    case 'year': echo 'This Year'; break;
                                    case 'custom': 
                                        if (!empty($startDate) && !empty($endDate)) {
                                            echo date('M j', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate));
                                        } elseif (!empty($startDate)) {
                                            echo 'From ' . date('M j, Y', strtotime($startDate));
                                        } elseif (!empty($endDate)) {
                                            echo 'Until ' . date('M j, Y', strtotime($endDate));
                                        }
                                        break;
                                }
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="p-3">
                        <i class="fas fa-users-cog text-purple-600 text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($clearMessage)): ?>
        <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                <span class="text-green-800"><?php echo htmlspecialchars($clearMessage); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($clearError)): ?>
        <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                <span class="text-red-800"><?php echo htmlspecialchars($clearError); ?></span>
            </div>
        </div>
        <?php endif; ?>


 <!-- Log Categories (Navigation) -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Log Categories</h2>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    
                    <!-- Admin Login Logs -->
                    <a href="/ALERTPOINT/html/Activity_Logs.php" 
                    class="category-card bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-4 cursor-pointer hover:shadow-md hover:scale-[1.02] transition-all duration-300 transform">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-blue-800">Admin Login Logs</h3>
                                <p class="text-sm text-blue-600">Track admin authentication</p>
                                <p class="text-sm font-medium text-blue-700 mt-2">View Details</p>
                            </div>
                            <div class="text-blue-600">
                                <i class="fas fa-sign-in-alt text-2xl"></i>
                            </div>
                        </div>
                    </a>

                    <!-- System Actions (Active - Now Indigo) -->
                    <a href="/ALERTPOINT/html/Activity_Logs_2.php" 
                    class="category-card bg-gradient-to-r from-orange-50 to-orange-100 border border-orange-200 rounded-lg p-4 cursor-pointer hover:shadow-md hover:scale-[1.02] transition-all duration-300 transform">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-orange-800">System Actions</h3>
                                <p class="text-sm text-orange-600">CRUD operations & alerts</p>
                                <span class="inline-block mt-2 text-xs bg-orange-200 text-orange-800 font-semibold px-2 py-1 rounded-full">Active</span>
                            </div>
                            <div class="text-orange-600">
                                <i class="fas fa-cogs text-2xl"></i>
                            </div>
                        </div>
                    </a>

                    <!-- User Activities (Emerald) -->
                    <a href="#" 
                    class="category-card bg-gradient-to-r from-emerald-50 to-emerald-100 border border-emerald-200 rounded-lg p-4 cursor-pointer hover:shadow-md hover:scale-[1.02] transition-all duration-300 transform">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-emerald-800">User Activities</h3>
                                <p class="text-sm text-emerald-600">Resident app interactions</p>
                                <p class="text-sm font-medium text-emerald-700 mt-2">View Details</p>
                            </div>
                            <div class="text-emerald-600">
                                <i class="fas fa-users text-2xl"></i>
                            </div>
                        </div>
                    </a>

                </div>
            </div>
        </div>







        <!-- System Action Logs Section -->
        <div class="bg-white rounded-xl shadow-lg mb-8">
            <div class="p-6 border-b border-gray-200">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-cogs text-orange-600 mr-3"></i>
                            System Action Logs
                        </h2>
                        <?php if ($pdo): ?>
                            <p class="text-xs text-green-600 mt-1">✓ Database Connected</p>
                        <?php else: ?>
                            <p class="text-xs text-red-600 mt-1">✗ Database Connection Failed</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Filters -->
                    <div class="flex flex-wrap gap-3">
                        <form method="GET" class="flex flex-wrap gap-3" id="filterForm">
                            <!-- Action Type Filter -->
                            <select name="type" class="px-3 py-2 border border-gray-300 rounded-md text-sm" onchange="document.getElementById('filterForm').submit()">
                                <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Types</option>
                                <option value="data_export" <?php echo $filterType === 'data_export' ? 'selected' : ''; ?>>Data Export</option>
                                <option value="system_maintenance" <?php echo $filterType === 'system_maintenance' ? 'selected' : ''; ?>>System Maintenance</option>
                                <option value="user_management" <?php echo $filterType === 'user_management' ? 'selected' : ''; ?>>User Management</option>
                            </select>

                            <!-- Timeframe Filter -->
                            <select name="timeframe" id="timeframeSelect" class="px-3 py-2 border border-gray-300 rounded-md text-sm" onchange="handleTimeframeChange()">
                                <option value="all" <?php echo $filterTimeframe === 'all' ? 'selected' : ''; ?>>All Time</option>
                                <option value="today" <?php echo $filterTimeframe === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $filterTimeframe === 'week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="month" <?php echo $filterTimeframe === 'month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="year" <?php echo $filterTimeframe === 'year' ? 'selected' : ''; ?>>This Year</option>
                                <!-- <option value="custom" <?php echo $filterTimeframe === 'custom' ? 'selected' : ''; ?>>Custom Range</option> -->
                            </select>

                            <!-- Date Range Picker (hidden by default) -->
                            <div id="dateRangeContainer" class="flex items-center gap-2" style="<?php echo $filterTimeframe !== 'custom' ? 'display: none;' : ''; ?>">
                                <input type="date" name="start_date" id="startDate" value="<?php echo htmlspecialchars($startDate); ?>" 
                                    class="px-3 py-2 border border-gray-300 rounded-md text-sm" max="<?php echo getCurrentServerDate(); ?>"
                                    onchange="validateDateRange(); document.getElementById('filterForm').submit();">
                                <span class="text-gray-500 text-sm">to</span>
                                <input type="date" name="end_date" id="endDate" value="<?php echo htmlspecialchars($endDate); ?>" 
                                    class="px-3 py-2 border border-gray-300 rounded-md text-sm" max="<?php echo getCurrentServerDate(); ?>"
                                    onchange="validateDateRange(); document.getElementById('filterForm').submit();">
                            </div>

                            <!-- Quick Date Buttons -->
                            <div id="quickDateButtons" class="flex gap-2" style="<?php echo $filterTimeframe !== 'custom' ? 'display: none;' : ''; ?>">
                                <button type="button" onclick="setQuickDate('thisMonth')" class="px-3 py-2 bg-blue-100 text-blue-700 rounded-md text-sm hover:bg-blue-200">
                                    This Month
                                </button>
                                <button type="button" onclick="setQuickDate('lastMonth')" class="px-3 py-2 bg-blue-100 text-blue-700 rounded-md text-sm hover:bg-blue-200">
                                    Last Month
                                </button>
                                <button type="button" onclick="setQuickDate('last7days')" class="px-3 py-2 bg-blue-100 text-blue-700 rounded-md text-sm hover:bg-blue-200">
                                    Last 7 Days
                                </button>
                            </div>

                            <!-- Search Input -->
                            <input type="text" id="searchInput" value="<?php echo htmlspecialchars($searchTerm); ?>" 
                                placeholder="Search admin name, action, or details..." 
                                class="px-3 py-2 border border-gray-300 rounded-md text-sm w-64"
                                oninput="performRealTimeSearch()">

                            <!-- Hidden inputs to preserve filter state -->
                            <input type="hidden" name="page" value="1">
                            <?php if ($filterTimeframe === 'custom'): ?>
                                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                                <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <?php if (!empty($systemLogs)): ?>
                    <!-- Logs Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Done</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device Info</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($systemLogs as $log): 
                                    $fullName = getFullName(
                                        $log['first_name'] ?? '', 
                                        $log['middle_name'] ?? '', 
                                        $log['last_name'] ?? ''
                                    );
                                    $initials = getInitials(
                                        $log['first_name'] ?? '', 
                                        $log['middle_name'] ?? '', 
                                        $log['last_name'] ?? ''
                                    );
                                    $picturePath = normalizePicturePath($log['picture'] ?? null);
                                    $hasPicture = !empty($picturePath);
                                    
                                    $actionType = $log['action_type'] ?? 'unknown';
                                    $actionTypeDisplay = ucfirst(str_replace('_', ' ', $actionType));
                                    
                                    // Set action type colors
                                    $actionTypeClass = 'bg-gray-100 text-gray-800';
                                    $actionIcon = 'fa-cog';
                                    
                                    switch($actionType) {
                                        case 'data_export':
                                            $actionTypeClass = 'bg-green-100 text-green-800';
                                            $actionIcon = 'fa-download';
                                            break;
                                        case 'system_maintenance':
                                            $actionTypeClass = 'bg-orange-100 text-orange-800';
                                            $actionIcon = 'fa-tools';
                                            break;
                                        case 'user_management':
                                            $actionTypeClass = 'bg-purple-100 text-purple-800';
                                            $actionIcon = 'fa-users-cog';
                                            break;
                                    }
                                    
                                    $avatarColors = ['bg-violet-500', 'bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500'];
                                    $avatarColor = $avatarColors[array_rand($avatarColors)];
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="relative">
                                                <?php if ($hasPicture): ?>
                                                    <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-gray-200">
                                                        <img src="<?php echo htmlspecialchars($picturePath); ?>" 
                                                            alt="<?php echo htmlspecialchars($fullName); ?>" 
                                                            class="w-full h-full object-cover"
                                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    </div>
                                                    <div class="w-10 h-10 <?php echo $avatarColor; ?> text-white rounded-full flex items-center justify-center font-semibold text-sm border-2 border-gray-200 absolute top-0 left-0" style="display: none;">
                                                        <?php echo htmlspecialchars($initials); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="w-10 h-10 <?php echo $avatarColor; ?> text-white rounded-full flex items-center justify-center font-semibold text-sm border-2 border-gray-200">
                                                        <?php echo htmlspecialchars($initials); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($fullName ?: 'Unknown Admin'); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($log['username'] ?? 'N/A'); ?> • 
                                                    <?php echo htmlspecialchars($log['barangay_position'] ?? 'Admin'); ?>
                                                </div>
                                                <div class="text-xs text-gray-400">
                                                    ID: <?php echo htmlspecialchars($log['admin_id'] ?? 'N/A'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo formatDateTime($log['action_date_time']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo getTimeAgo($log['action_date_time']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $actionTypeClass; ?>">
                                            <i class="fas <?php echo $actionIcon; ?> mr-1"></i>
                                            <?php echo htmlspecialchars($log['action_summary'] ?? $actionTypeDisplay); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-2 align-top">
                                        <div class="text-sm text-gray-900 max-w-xs whitespace-normal break-words leading-snug">
                                            <div title="<?php echo htmlspecialchars($log['action_details'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($log['action_details'] ?? ''); ?>
                                            </div>
                                            <?php if (!empty($log['target_name'])): ?>
                                                <div class="text-xs text-gray-500 mt-0.5 leading-tight">
                                                    Target: <?php echo htmlspecialchars($log['target_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <i class="fas fa-desktop mr-2 text-gray-400"></i>
                                                <?php echo getBrowserName($log['user_agent'] ?? ''); ?> on <?php echo getOSName($log['user_agent'] ?? ''); ?>
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1 max-w-xs truncate" title="<?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>">
                                            <?php echo htmlspecialchars(substr($log['user_agent'] ?? '', 0, 50)) . (strlen($log['user_agent'] ?? '') > 50 ? '...' : ''); ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="flex items-center justify-between mt-6 pt-6 border-t border-gray-200">
                        <div class="text-sm text-gray-500">
                            Showing <?php echo (($currentPage - 1) * $logsPerPage) + 1; ?>-<?php echo min($currentPage * $logsPerPage, $totalSystemLogs); ?> of <?php echo $totalSystemLogs; ?> logs
                        </div>
                        <div class="flex space-x-2">
                            <?php
                            // Build query string for pagination
                            $queryParams = $_GET;
                            
                            // Previous button
                            if ($currentPage > 1):
                                $queryParams['page'] = $currentPage - 1;
                                $prevUrl = '?' . http_build_query($queryParams);
                            ?>
                                <a href="<?php echo $prevUrl; ?>" class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors">
                                    <i class="fas fa-chevron-left mr-1"></i>Previous
                                </a>
                            <?php endif; ?>

                            <?php
                            
                            // Page numbers
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                                $queryParams['page'] = $i;
                                $pageUrl = '?' . http_build_query($queryParams);
                                $isActive = ($i == $currentPage);
                            ?>
                                <a href="<?php echo $pageUrl; ?>" class="px-3 py-2 text-sm <?php echo $isActive ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'; ?> rounded-md transition-colors">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php
                            // Next button
                            if ($currentPage < $totalPages):
                                $queryParams['page'] = $currentPage + 1;
                                $nextUrl = '?' . http_build_query($queryParams);
                            ?>
                                <a href="<?php echo $nextUrl; ?>" class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors">
                                    Next<i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Empty State -->
                    <div class="text-center py-12">
                        <div class="mx-auto h-24 w-24 text-gray-300 mb-4">
                            <i class="fas fa-cogs text-6xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No System Actions Found</h3>
                        <p class="text-gray-600 mb-4">
                            <?php if (!empty($searchTerm)): ?>
                                No system actions match your search criteria.
                            <?php elseif ($filterTimeframe !== 'all' || $filterType !== 'all'): ?>
                                No system actions found for the selected filters.
                            <?php else: ?>
                                There are no system actions logged yet.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($searchTerm) || $filterTimeframe !== 'all' || $filterType !== 'all'): ?>
                            <a href="?<?php echo http_build_query(array_filter(['timeframe' => 'all', 'type' => 'all'])); ?>" 
                               class="text-blue-600 hover:text-blue-800 font-medium">
                                <i class="fas fa-times mr-1"></i>Clear all filters
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

   <!-- Clear Logs Modal -->
<div id="clearLogsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-red-50 rounded-t-xl">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-trash-alt text-red-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">Clear Old Logs</h3>
            </div>
            <button onclick="closeClearLogsModal()" class="text-gray-400 p-2 rounded-full">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2">Permanent Action Warning</h4>
                <p class="text-gray-600 text-sm mb-4">
                    This will permanently delete all login logs older than <strong>90 days</strong>. This action cannot be undone.
                </p>
                <div class="bg-yellow-50 border border-yellow-200 p-3 rounded-lg mb-4">
                    <div class="flex items-center justify-center space-x-2 text-sm text-yellow-800">
                        <i class="fas fa-info-circle text-yellow-600"></i>
                        <span>Only logs from before <?php echo date('Y-m-d', strtotime('-2 years')); ?> will be deleted</span>
                    </div>
                </div>
            </div>
            
            <form method="POST" class="flex justify-center space-x-4">
                <button type="button" onclick="closeClearLogsModal()"
                        class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                    <i class="fas fa-times"></i>
                    <span>Cancel</span>
                </button>
                <button type="submit" name="clear_old_logs" value="confirm"
                        class="px-6 py-3 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                    <i class="fas fa-trash-alt"></i>
                    <span>Clear Old Logs</span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Export Logs Modal -->
<div id="exportLogsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-green-50 rounded-t-xl">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-download text-green-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">Export Logs</h3>
            </div>
            <button onclick="closeExportLogsModal()" class="text-gray-400 p-2 rounded-full">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-file-csv text-green-500 text-2xl"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2">Download Activity Logs</h4>
                <p class="text-gray-600 text-sm mb-4">
                    This will generate and download a CSV file containing all current activity logs with your applied filters.
                </p>
                <div class="bg-blue-50 border border-blue-200 p-3 rounded-lg mb-4">
                    <div class="flex items-center justify-center space-x-2 text-sm text-blue-800">
                        <i class="fas fa-info-circle text-blue-600"></i>
                        <span>Export includes: <?php echo $totalSystemLogs; ?> log entries</span>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-center space-x-4">
                <button type="button" onclick="closeExportLogsModal()"
                        class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                    <i class="fas fa-times"></i>
                    <span>Cancel</span>
                </button>
                <button type="button" onclick="confirmExportLogs()"
                        class="px-6 py-3 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                    <i class="fas fa-download"></i>
                    <span>Download CSV</span>
                </button>
            </div>
        </div>
    </div>
</div>


    <!-- Logout Confirmation Modal -->
    <div id="logoutConfirmationModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 transform">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Confirm Logout</h3>
            </div>
            <div class="px-6 py-4">
                <p class="text-gray-600">Are you sure you want to logout from your account?</p>
            </div>
            <div class="px-6 py-4 bg-gray-50 rounded-b-xl flex justify-end space-x-3">
                <button id="cancelLogoutBtn" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                    Cancel
                </button>
                <button id="confirmLogoutBtn" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </button>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profileModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 transform">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-user text-blue-600 mr-2"></i>
                    Admin Profile
                </h3>
            </div>
            <div class="px-6 py-4">
                <?php if ($currentAdmin): ?>
                    <div class="flex items-center mb-4">
                        <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold text-lg mr-4">
                            <?php echo getInitials($currentAdmin['first_name'] ?? '', $currentAdmin['middle_name'] ?? '', $currentAdmin['last_name'] ?? ''); ?>
                        </div>
                        <div>
                            <div class="text-lg font-medium text-gray-900">
                                <?php echo getFullName($currentAdmin['first_name'] ?? '', $currentAdmin['middle_name'] ?? '', $currentAdmin['last_name'] ?? ''); ?>
                            </div>
                            <div class="text-gray-600">
                                <?php echo htmlspecialchars($currentAdmin['username'] ?? 'N/A'); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($currentAdmin['barangay_position'] ?? 'Admin'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div><strong>Admin ID:</strong> <?php echo htmlspecialchars($currentAdmin['admin_id'] ?? 'N/A'); ?></div>
                        <?php if (!empty($currentAdmin['email'])): ?>
                        <div><strong>Email:</strong> <?php echo htmlspecialchars($currentAdmin['email']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($currentAdmin['last_login'])): ?>
                        <div><strong>Last Login:</strong> <?php echo formatDateTime($currentAdmin['last_login']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">Profile information not available.</p>
                <?php endif; ?>
            </div>
            <div class="px-6 py-4 bg-gray-50 rounded-b-xl flex justify-end">
                <button onclick="closeProfileModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

      <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div>
                        <div class="flex items-center mb-4">
                            <i class="fas fa-map-marker-alt text-2xl text-blue-400 mr-2"></i>
                            <h3 class="text-lg font-bold">AlertPoint</h3>
                        </div>
                        <p class="text-gray-300 text-sm mb-4">
                           A Disaster Risk Reduction Management System for Barangay 170, Caloocan City
                        </p>
                        <div class="flex space-x-3">
                            <i class="fab fa-facebook text-blue-400 hover:text-blue-300 cursor-pointer"></i>
                            <i class="fab fa-twitter text-blue-400 hover:text-blue-300 cursor-pointer"></i>
                            <i class="fas fa-envelope text-blue-400 hover:text-blue-300 cursor-pointer"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-md font-semibold mb-4">Quick Links</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/ALERTPOINT/html/dashboard.php" class="text-gray-300 hover:text-white transition-colors">Dashboard</a></li>
                            <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Active Alerts</a></li>
                            <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Reports</a></li>
                            <li><a href="/ALERTPOINT/html/Users.php" class="text-gray-300 hover:text-white transition-colors">User Management</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-md font-semibold mb-4">Services</h4>
                        <ul class="space-y-2 text-sm text-gray-300">
                            <li>Flood Monitoring</li>
                            <li>Temperature Tracking</li>
                            <li>Humidity Analysis</li>
                            <li>Emergency Alerts</li>
                            <li>AI Insights</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-md font-semibold mb-4">Contact</h4>
                        <div class="space-y-2 text-sm text-gray-300">
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt mr-2 text-blue-400"></i>
                                <span>Barangay 170, Caloocan City</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-phone mr-2 text-blue-400"></i>
                                <span>+63 (2) 8123-4567</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-envelope mr-2 text-blue-400"></i>
                                <span>admin@alertpoint.gov.ph</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-clock mr-2 text-blue-400"></i>
                                <span>24/7 Monitoring</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 py-4">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-sm text-gray-400 mb-2 md:mb-0">
                        © <span id="current-year">2025</span> AlertPoint Environmental Monitoring System. All rights reserved.
                    </div>
                    <div class="flex space-x-6 text-sm">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Support</a>
                    </div>
                </div>
                <div class="mt-3 text-center">
                    <div class="flex justify-center items-center space-x-4 text-xs text-gray-400">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></div>
                            <span>System Online</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-server mr-1 text-green-400"></i>
                            <span>Server Status: Active</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-wifi mr-1 text-green-400"></i>
                            <span>Connection: Stable</span>
                        </div>
                        <div class="flex items-center">
                            <span>Last Update: </span>
                            <span id="last-update-time" class="ml-1"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Files -->
    <script src="/ALERTPOINT/javascript/footer.js"></script>
    <script src="/ALERTPOINT/javascript/nav-bar.js"></script>
    <script src="/ALERTPOINT/javascript/profile.js"></script>
    <script src="/ALERTPOINT/javascript/LOGS/system_actions.js"></script>


    <script>
                   // LOGOUT FUNCTION
function toggleSettingsDropdown() {
    const dropdown = document.getElementById('settingsDropdown');
    const isVisible = !dropdown.classList.contains('opacity-0');
    
    if (isVisible) {
        dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
    } else {
        dropdown.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
    }
}

function confirmLogout() {
    // Close the dropdown first
    const dropdown = document.getElementById('settingsDropdown');
    dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
    
    // Show logout modal
    const modal = document.getElementById('logoutConfirmationModal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    return false;
}

function closeLogoutModal() {
    const modal = document.getElementById('logoutConfirmationModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function performLogout() {
    // Show loading state on button
    const confirmBtn = document.getElementById('confirmLogoutBtn');
    const originalContent = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Logging out...';
    confirmBtn.disabled = true;
    
    // Add smooth transition before redirect
    setTimeout(() => {
        window.location.href = '/ALERTPOINT/javascript/LOGIN/logout.php';
    }, 1000);
}

// Event listeners for modal buttons
document.addEventListener('DOMContentLoaded', function() {
    // Cancel logout button
    document.getElementById('cancelLogoutBtn').addEventListener('click', closeLogoutModal);
    
    // Confirm logout button
    document.getElementById('confirmLogoutBtn').addEventListener('click', performLogout);
    
    // Close modal when clicking outside
    document.getElementById('logoutConfirmationModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeLogoutModal();
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLogoutModal();
        }
    });
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('settingsDropdown');
    const cogIcon = e.target.closest('.fa-cog');
    
    if (!cogIcon && !dropdown.contains(e.target)) {
        dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
    }
});

    </script>
</body>
</html>