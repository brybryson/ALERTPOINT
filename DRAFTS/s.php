<?php
// ADD THIS AT THE VERY TOP OF Activity_Logs.php (before any other PHP code)
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


require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;


// Initialize variables with default values
$adminLogs = [];
$totalAdminLogs = 0;
$successfulLogins = 0;
$failedLogins = 0;
$pdo = null;
$currentAdmin = null; // Initialize currentAdmin

// Initialize variables for system action logs
$systemLogs = [];
$totalSystemLogs = 0;

// Pagination settings
$logsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $logsPerPage;

// Filter settings - Enhanced with date range support
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
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
        
        // Status filter
        if ($filterStatus !== 'all') {
            $whereConditions[] = "al.login_status = :status";
            $params[':status'] = $filterStatus;
        }
        
        // Timeframe filter
        if ($filterTimeframe !== 'all') {
            switch ($filterTimeframe) {
                case 'today':
                    $whereConditions[] = "DATE(al.login_time) = CURDATE()";
                    break;
                case 'week':
                    $whereConditions[] = "al.login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $whereConditions[] = "al.login_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'custom':
                    if (!empty($startDate) && !empty($endDate)) {
                        $whereConditions[] = "DATE(al.login_time) BETWEEN :start_date AND :end_date";
                        $params[':start_date'] = $startDate;
                        $params[':end_date'] = $endDate;
                    } elseif (!empty($startDate)) {
                        $whereConditions[] = "DATE(al.login_time) >= :start_date";
                        $params[':start_date'] = $startDate;
                    } elseif (!empty($endDate)) {
                        $whereConditions[] = "DATE(al.login_time) <= :end_date";
                        $params[':end_date'] = $endDate;
                    }
                    break;
            }
        }
        
        // Search filter
        if (!empty($searchTerm)) {
            $whereConditions[] = "(CONCAT(a.first_name, ' ', COALESCE(a.middle_name, ''), ' ', a.last_name) LIKE :search OR a.username LIKE :search OR al.admin_id LIKE :search)";
            $params[':search'] = '%' . $searchTerm . '%';
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Count total logs for pagination
        $countQuery = "SELECT COUNT(*) as total FROM admin_logs al 
                       LEFT JOIN admins_tbl a ON al.admin_id = a.admin_id 
                       $whereClause";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalAdminLogs = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Fetch admin logs with pagination
        $query = "SELECT al.*, 
                         a.first_name, a.middle_name, a.last_name, a.username, a.picture,
                         a.barangay_position
                  FROM admin_logs al 
                  LEFT JOIN admins_tbl a ON al.admin_id = a.admin_id 
                  $whereClause
                  ORDER BY al.login_time DESC 
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
        $adminLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get statistics
        $statsWhereClause = $whereClause; // Use same WHERE clause as main query
        $statsQuery = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN login_status = 'success' THEN 1 ELSE 0 END) as successful,
                        SUM(CASE WHEN login_status = 'failed' THEN 1 ELSE 0 END) as failed
                    FROM admin_logs al 
                    LEFT JOIN admins_tbl a ON al.admin_id = a.admin_id 
                    $statsWhereClause";
        $statsStmt = $pdo->prepare($statsQuery);
        $statsStmt->execute($params); // Use same params as main query
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

        $totalAdminLogsAll = $stats['total'];
        $successfulLogins = $stats['successful'];
        $failedLogins = $stats['failed'];

        // FIXED: Fetch system action logs with proper WHERE clause for system_action_logs table
        try {
            // Build WHERE clause for system action logs (different field names)
            $systemWhereConditions = [];
            $systemParams = [];
            
            // Timeframe filter for system logs
            if ($filterTimeframe !== 'all') {
                switch ($filterTimeframe) {
                    case 'today':
                        $systemWhereConditions[] = "DATE(sal.action_date_time) = CURDATE()";
                        break;
                    case 'week':
                        $systemWhereConditions[] = "sal.action_date_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                        break;
                    case 'month':
                        $systemWhereConditions[] = "sal.action_date_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                        break;
                    case 'custom':
                        if (!empty($startDate) && !empty($endDate)) {
                            $systemWhereConditions[] = "DATE(sal.action_date_time) BETWEEN :start_date AND :end_date";
                            $systemParams[':start_date'] = $startDate;
                            $systemParams[':end_date'] = $endDate;
                        } elseif (!empty($startDate)) {
                            $systemWhereConditions[] = "DATE(sal.action_date_time) >= :start_date";
                            $systemParams[':start_date'] = $startDate;
                        } elseif (!empty($endDate)) {
                            $systemWhereConditions[] = "DATE(sal.action_date_time) <= :end_date";
                            $systemParams[':end_date'] = $endDate;
                        }
                        break;
                }
            }
            
            // Search filter for system logs
            if (!empty($searchTerm)) {
                $systemWhereConditions[] = "(CONCAT(a.first_name, ' ', COALESCE(a.middle_name, ''), ' ', a.last_name) LIKE :search OR a.username LIKE :search OR sal.admin_id LIKE :search)";
                $systemParams[':search'] = '%' . $searchTerm . '%';
            }
            
            $systemWhereClause = !empty($systemWhereConditions) ? 'WHERE ' . implode(' AND ', $systemWhereConditions) : '';
            
            // Fetch system action logs with pagination
            $systemQuery = "SELECT sal.*, 
                                   a.first_name, a.middle_name, a.last_name, a.username, a.picture,
                                   a.barangay_position
                            FROM system_action_logs sal 
                            LEFT JOIN admins_tbl a ON sal.admin_id = a.admin_id 
                            $systemWhereClause
                            ORDER BY sal.action_date_time DESC 
                            LIMIT :limit OFFSET :offset";
            
            $systemStmt = $pdo->prepare($systemQuery);
            $systemStmt->bindValue(':limit', $logsPerPage, PDO::PARAM_INT);
            $systemStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            foreach ($systemParams as $key => $value) {
                $systemStmt->bindValue($key, $value);
            }
            
            $systemStmt->execute();
            $systemLogs = $systemStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Count system logs
            $systemCountQuery = "SELECT COUNT(*) as total FROM system_action_logs sal 
                                LEFT JOIN admins_tbl a ON sal.admin_id = a.admin_id 
                                $systemWhereClause";
            $systemCountStmt = $pdo->prepare($systemCountQuery);
            foreach ($systemParams as $key => $value) {
                $systemCountStmt->bindValue($key, $value);
            }
            $systemCountStmt->execute();
            $totalSystemLogs = $systemCountStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
        } catch (Exception $e) {
            // If system logs fail, just use empty arrays
            $systemLogs = [];
            $totalSystemLogs = 0;
            error_log("System logs error: " . $e->getMessage());
        }
    }
} catch (Exception $e) {
    // Log error and use fallback values
    error_log("Database error in Activity_Logs.php: " . $e->getMessage());
    $adminLogs = [];
    $totalAdminLogs = 0;
    $successfulLogins = 0;
    $failedLogins = 0;
    $pdo = null;
}


if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Apply filters to Excel export query
    try {
        if ($pdo) {
            // Use the same query logic for export but without LIMIT
            $exportQuery = "SELECT al.*, 
                                   a.first_name, a.middle_name, a.last_name, a.username, a.picture,
                                   a.barangay_position
                            FROM admin_logs al 
                            LEFT JOIN admins_tbl a ON al.admin_id = a.admin_id 
                            $whereClause
                            ORDER BY al.login_time DESC";
            
            $exportStmt = $pdo->prepare($exportQuery);
            foreach ($params as $key => $value) {
                $exportStmt->bindValue($key, $value);
            }
            $exportStmt->execute();
            $adminLogsForExport = $exportStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // LOG THE EXPORT ACTION
            if ($currentAdmin && isset($currentAdmin['admin_id'])) {
                $exportFilters = [
                    'timeframe' => $filterTimeframe,
                    'status' => $filterStatus,
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
                        ':action_summary' => 'Exported admin login logs',
                        ':action_details' => "Exported " . count($adminLogsForExport) . " admin login log entries with filters: " . $filterDetails,
                        ':target_type' => 'data',
                        ':target_name' => 'admin_login_logs',
                        ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ]);
                } catch (Exception $e) {
                    error_log("Failed to log export action: " . $e->getMessage());
                }
            }
        } else {
            $adminLogsForExport = [];
        }
    } catch (Exception $e) {
        $adminLogsForExport = [];
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
    
    // Add status filter info if applied
    $statusFilterInfo = '';
    if ($filterStatus !== 'all') {
        $statusFilterInfo = ' (Status: ' . ucfirst($filterStatus) . ')';
    }
    
    // Add search filter info if applied
    $searchFilterInfo = '';
    if (!empty($searchTerm)) {
        $searchFilterInfo = ' (Search: "' . $searchTerm . '")';
    }
    
    $currentRow = 1;
    
    // Add header information
    $sheet->setCellValue('A' . $currentRow, 'ADMIN LOGIN LOGS STATISTICS');
    $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(16);
    $currentRow += 2;
    
    $sheet->setCellValue('A' . $currentRow, 'Exported by: ' . $currentAdminName);
    $sheet->setCellValue('B' . $currentRow, 'ID: ' . $currentAdminId);
    $currentRow += 2;
    
    $sheet->setCellValue('A' . $currentRow, 'Report Generated: ' . $reportDate);
    $currentRow++;
    
    $sheet->setCellValue('A' . $currentRow, 'Filter Period: ' . $filterDescription . $statusFilterInfo . $searchFilterInfo);
    $currentRow += 2;
    
    // Add statistics
    $sheet->setCellValue('A' . $currentRow, 'Total Login Attempts');
    $sheet->setCellValue('B' . $currentRow, $totalAdminLogsAll);
    $currentRow++;
    
    $sheet->setCellValue('A' . $currentRow, 'Successful Logins');
    $sheet->setCellValue('B' . $currentRow, $successfulLogins);
    $currentRow++;
    
    $sheet->setCellValue('A' . $currentRow, 'Failed Attempts');
    $sheet->setCellValue('B' . $currentRow, $failedLogins);
    $currentRow++;
    
    $sheet->setCellValue('A' . $currentRow, 'Success Rate');
    $sheet->setCellValue('B' . $currentRow, ($totalAdminLogsAll > 0 ? round(($successfulLogins / $totalAdminLogsAll) * 100, 1) : 0) . '%');
    $currentRow += 3;
    
    // Add table header
    $sheet->setCellValue('A' . $currentRow, 'DETAILED ADMIN LOGIN LOGS');
    $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(14);
    $currentRow++;
    
    // Define headers and their corresponding column widths
    $headers = [
        'A' => ['title' => 'Admin Name', 'width' => 35],
        'B' => ['title' => 'Username', 'width' => 15],
        'C' => ['title' => 'Position', 'width' => 30],
        'D' => ['title' => 'Admin ID', 'width' => 12],
        'E' => ['title' => 'Login Time', 'width' => 25],
        'F' => ['title' => 'Previous Session', 'width' => 25],
        'G' => ['title' => 'Status', 'width' => 12],
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
    foreach ($adminLogsForExport as $log) {
        $fullName = getFullName($log['first_name'] ?? '', $log['middle_name'] ?? '', $log['last_name'] ?? '');
        $browser = getBrowserName($log['user_agent'] ?? '');
        $os = getOSName($log['user_agent'] ?? '');
        
        $sheet->setCellValue('A' . $currentRow, $fullName ?: 'Unknown Admin');
        $sheet->setCellValue('B' . $currentRow, $log['username'] ?? 'N/A');
        $sheet->setCellValue('C' . $currentRow, $log['barangay_position'] ?? 'Admin');
        $sheet->setCellValue('D' . $currentRow, $log['admin_id'] ?? 'N/A');
        $sheet->setCellValue('E' . $currentRow, formatDateTime($log['login_time']));
        $sheet->setCellValue('F' . $currentRow, formatDateTime($log['previous_last_active']));
        $sheet->setCellValue('G' . $currentRow, ucfirst($log['login_status'] ?? ''));
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
    
    // Generate filename with filter info
    $filename = 'admin_logs_';
    
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
            // Delete logs older than 90 days
            $clearQuery = "DELETE FROM admin_logs WHERE login_time < DATE_SUB(NOW(), INTERVAL 90 DAY)";
            $clearStmt = $pdo->prepare($clearQuery);
            $clearStmt->execute();
            
            $deletedCount = $clearStmt->rowCount();
            $clearMessage = "Successfully cleared $deletedCount old log entries (older than 90 days).";
            
            // LOG THE CLEAR ACTION to system_action_logs
            if ($currentAdmin && isset($currentAdmin['admin_id']) && $deletedCount > 0) {
                try {
                    $logQuery = "INSERT INTO system_action_logs (admin_id, action_type, action_summary, action_details, target_type, target_name, ip_address, user_agent) 
                                VALUES (:admin_id, :action_type, :action_summary, :action_details, :target_type, :target_name, :ip_address, :user_agent)";
                    $logStmt = $pdo->prepare($logQuery);
                    $logStmt->execute([
                        ':admin_id' => $currentAdmin['admin_id'],
                        ':action_type' => 'system_maintenance',
                        ':action_summary' => 'Cleared old admin login logs',
                        ':action_details' => "Cleared $deletedCount admin login log entries older than 90 days",
                        ':target_type' => 'system',
                        ':target_name' => 'admin_login_logs',
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
$totalPages = ceil($totalAdminLogs / $logsPerPage);

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




