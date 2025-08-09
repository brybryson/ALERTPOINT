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


// Get current admin info
$currentAdmin = getCurrentAdmin();

// Database connection
require_once '../config/database.php';

// Initialize variables with default values
$activeAdmins = [];
$totalActiveAdmins = 0;
$totalArchivedAdmins = 0;
$pdo = null;

// ... rest of your existing Users.php code stays the same ...

// Database connection and admin fetching
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if ($pdo) {
        // Fetch all active admins from database
        $stmt = $pdo->prepare("SELECT * FROM admins_tbl WHERE account_status = 'active' ORDER BY account_created DESC");
        $stmt->execute();
        $activeAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count total active admins
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins_tbl WHERE account_status = 'active'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalActiveAdmins = $result['count'];
        
        // Count archived/inactive admins
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins_tbl WHERE account_status IN ('inactive', 'suspended')");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalArchivedAdmins = $result['count'];
    }
} catch (Exception $e) {
    // Log error and use fallback values
    error_log("Database error in Users.php: " . $e->getMessage());
    $activeAdmins = [];
    $totalActiveAdmins = 0;
    $totalArchivedAdmins = 0;
    $pdo = null;
}






// Initialize variables with default values
$activeAdmins = [];
$totalActiveAdmins = 0;
$totalArchivedAdmins = 0;
$pdo = null;


// Database connection and admin fetching
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if ($pdo) {
        // Fetch all active admins from database
        $stmt = $pdo->prepare("SELECT * FROM admins_tbl WHERE account_status = 'active' ORDER BY account_created DESC");
        $stmt->execute();
        $activeAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count total active admins
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins_tbl WHERE account_status = 'active'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalActiveAdmins = $result['count'];
        
        // Count archived/inactive admins
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins_tbl WHERE account_status IN ('inactive', 'suspended')");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalArchivedAdmins = $result['count'];
    }
} catch (Exception $e) {
    // Log error and use fallback values
    error_log("Database error in Users.php: " . $e->getMessage());
    $activeAdmins = [];
    $totalActiveAdmins = 0;
    $totalArchivedAdmins = 0;
    $pdo = null;
}

// Fetch archived admins
$archivedAdmins = [];
try {
    if ($pdo) {
        // Fetch all archived/inactive admins from database
        $stmt = $pdo->prepare("SELECT * FROM admins_tbl WHERE account_status IN ('inactive', 'suspended') ORDER BY last_active DESC");
        $stmt->execute();
        $archivedAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update archived count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins_tbl WHERE account_status IN ('inactive', 'suspended')");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalArchivedAdmins = $result['count'];
    }
} catch (Exception $e) {
    error_log("Error fetching archived admins: " . $e->getMessage());
    $archivedAdmins = [];
}

// Function to calculate time difference
function getTimeAgo($datetime) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return "Never";
    }
    
    try {
        $now = new DateTime();
        $lastActive = new DateTime($datetime);
        $diff = $now->diff($lastActive);
        
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

// Function to generate initials from name (first two initials only)
function getInitials($firstName, $middleName = '', $lastName = '') {
    $initials = '';
    
    // Always get first initial from first name
    if (!empty($firstName)) {
        $initials .= strtoupper(substr($firstName, 0, 1));
    }
    
    // Get second initial from middle name if available, otherwise from last name
    if (!empty($middleName)) {
        $initials .= strtoupper(substr($middleName, 0, 1));
    } elseif (!empty($lastName)) {
        $initials .= strtoupper(substr($lastName, 0, 1));
    }
    
    return $initials;
}

// Function to get full name
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

// Function to normalize picture path
function normalizePicturePath($picturePath) {
    if (empty($picturePath) || $picturePath === 'NULL' || strtolower($picturePath) === 'null') {
        return null;
    }
    
    // Handle relative paths that start with ../../
    if (strpos($picturePath, '../../') === 0) {
        // Remove ../../ and replace with /ALERTPOINT/
        $picturePath = str_replace('../../', '/ALERTPOINT/', $picturePath);
    }
    // Handle paths that already start with /ALERTPOINT/
    elseif (strpos($picturePath, '/ALERTPOINT/') === 0) {
        // Keep as is, it's already properly formatted
    }
    // Handle other relative paths - add /ALERTPOINT/ prefix
    elseif (strpos($picturePath, '/') !== 0 && strpos($picturePath, 'http') !== 0) {
        $picturePath = '/ALERTPOINT/' . $picturePath;
    }
    
    return $picturePath;
}

// Add sample residents data (you'll replace this with actual database query)
$activeResidents = [
    [
        'resident_id' => 1,
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'address' => '1110 MBA Compound Barangay Bagumbong Caloocan City',
        'email' => 'juan.delacruz@email.com',
        'phone' => '09123456789',
        'user_status' => 'online',
        'account_status' => 'active',
        'last_active' => date('Y-m-d H:i:s'),
        'picture' => '/ALERTPOINT/uploads/admin/admin_688dd9b59ec06.jpg'
    ],
    [
        'resident_id' => 2,
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'address' => 'Bonifacio Avenue',
        'email' => 'maria.santos@email.com',
        'phone' => '09876543210',
        'user_status' => 'offline',
        'account_status' => 'active',
        'last_active' => '2025-01-01 10:30:00',
        'picture' => null
    ],
    [
        'resident_id' => 4,
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
        'address' => 'Luna Street',
        'email' => 'ana.reyes@email.com',
        'phone' => '09111222333',
        'user_status' => 'online',
        'account_status' => 'active',
        'last_active' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'picture' => null
    ],
    // Add more residents to test pagination (total 15 for testing)
    [
        'resident_id' => 5,
        'first_name' => 'Carlos',
        'last_name' => 'Lopez',
        'address' => 'Quezon Street',
        'email' => 'carlos.lopez@email.com',
        'phone' => '09444555666',
        'user_status' => 'offline',
        'account_status' => 'active',
        'last_active' => '2024-01-02 15:30:00',
        'picture' => null
    ],
    // ... continue with more sample data for pagination testing
];

$archivedResidents = [
    [
        'resident_id' => 3,
        'first_name' => 'Pedro',
        'last_name' => 'Garcia',
        'address' => '1110 MBA Compound Barangay Bagumbong Caloocan City',
        'email' => 'pedro.garcia@email.com',
        'phone' => '09555666777',
        'user_status' => 'offline',
        'account_status' => 'inactive',
        'last_active' => '2023-12-15 14:20:00',
        'picture' => null
    ],
    [
        'resident_id' => 6,
        'first_name' => 'Linda',
        'last_name' => 'Cruz',
        'address' => 'Jose Rizal Avenue',
        'email' => 'linda.cruz@email.com',
        'phone' => '09777888999',
        'user_status' => 'offline',
        'account_status' => 'inactive',
        'last_active' => '2023-11-20 09:15:00',
        'picture' => null
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlertPoint User Management</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/ALERTPOINT/css/Users.css">
    <link rel="stylesheet" href="/ALERTPOINT/css/footer.css">
    <link rel="stylesheet" href="/ALERTPOINT/css/nav-bar-2.css">
    <link rel="stylesheet" href="/ALERTPOINT/css/logout.css">


    <!-- Firebase Scripts -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-firestore-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-storage-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
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
                class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-chart-bar text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Dashboard</span>
            </a>

            <!-- Alerts -->
            <a href="#"
                class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-bell text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Alerts</span>
            </a>

            <!-- Reports -->
            <a href="#"
                class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-chart-line text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Reports</span>
            </a>

            <!-- Users  -->
            <a href="/ALERTPOINT/html/Users.php"
                class="nav-tab active flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-black border-black">
                <i class="fas fa-users text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Users</span>
            </a>

            <!-- Evacuation Plan -->
            <a href="/ALERTPOINT/html/EvacuationPlan.php"
                class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-route text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Evacuation Plan</span>
            </a>

                <!-- Activity Logs -->
                <a href="/ALERTPOINT/html/Activity_Logs.php"
                    class="nav-tab  flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2  text-gray-500  duration-200">
                    <i class="fas fa-history text-lg"></i>
                    <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Activity Logs</span>
                </a>
            <!-- Settings -->
            <a href="#"
                class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-cog text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Settings</span>
            </a>
            </div>
        </div>
    </nav>



    <!-- Main Content -->
    <main class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header (Responsive) -->
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <!-- Title and Description -->
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-1">User Management</h1>
                    <p class="text-gray-600 text-base">Manage resident accounts and admin users for AlertPoint system</p>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row sm:space-x-3 space-y-3 sm:space-y-0">
                    <!-- <button onclick="showAddUserModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-md flex items-center justify-center space-x-2 text-sm shadow-md transition-all">
                        <i class="fas fa-user-plus"></i>
                        <span>Add New User</span>
                    </button> -->

                     <!-- Add Admin Button -->
                    <button onclick="openAddAdminModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                        <i class="fas fa-user-gear mr-2"></i>Add Admin
                    </button>

                </div>
            </div>
        </div>


        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="stat-card hover-card rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Residents</p>
                        <p class="text-2xl font-bold text-gray-900" id="total-residents">0</p>
                    </div>
                    <div class="p-3">
                        <i class="fas fa-house text-blue-600 text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card hover-card rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Online Users</p>
                        <p class="text-2xl font-bold text-gray-900" id="online-users">0</p>
                    </div>
                    <div class="p-3">
                        <i class="fas fa-wifi text-green-600 text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card hover-card rounded-lg p-6" data-total-admins="<?php echo $totalActiveAdmins; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Admins</p>
                        <p class="text-2xl font-bold text-gray-900" id="total-admins"><?php echo $totalActiveAdmins; ?></p>
                    </div>
                    <div class="p-3">
                        <i class="fas fa-user-shield text-purple-600 text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card hover-card rounded-lg p-6" data-archived-count="<?php echo $totalArchivedAdmins; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Archived</p>
                        <p class="text-2xl font-bold text-gray-900" id="archived-count"><?php echo $totalArchivedAdmins; ?></p>
                    </div>
                    <div class="p-3">
                        <i class="fas fa-archive text-red-600 text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>


        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Filter Tabs -->
                <div class="flex flex-wrap gap-2">
                    <button onclick="filterUsers('residents')" class="filter-tab active px-4 py-2 rounded-md text-sm font-medium bg-blue-600 text-white" id="filter-residents">
                        <i class="fas fa-home mr-1"></i>Residents
                    </button>
                    <button onclick="filterUsers('admins')" class="filter-tab px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200" id="filter-admins">
                        <i class="fas fa-user-shield mr-1"></i>Admins
                    </button>
                    <!-- Online/Offline filters will be shown based on current tab -->
                    <div id="status-filters" class="flex gap-2">
                        <button onclick="filterByStatus('online')" class="status-filter px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200" id="filter-online">
                            <i class="fas fa-circle text-green-500 mr-1"></i>Online
                        </button>
                        <button onclick="filterByStatus('offline')" class="status-filter px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200" id="filter-offline">
                            <i class="fas fa-circle text-red-500 mr-1"></i>Offline
                        </button>
                        <button onclick="filterByStatus('all')" class="status-filter px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200" id="filter-all-status">
                            <i class="fas fa-users mr-1"></i>All Status
                        </button>
                    </div>
                </div>

                <!-- Search and Archive Button -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="relative w-full sm:w-64">
                        <input type="text" placeholder="Search users..." class="search-input pl-10 pr-3 py-2 rounded-md w-full text-sm border border-gray-300" id="search-input" oninput="searchUsers()">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                    </div>
                    <button onclick="toggleArchivedSection()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm transition-all duration-200" id="archived-toggle">
                        <i class="fas fa-archive mr-1"></i>Archived
                    </button>
                </div>
            </div>
        </div>


        <!-- USERS SECTION -->
        <!-- Users Section -->
        <div class="bg-white rounded-xl shadow-lg mb-8" id="active-users-section">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900" id="section-title">AlertPoint Residents</h2>
                <?php if ($pdo): ?>
                    <p class="text-xs text-green-600 mt-1">✓ Database Connected</p>
                <?php else: ?>
                    <p class="text-xs text-red-600 mt-1">✗ Database Connection Failed</p>
                <?php endif; ?>
            </div>

            <div class="p-6">
                <!-- Residents Table View -->
                <div id="residents-view">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="residents-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Seen</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="residents-tbody">
                                <?php foreach ($activeResidents as $resident): 
                                    $fullName = getFullName($resident['first_name'], '', $resident['last_name']);
                                    $initials = getInitials($resident['first_name'], '', $resident['last_name']);
                                    $timeAgo = getTimeAgo($resident['last_active']);
                                    $isOnline = $resident['user_status'] === 'online';
                                    $statusClass = $isOnline ? 'online' : 'offline';
                                    $statusText = $isOnline ? 'Online' : 'Offline';
                                    $statusBadge = $isOnline ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                    $accountStatusBadge = $resident['account_status'] === 'active' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800';
                                    $picturePath = normalizePicturePath($resident['picture'] ?? null);
                                    $hasPicture = !empty($picturePath);
                                ?>
                                <tr class="resident-row hover:bg-gray-50" 
                                    data-role="resident" 
                                    data-status="<?php echo $resident['user_status']; ?>" 
                                    data-name="<?php echo strtolower($fullName); ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 relative">
                                                <?php if ($hasPicture): ?>
                                                    <img class="h-10 w-10 rounded-full object-cover" 
                                                        src="<?php echo htmlspecialchars($picturePath); ?>" 
                                                        alt="<?php echo htmlspecialchars($fullName); ?>"
                                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div class="h-10 w-10 bg-gray-500 rounded-full flex items-center justify-center text-white font-medium absolute top-0 left-0" style="display: none;">
                                                        <?php echo htmlspecialchars($initials); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="h-10 w-10 bg-gray-500 rounded-full flex items-center justify-center text-white font-medium">
                                                        <?php echo htmlspecialchars($initials); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="absolute -bottom-0 -right-0 h-3 w-3 <?php echo $isOnline ? 'bg-green-400' : 'bg-red-400'; ?> rounded-full border-2 border-white"></div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($fullName); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs break-words leading-tight whitespace-pre-wrap"><?php echo htmlspecialchars($resident['address']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($resident['email'] ?? 'resident@example.com'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($resident['phone']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusBadge; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $accountStatusBadge; ?>">
                                            <?php echo ucfirst($resident['account_status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($timeAgo); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="editResident('<?php echo $resident['resident_id']; ?>')" 
                                                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition-colors">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            <button onclick="archiveResident('<?php echo $resident['resident_id']; ?>')" 
                                                    class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded text-xs transition-colors">
                                                <i class="fas fa-archive mr-1"></i>Archive
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                     <!-- ADD: Residents Pagination -->
                    <div id="residents-pagination" class="flex items-center justify-between px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center text-sm text-gray-700">
                            <span>Showing</span>
                            <span id="residents-showing-start" class="mx-1 font-medium">1</span>
                            <span>to</span>
                            <span id="residents-showing-end" class="mx-1 font-medium">10</span>
                            <span>of</span>
                            <span id="residents-total-count" class="mx-1 font-medium">0</span>
                            <span>residents</span>
                        </div>
                        <div id="residents-pagination-buttons" class="flex space-x-1">
                            <!-- Pagination buttons will be generated by JavaScript -->
                        </div>
                    </div>

                      <!-- No residents message -->
                        <?php if (empty($activeResidents)): ?>
                        <div class="text-center py-8 text-gray-500" id="no-residents">
                            <i class="fas fa-home text-4xl mb-4 text-gray-400"></i>
                            <p class="text-lg font-semibold">No Residents Found</p>
                            <p class="text-sm">No resident accounts found in the database.</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    
                    <!-- No residents message -->
                    <?php if (empty($activeResidents)): ?>
                    <div class="text-center py-8 text-gray-500" id="no-residents">
                        <i class="fas fa-home text-4xl mb-4 text-gray-400"></i>
                        <p class="text-lg font-semibold">No Residents Found</p>
                        <p class="text-sm">No resident accounts found in the database.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Admins Cards View -->
                <div id="admins-view" style="display: none;">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 px-6 pb-8" id="admins-grid">
                        <!-- Dynamic Admin Cards from Database -->
                        <?php if (!empty($activeAdmins)): ?>
                            <?php foreach ($activeAdmins as $admin): 
                                $fullName = getFullName(
                                    $admin['first_name'] ?? '', 
                                    $admin['middle_name'] ?? '', 
                                    $admin['last_name'] ?? ''
                                );
                                $initials = getInitials(
                                    $admin['first_name'] ?? '', 
                                    $admin['middle_name'] ?? '', 
                                    $admin['last_name'] ?? ''
                                );
                                $timeAgo = getTimeAgo($admin['last_active'] ?? null);
                                $isOnline = ($admin['user_status'] ?? 'offline') === 'online';
                                $statusBgColor = $isOnline ? 'bg-green-500' : 'bg-red-500';
                                $statusBadgeClass = $isOnline ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                $statusText = $isOnline ? 'Online' : 'Offline';
                                $statusClass = $isOnline ? 'online' : 'offline';
                                
                                // Generate random avatar background colors
                                $avatarColors = ['bg-violet-500', 'bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-indigo-500', 'bg-purple-500', 'bg-pink-500'];
                                $avatarColor = $avatarColors[array_rand($avatarColors)];
                                
                                // Check if user has a profile picture
                                $picturePath = normalizePicturePath($admin['picture'] ?? null);
                                $hasPicture = !empty($picturePath);
                            ?>
                            
                            <div class="user-card admin-card bg-white rounded-xl p-6 border border-gray-200 shadow-sm" 
                                data-role="admin" 
                                data-status="<?php echo htmlspecialchars($admin['user_status'] ?? 'offline'); ?>" 
                                data-name="<?php echo htmlspecialchars(strtolower($fullName)); ?>"
                                data-user-role="<?php echo htmlspecialchars($admin['barangay_position'] ?? 'Admin'); ?>">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="relative">
                                            <?php if ($hasPicture): ?>
                                                <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-gray-200">
                                                    <img src="<?php echo htmlspecialchars($picturePath); ?>" 
                                                        alt="<?php echo htmlspecialchars($fullName); ?>" 
                                                        class="w-full h-full object-cover"
                                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                </div>
                                                <!-- Fallback initials div (hidden by default, shown on image error) -->
                                                <div class="w-12 h-12 <?php echo $avatarColor; ?> text-white rounded-full flex items-center justify-center font-semibold text-lg border-2 border-gray-200 absolute top-0 left-0" style="display: none;">
                                                    <?php echo htmlspecialchars($initials); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="w-12 h-12 <?php echo $avatarColor; ?> text-white rounded-full flex items-center justify-center font-semibold text-lg border-2 border-gray-200">
                                                    <?php echo htmlspecialchars($initials); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="absolute -bottom-1 -right-0 w-4 h-4 <?php echo $statusBgColor; ?> rounded-full border-2 border-white <?php echo $statusClass; ?>"></div>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($fullName); ?></h3>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($admin['barangay_position'] ?? 'Admin'); ?></p>
                                            <p class="text-xs text-gray-500 flex items-center mt-1">
                                                <i class="fas fa-user-shield text-gray-400 mr-2"></i><?php echo htmlspecialchars($admin['username'] ?? 'N/A'); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusBadgeClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                        <span class="text-xs text-gray-500 mt-1 capitalize px-2 py-1"><?php echo htmlspecialchars($admin['role'] ?? 'Admin'); ?></span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                    <div class="text-xs text-gray-500">
                                        Last seen: <?php echo htmlspecialchars($timeAgo); ?>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition-colors"
                                                onclick="editAdmin('<?php echo htmlspecialchars($admin['admin_id'] ?? ''); ?>')">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </button>
                                        <button class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded text-xs transition-colors"
                                                onclick="archiveAdmin('<?php echo htmlspecialchars($admin['admin_id'] ?? ''); ?>')">
                                            <i class="fas fa-archive mr-1"></i>Archive
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- No Admins Message (show if no admins found) -->
                        <?php if (empty($activeAdmins)): ?>
                        <div class="col-span-full text-center py-8 text-gray-500" id="no-active-admins">
                            <i class="fas fa-user-shield text-4xl mb-4 text-gray-400"></i>
                            <p class="text-lg font-semibold">No Active Admins</p>
                            <p class="text-sm">No active admin accounts found in the database.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- No Results Message (initially hidden) -->
                <div class="text-center py-8 text-gray-500 hidden" id="no-results">
                    <i class="fas fa-user-slash text-4xl mb-4 text-red-500"></i>
                    <p class="text-lg font-semibold">User Not Found</p>
                    <p class="text-sm">No users match your search criteria.</p>
                </div>
            </div>
        </div>

       <!-- Archived Users Section -->
        <div class="bg-white rounded-xl shadow-lg mb-8 hidden" id="archived-section">
            <div class="p-6 border-b border-gray-200">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-archive text-orange-600 mr-3"></i>
                        Archived Users
                    </h2>
                    
                    <!-- Archived Search -->
                    <div class="flex items-center">
                        <div class="relative w-64">
                            <input type="text" placeholder="Search archived users..." class="archived-search-input pl-10 pr-3 py-2 rounded-md w-full text-sm border border-gray-300" id="archived-search-input" oninput="searchArchivedUsers()">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <!-- Archived content will be mixed (residents table + admin cards) -->
                <div id="archived-content">
                    <!-- Archived Residents Table -->
                    <div id="archived-residents-section" class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                            <i class="fas fa-home text-gray-500 mr-2"></i>Archived Residents
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Seen</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="archived-residents-tbody">
                                    <?php foreach ($archivedResidents as $resident): 
                                        $fullName = getFullName($resident['first_name'], '', $resident['last_name']);
                                        $initials = getInitials($resident['first_name'], '', $resident['last_name']);
                                        $timeAgo = getTimeAgo($resident['last_active']);
                                        $picturePath = normalizePicturePath($resident['picture'] ?? null);
                                        $hasPicture = !empty($picturePath);
                                        $statusBadge = $resident['user_status'] === 'online' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                    ?>
                                    <tr class="archived-resident-row opacity-75 hover:bg-gray-50" 
                                        data-role="resident" 
                                        data-status="archived" 
                                        data-name="<?php echo strtolower($fullName); ?>">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 relative">
                                                    <?php if ($hasPicture): ?>
                                                        <img class="h-10 w-10 rounded-full object-cover opacity-60" 
                                                            src="<?php echo htmlspecialchars($picturePath); ?>" 
                                                            alt="<?php echo htmlspecialchars($fullName); ?>">
                                                    <?php else: ?>
                                                        <div class="h-10 w-10 bg-gray-500 rounded-full flex items-center justify-center text-white font-medium opacity-60">
                                                            <?php echo htmlspecialchars($initials); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="absolute -bottom-0 -right-0 h-3 w-3 bg-gray-400 rounded-full border-2 border-white"></div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-600"><?php echo htmlspecialchars($fullName); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-500 max-w-xs break-words leading-tight whitespace-pre-wrap"><?php echo htmlspecialchars($resident['address']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($resident['email'] ?? 'resident@example.com'); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($resident['phone']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusBadge; ?>">
                                                <?php echo ucfirst($resident['user_status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Inactive
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                            <?php echo htmlspecialchars($timeAgo); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button onclick="restoreResident('<?php echo $resident['resident_id']; ?>')" 
                                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs transition-colors">
                                                    <i class="fas fa-undo mr-1"></i>Restore
                                                </button>
                                                <button onclick="deleteResident('<?php echo $resident['resident_id']; ?>')" 
                                                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs transition-colors">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- ADD: Archived Residents Pagination -->
                        <div id="archived-residents-pagination" class="flex items-center justify-between px-6 py-4 border-t border-gray-200">
                            <div class="flex items-center text-sm text-gray-700">
                                <span>Showing</span>
                                <span id="archived-residents-showing-start" class="mx-1 font-medium">1</span>
                                <span>to</span>
                                <span id="archived-residents-showing-end" class="mx-1 font-medium">10</span>
                                <span>of</span>
                                <span id="archived-residents-total-count" class="mx-1 font-medium">0</span>
                                <span>archived residents</span>
                            </div>
                            <div id="archived-residents-pagination-buttons" class="flex space-x-1">
                                <!-- Pagination buttons will be generated by JavaScript -->
                            </div>
                        </div>
                        
                        <!-- No archived residents message -->
                        <?php if (empty($archivedResidents)): ?>
                        <div class="text-center py-8 text-gray-500" id="no-archived-residents">
                            <i class="fas fa-home text-4xl mb-4 text-gray-400"></i>
                            <p class="text-lg font-semibold">No Archived Residents</p>
                            <p class="text-sm">No archived resident accounts found.</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Archived Admins Cards -->
                    <div id="archived-admins-section">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                            <i class="fas fa-user-shield text-gray-500 mr-2"></i>Archived Admins
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="archived-admins-grid">
                            <!-- Dynamic Archived Admin Cards from Database -->
                            <?php if (!empty($archivedAdmins)): ?>
                                <?php foreach ($archivedAdmins as $admin): 
                                    $fullName = getFullName(
                                        $admin['first_name'] ?? '', 
                                        $admin['middle_name'] ?? '', 
                                        $admin['last_name'] ?? ''
                                    );
                                    $initials = getInitials(
                                        $admin['first_name'] ?? '', 
                                        $admin['middle_name'] ?? '', 
                                        $admin['last_name'] ?? ''
                                    );
                                    $timeAgo = getTimeAgo($admin['last_active'] ?? null);
                                    $statusText = ucfirst($admin['account_status'] ?? 'Archived');
                                    $statusBadgeClass = 'bg-gray-100 text-gray-800';
                                    
                                    // Generate random avatar background colors
                                    $avatarColors = ['bg-gray-500', 'bg-slate-500', 'bg-zinc-500'];
                                    $avatarColor = $avatarColors[array_rand($avatarColors)];
                                    
                                    // Check if user has a profile picture
                                    $picturePath = normalizePicturePath($admin['picture'] ?? null);
                                    $hasPicture = !empty($picturePath);
                                ?>
                                
                                <div class="user-card archived-admin-card bg-white rounded-xl p-6 border border-gray-200 shadow-sm opacity-75" 
                                    data-role="admin" 
                                    data-status="archived" 
                                    data-name="<?php echo htmlspecialchars(strtolower($fullName)); ?>"
                                    data-user-role="<?php echo htmlspecialchars($admin['barangay_position'] ?? 'Admin'); ?>">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="relative">
                                                <?php if ($hasPicture): ?>
                                                    <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-gray-200 opacity-60">
                                                        <img src="<?php echo htmlspecialchars($picturePath); ?>" 
                                                            alt="<?php echo htmlspecialchars($fullName); ?>" 
                                                            class="w-full h-full object-cover"
                                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    </div>
                                                    <!-- Fallback initials div -->
                                                    <div class="w-12 h-12 <?php echo $avatarColor; ?> text-white rounded-full flex items-center justify-center font-semibold text-lg border-2 border-gray-200 opacity-60 absolute top-0 left-0" style="display: none;">
                                                        <?php echo htmlspecialchars($initials); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="w-12 h-12 <?php echo $avatarColor; ?> text-white rounded-full flex items-center justify-center font-semibold text-lg border-2 border-gray-200 opacity-60">
                                                        <?php echo htmlspecialchars($initials); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="absolute -bottom-1 -right-0 w-4 h-4 bg-gray-400 rounded-full border-2 border-white"></div>
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-gray-600 text-lg"><?php echo htmlspecialchars($fullName); ?></h3>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($admin['barangay_position'] ?? 'Admin'); ?></p>
                                                <p class="text-xs text-gray-400 flex items-center mt-1">
                                                    <i class="fas fa-user-shield text-gray-400 mr-2"></i><?php echo htmlspecialchars($admin['username'] ?? 'N/A'); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end">
                                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusBadgeClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                            <span class="text-xs text-gray-400 mt-1 capitalize px-2 py-1"><?php echo htmlspecialchars($admin['role'] ?? 'Admin'); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                        <div class="text-xs text-gray-400">
                                            Last Seen: <?php echo htmlspecialchars($timeAgo); ?>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs transition-colors"
                                                    onclick="restoreAdmin('<?php echo htmlspecialchars($admin['admin_id'] ?? ''); ?>')">
                                                <i class="fas fa-undo mr-1"></i>Restore
                                            </button>
                                            <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs transition-colors"
                                                    onclick="deleteAdmin('<?php echo htmlspecialchars($admin['admin_id'] ?? ''); ?>')">
                                                <i class="fas fa-trash mr-1"></i>Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- No Archived Admins Message -->
                            <?php if (empty($archivedAdmins)): ?>
                            <div class="col-span-full text-center py-8 text-gray-500" id="no-archived-admins">
                                <i class="fas fa-user-shield text-4xl mb-4 text-gray-400"></i>
                                <p class="text-lg font-semibold">No Archived Admins</p>
                                <p class="text-sm">No archived admin accounts found.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

       <!-- Add Admin Modal -->
    <div id="addAdminModal" class="fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 animate-fade-in max-h-[95vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-blue-50 rounded-t-xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-plus text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Add Barangay Admin</h3>
                </div>
               <button onclick="confirmClose()" class="text-gray-400 p-2 rounded-full">
                    <i class="fas fa-times text-lg !transition-none !transform-none"></i>
                </button>


                
            </div>

            <!-- Form -->
            <div class="p-6">
                <form id="addAdminForm" class="space-y-6">
                    
                    <!-- Two Column Layout -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        
                        <!-- Left Column -->
                        <div class="space-y-6">
                            
                            <!-- Personal Information Section -->
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-blue-600 text-sm"></i>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900">Personal Information</h4>
                                </div>
                                
                                <!-- Profile Photo - Inside Personal Information -->
                                <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                                    <div class="profile-image-container">
                                        <img id="profilePreview" src="" alt="Profile Preview" class="profile-image hidden">
                                        <div id="uploadPlaceholder" class="upload-placeholder">
                                            <i class="fas fa-camera text-gray-400 text-lg"></i>
                                        </div>
                                        <div class="image-overlay">
                                            <i class="fas fa-camera text-white text-sm"></i>
                                        </div>
                                        <input type="file" id="profileImageInput" accept=".png,.jpg,.jpeg" class="hidden">
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-700 mb-2">Profile Photo</p>
                                        <div class="flex space-x-2 mb-2">
                                            <button type="button" onclick="uploadPhoto()" class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors">
                                                <i class="fas fa-upload mr-1"></i>Upload
                                            </button>
                                            <button type="button" onclick="removePhoto()" id="removePhotoBtn" class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition-colors hidden">
                                                <i class="fas fa-trash mr-1"></i>Remove
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG, JPEG only. Max 5MB</p>
                                        <div class="error-message text-red-500 text-xs mt-1 hidden" id="photoError"></div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-user-circle text-gray-400 mr-2"></i>First Name *
                                        </label>
                                        <input type="text" id="admin_fn" name="admin_fn" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                               placeholder="Enter first name" required>
                                        <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-user-circle text-gray-400 mr-2"></i>Last Name *
                                        </label>
                                        <input type="text" id="admin_ln" name="admin_ln" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                               placeholder="Enter last name" required>
                                        <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user text-gray-400 mr-2"></i>Middle Name
                                    </label>
                                    <input type="text" id="admin_mn" name="admin_mn" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                           placeholder="Enter middle name (optional)">
                                </div>

                                <!-- Email Field - Added Here -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-envelope text-gray-400 mr-2"></i>Email Address *
                                    </label>
                                    <input type="email" id="user_email" name="user_email" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                           placeholder="Enter email address (e.g., user@gmail.com)" required>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                    <div class="flex items-center text-xs text-gray-500 mt-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Must contain "@" and end with ".com"
                                    </div>
                                </div>

                                <!-- Birthdate Section -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar text-gray-400 mr-2"></i>Birthdate *
                                    </label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <select id="birth_month" name="birth_month" 
                                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" required>
                                                <option value="">Month</option>
                                                <option value="01">January</option>
                                                <option value="02">February</option>
                                                <option value="03">March</option>
                                                <option value="04">April</option>
                                                <option value="05">May</option>
                                                <option value="06">June</option>
                                                <option value="07">July</option>
                                                <option value="08">August</option>
                                                <option value="09">September</option>
                                                <option value="10">October</option>
                                                <option value="11">November</option>
                                                <option value="12">December</option>
                                            </select>
                                        </div>
                                        <div>
                                            <select id="birth_day" name="birth_day" 
                                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" required>
                                                <option value="">Day</option>
                                            </select>
                                        </div>
                                        <div>
                                            <select id="birth_year" name="birth_year" 
                                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" required>
                                                <option value="">Year</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden" id="birthdateError"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            
                            <!-- Barangay Position Section -->
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-briefcase text-green-600 text-sm"></i>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900">Barangay Position</h4>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-id-badge text-gray-400 mr-2"></i>Position/Role *
                                    </label>
                                    <select id="role" name="role" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" required>
                                        <option value="">Select Position</option>
                                        <option value="Barangay Captain (Punong Barangay)">Barangay Captain (Punong Barangay)</option>
                                        <option value="Barangay Councilor (Kagawad)">Barangay Councilor (Kagawad)</option>
                                        <option value="SK Chairperson">SK Chairperson</option>
                                        <option value="Barangay Secretary">Barangay Secretary</option>
                                        <option value="Barangay Treasurer">Barangay Treasurer</option>
                                        <option value="Barangay Tanod (Watchmen)">Barangay Tanod (Watchmen)</option>
                                        <option value="Lupong Tagapamayapa Member">Lupong Tagapamayapa Member</option>
                                        <option value="Barangay Health Worker (BHW)">Barangay Health Worker (BHW)</option>
                                        <option value="Day Care Worker">Day Care Worker</option>
                                        <option value="Barangay Nutrition Scholar (BNS)">Barangay Nutrition Scholar (BNS)</option>
                                        <option value="BDRRMC Member">BDRRMC Member</option>
                                        <option value="other">Other (Specify)</option>
                                    </select>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>
                                
                                <div id="customRoleDiv" class="hidden">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-edit text-gray-400 mr-2"></i>Specify Position *
                                    </label>
                                    <input type="text" id="customRole" name="customRole" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                           placeholder="Enter custom position">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>
                            </div>

                            <!-- Account Information Section -->
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-key text-purple-600 text-sm"></i>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900">Account Information</h4>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-at text-gray-400 mr-2"></i>Username *
                                    </label>
                                    <input type="text" id="username" name="username" readonly
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed">
                                    <div class="flex items-center text-xs text-gray-500 mt-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Auto-generated: admin_[firstname] (numbered if duplicate)
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-lock text-gray-400 mr-2"></i>Password *
                                    </label>
                                    <div class="relative">
                                        <input type="password" id="password" name="password" 
                                               class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                               placeholder="Enter password" required>
                                       <button type="button" onclick="togglePassword()"
                                            class="absolute right-4 top-3 translate-y-1 p-0 rounded text-gray-400 hover:text-gray-500 !transition-none !transform-none">
                                            <i id="passwordToggle" class="fas fa-eye pointer-events-none"></i>
                                        </button>


                                    </div>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                    <div class="flex items-center text-xs text-gray-500 mt-2">
                                        <i class="fas fa-shield-alt mr-1"></i>
                                        Minimum 8 characters with letters and numbers
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                        <button type="button" onclick="confirmClose()" 
                                class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                            <i class="fas fa-times"></i>
                            <span>Cancel</span>
                        </button>
                        <button type="submit" 
                                class="px-6 py-3 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                            <i class="fas fa-plus"></i>
                            <span>Create Admin</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 animate-fade-in">
            <div class="p-6 text-center">
                <div id="confirmIcon" class="w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center">
                    <i id="confirmIconClass" class="text-xl"></i>
                </div>
                <h3 id="confirmTitle" class="text-lg font-medium mb-4"></h3>
                <div id="confirmMessage" class="text-gray-600 text-sm mb-6"></div>
                <div class="flex justify-center space-x-3">
                    <button id="confirmCancel" class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors">
                        Cancel
                    </button>
                    <button id="confirmAction" class="px-4 py-2 text-sm rounded-md transition-colors">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Details Confirmation Modal -->
    <div id="adminDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 animate-fade-in">
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-blue-50 rounded-t-xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-check text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Confirm Admin Details</h3>
                </div>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-6">Please review the admin details before creating the account:</p>
                <div id="adminDetailsContent" class="bg-gray-50 rounded-lg p-4 mb-6">
                    <!-- Details will be populated by JavaScript -->
                </div>
                <div class="flex justify-end space-x-4">
                    <button id="cancelDetailsBtn" class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button id="confirmDetailsBtn" class="px-6 py-3 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        Create Admin
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in">
            <div class="p-6 text-center">
                <div class="w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center bg-green-100">
                    <i class="fas fa-check-circle text-xl text-green-500"></i>
                </div>
                <h3 class="text-lg font-medium mb-4 text-gray-900">Success!</h3>
                <div id="successMessage" class="text-gray-600 text-sm mb-6"></div>
                <button id="successOkBtn" class="px-6 py-3 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                    OK
                </button>
            </div>
        </div>
    </div>


    <!-- EDIT AND ARCHIVE ADMIN MODALS -->
    <!-- Edit Admin Modal -->
<div id="editAdminModal" class="fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 max-h-[95vh] overflow-y-auto">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-blue-50 rounded-t-xl">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-edit text-blue-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">Edit Admin Details</h3>
            </div>
           <button onclick="closeEditAdminModal()" class="text-gray-400 p-2 rounded-full">
                <i class="fas fa-times text-lg !transition-none !transform-none"></i>
            </button>

        </div>

        <!-- Form -->
        <div class="p-6">
            <form id="editAdminForm" class="space-y-6">
                <input type="hidden" id="edit_admin_id" name="edit_admin_id">
                
                <!-- Two Column Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- Left Column -->
                    <div class="space-y-6">
                        
                        <!-- Personal Information Section -->
                        <div class="space-y-4">
                            <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-blue-600 text-sm"></i>
                                </div>
                                <h4 class="text-lg font-medium text-gray-900">Personal Information</h4>
                            </div>
                            
                            <!-- Profile Photo -->
                            <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                                <div class="profile-image-container relative w-16 h-16">
                                <img id="editProfilePreview" src="" alt="Profile Preview"
                                    class="absolute top-0 left-0 w-16 h-16 rounded-full object-cover border-2 border-gray-200 hidden">
                                    <div id="editUploadPlaceholder" class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center border-2 border-gray-300">
                                        <i class="fas fa-camera text-gray-400 text-lg"></i>
                                    </div>
                                    <input type="file" id="editProfileImageInput" accept=".png,.jpg,.jpeg" class="hidden">
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Profile Photo</p>
                                    <div class="flex space-x-2 mb-2">
                                        <button type="button" onclick="editUploadPhoto()" class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors">
                                            <i class="fas fa-upload mr-1"></i>Upload
                                        </button>
                                        <button type="button" onclick="editRemovePhoto()" id="editRemovePhotoBtn" class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition-colors hidden">
                                            <i class="fas fa-trash mr-1"></i>Remove
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, JPEG only. Max 5MB</p>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden" id="editPhotoError"></div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user-circle text-gray-400 mr-2"></i>First Name
                                    </label>
                                    <input type="text" id="edit_admin_fn" name="edit_admin_fn" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                        placeholder="First name" readonly>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                    <div class="flex items-center text-xs text-gray-500 mt-1">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        This field cannot be changed
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user-circle text-gray-400 mr-2"></i>Last Name
                                    </label>
                                    <input type="text" id="edit_admin_ln" name="edit_admin_ln" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                        placeholder="Last name" readonly>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                    <div class="flex items-center text-xs text-gray-500 mt-1">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        This field cannot be changed
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user text-gray-400 mr-2"></i>Middle Name
                                </label>
                                <input type="text" id="edit_admin_mn" name="edit_admin_mn" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                    placeholder="Middle name" readonly>
                                <div class="flex items-center text-xs text-gray-500 mt-1">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    This field cannot be changed
                                </div>
                            </div>

                            <!-- Birthdate Section -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar text-gray-400 mr-2"></i>Birthdate
                                </label>
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <select id="edit_birth_month" name="edit_birth_month" 
                                                class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" disabled>
                                            <option value="">Month</option>
                                            <option value="01">January</option>
                                            <option value="02">February</option>
                                            <option value="03">March</option>
                                            <option value="04">April</option>
                                            <option value="05">May</option>
                                            <option value="06">June</option>
                                            <option value="07">July</option>
                                            <option value="08">August</option>
                                            <option value="09">September</option>
                                            <option value="10">October</option>
                                            <option value="11">November</option>
                                            <option value="12">December</option>
                                        </select>
                                    </div>
                                    <div>
                                        <select id="edit_birth_day" name="edit_birth_day" 
                                                class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" disabled>
                                            <option value="">Day</option>
                                        </select>
                                    </div>
                                    <div>
                                        <select id="edit_birth_year" name="edit_birth_year" 
                                                class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" disabled>
                                            <option value="">Year</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex items-center text-xs text-gray-500 mt-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Birthdate cannot be changed
                                </div>
                                <div class="error-message text-red-500 text-xs mt-1 hidden" id="editBirthdateError"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        
                        <!-- Barangay Position Section -->
                        <div class="space-y-4">
                            <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-briefcase text-green-600 text-sm"></i>
                                </div>
                                <h4 class="text-lg font-medium text-gray-900">Barangay Position</h4>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-id-badge text-gray-400 mr-2"></i>Position/Role *
                                </label>
                                <select id="edit_role" name="edit_role" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" required>
                                    <option value="">Select Position</option>
                                    <option value="Barangay Captain (Punong Barangay)">Barangay Captain (Punong Barangay)</option>
                                    <option value="Barangay Councilor (Kagawad)">Barangay Councilor (Kagawad)</option>
                                    <option value="SK Chairperson">SK Chairperson</option>
                                    <option value="Barangay Secretary">Barangay Secretary</option>
                                    <option value="Barangay Treasurer">Barangay Treasurer</option>
                                    <option value="Barangay Tanod (Watchmen)">Barangay Tanod (Watchmen)</option>
                                    <option value="Lupong Tagapamayapa Member">Lupong Tagapamayapa Member</option>
                                    <option value="Barangay Health Worker (BHW)">Barangay Health Worker (BHW)</option>
                                    <option value="Day Care Worker">Day Care Worker</option>
                                    <option value="Barangay Nutrition Scholar (BNS)">Barangay Nutrition Scholar (BNS)</option>
                                    <option value="BDRRMC Member">BDRRMC Member</option>
                                    <option value="other">Other (Specify)</option>
                                </select>
                                <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                            </div>
                            
                            <div id="editCustomRoleDiv" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-edit text-gray-400 mr-2"></i>Specify Position *
                                </label>
                                <input type="text" id="editCustomRole" name="editCustomRole" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                    placeholder="Enter custom position">
                                <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                            </div>
                        </div>

                        <!-- Account Information Section (Read-only) -->
                        <div class="space-y-4">
                            <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-key text-purple-600 text-sm"></i>
                                </div>
                                <h4 class="text-lg font-medium text-gray-900">Account Information</h4>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-id-card text-gray-400 mr-2"></i>Admin ID
                                </label>
                                <input type="text" id="edit_admin_id_display" readonly
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-at text-gray-400 mr-2"></i>Username
                                </label>
                                <input type="text" id="edit_username" readonly
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed">
                                <div class="flex items-center text-xs text-gray-500 mt-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Username cannot be changed
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <button type="button" onclick="closeEditAdminModal()" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </button>
                    <button type="submit" 
                            class="px-6 py-3 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                        <i class="fas fa-save"></i>
                        <span>Update Admin</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Edit Confirmation Modal -->
    <div id="editConfirmationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 animate-fade-in relative z-[10000]">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-blue-50 rounded-t-xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-question-circle text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Confirm Changes</h3>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="text-center mb-6">
                    <p class="text-gray-600 text-sm mb-4">Are you sure you want to update this admin's information?</p>
                </div>
                
                <!-- Details Display -->
                <div id="editConfirmationContent" class="space-y-3 mb-6 bg-gray-50 p-4 rounded-lg">
                    <!-- Details will be populated by JavaScript -->
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-center space-x-4">
                    <button id="cancelEditBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </button>
                    <button id="confirmEditBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                        <i class="fas fa-check"></i>
                        <span>Confirm Update</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Archive Confirmation Modal -->
    <div id="archiveConfirmationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-orange-50 rounded-t-xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-archive text-orange-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Archive Admin</h3>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-orange-500 text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Are you sure?</h4>
                    <p class="text-gray-600 text-sm mb-4">
                        This will archive the admin account. The admin will not be able to access the system until the account is restored.
                    </p>
                    <div id="archiveAdminDetails" class="bg-gray-50 p-3 rounded-lg mb-4">
                        <!-- Admin details will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-center space-x-4">
                    <button id="cancelArchiveBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </button>
                    <button id="confirmArchiveBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                        <i class="fas fa-archive"></i>
                        <span>Archive Admin</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Success Modal -->
    <div id="editSuccessModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Content -->
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Success!</h3>
                <p id="editSuccessMessage" class="text-gray-600 text-sm mb-6">
                    Admin information has been updated successfully.
                </p>
                <button id="editSuccessOkBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors shadow-lg">
                    <i class="fas fa-check mr-2"></i>OK
                </button>
            </div>
        </div>
    </div>

    <!-- Archive Success Modal -->
    <div id="archiveSuccessModal" class="fixed inset-0 z-[9999] items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Content -->
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-archive text-orange-500 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Admin Archived!</h3>
                <p id="archiveSuccessMessage" class="text-gray-600 text-sm mb-6">
                    The admin account has been archived successfully.
                </p>
                <button id="archiveSuccessOkBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors shadow-lg">
                    <i class="fas fa-check mr-2"></i>OK
                </button>
            </div>
        </div>
    </div>


    <!-- UNARCHIVE AND DELETE -->
     <!-- Add these modals after the existing Archive Success Modal in Users.php -->

    <!-- Restore Confirmation Modal -->
    <div id="restoreConfirmationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-green-50 rounded-t-xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-undo text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Restore Admin</h3>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-check text-green-500 text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Restore Admin Account</h4>
                    <p class="text-gray-600 text-sm mb-4">
                        This will restore the admin account and allow them to access the system again.
                    </p>
                    <div id="restoreAdminDetails" class="bg-gray-50 p-3 rounded-lg mb-4">
                        <!-- Admin details will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-center space-x-4">
                    <button id="cancelRestoreBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </button>
                    <button id="confirmRestoreBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                        <i class="fas fa-undo"></i>
                        <span>Restore Admin</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-red-50 rounded-t-xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-trash text-red-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Delete Admin</h3>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Permanent Deletion</h4>
                    <p class="text-gray-600 text-sm mb-4">
                        <strong class="text-red-600">Warning:</strong> This action cannot be undone. The admin account and all associated data will be permanently deleted from the system.
                    </p>
                    <div id="deleteAdminDetails" class="bg-red-50 border border-red-200 p-3 rounded-lg mb-4">
                        <!-- Admin details will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-center space-x-4">
                    <button id="cancelDeleteBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </button>
                    <button id="confirmDeleteBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                        <i class="fas fa-trash"></i>
                        <span>Delete Permanently</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Success Modal -->
    <div id="restoreSuccessModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Content -->
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-check text-green-500 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Admin Restored!</h3>
                <p id="restoreSuccessMessage" class="text-gray-600 text-sm mb-6">
                    The admin account has been restored successfully and can now access the system.
                </p>
                <button id="restoreSuccessOkBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors shadow-lg">
                    <i class="fas fa-check mr-2"></i>OK
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Success Modal -->
    <div id="deleteSuccessModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Content -->
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-red-500 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Admin Deleted!</h3>
                <p id="deleteSuccessMessage" class="text-gray-600 text-sm mb-6">
                    The admin account has been permanently deleted from the system.
                </p>
                <button id="deleteSuccessOkBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors shadow-lg">
                    <i class="fas fa-check mr-2"></i>OK
                </button>
            </div>
        </div>
    </div>

    

   <!-- ADD: Edit Resident Modal -->
<div id="editResidentModal" class="fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 max-h-[95vh] overflow-y-auto">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-blue-50 rounded-t-xl">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-edit text-blue-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">Edit Resident Details</h3>
            </div>
            <button onclick="closeEditResidentModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-full">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Form -->
        <div class="p-6">
            <form id="editResidentForm">
                <input type="hidden" id="edit_resident_id" name="resident_id">
                
                <!-- Personal Information Section -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-blue-600 text-sm"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-900">Personal Information</h4>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- First Name -->
                        <div>
                            <label for="edit_resident_fn" class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                            <input type="text" id="edit_resident_fn" name="first_name" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label for="edit_resident_ln" class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                            <input type="text" id="edit_resident_ln" name="last_name" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="edit_resident_email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" id="edit_resident_email" name="email" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="edit_resident_phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                            <input type="tel" id="edit_resident_phone" name="phone" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- Address (Full Width) -->
                    <div>
                        <label for="edit_resident_address" class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
                        <textarea id="edit_resident_address" name="address" required rows="3"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200 mt-6">
                    <button type="button" onclick="closeEditResidentModal()" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit" 
                            class="px-6 py-3 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-save mr-2"></i>Update Resident
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Success Modal for Edit Resident -->
<div id="editResidentSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in">
        <div class="p-8 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check-circle text-green-500 text-2xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Success!</h3>
            <p class="text-gray-600 text-sm mb-6">Resident information has been updated successfully.</p>
            <button id="editResidentSuccessOkBtn" type="button" 
                    class="px-6 py-3 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                <i class="fas fa-check mr-2"></i>OK
            </button>
        </div>
    </div>
</div>

<!-- ADD: Archive Resident Modal -->
<div id="archiveResidentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-orange-50 rounded-t-xl">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-archive text-orange-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">Archive Resident</h3>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-orange-500 text-2xl"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2">Are you sure?</h4>
                <p class="text-gray-600 text-sm mb-4">
                    This will archive the resident account. The resident will not be able to access the system until the account is restored.
                </p>
                <div id="archiveResidentDetails" class="bg-gray-50 p-3 rounded-lg mb-4">
                    <!-- Resident details will be populated by JavaScript -->
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-center space-x-4">
                <button id="cancelArchiveResidentBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button id="confirmArchiveResidentBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-archive mr-2"></i>Archive Resident
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ADD: Restore/Delete Resident Modals (similar to admin modals) -->
<div id="restoreResidentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-green-50 rounded-t-xl">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-undo text-green-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">Restore Resident</h3>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-check text-green-500 text-2xl"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2">Restore Resident Account</h4>
                <p class="text-gray-600 text-sm mb-4">
                    This will restore the resident account and allow them to access the system again.
                </p>
                <div id="restoreResidentDetails" class="bg-gray-50 p-3 rounded-lg mb-4">
                    <!-- Resident details will be populated by JavaScript -->
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-center space-x-4">
                <button id="cancelRestoreResidentBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button id="confirmRestoreResidentBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-undo mr-2"></i>Restore Resident
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ADD: Delete Resident Modal -->
<div id="deleteResidentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-red-50 rounded-t-xl">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-trash text-red-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">Delete Resident</h3>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2">Permanent Deletion</h4>
                <p class="text-gray-600 text-sm mb-4">
                    <strong class="text-red-600">Warning:</strong> This action cannot be undone. The resident account and all associated data will be permanently deleted from the system.
                </p>
                <div id="deleteResidentDetails" class="bg-red-50 border border-red-200 p-3 rounded-lg mb-4">
                    <!-- Resident details will be populated by JavaScript -->
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-center space-x-4">
                <button id="cancelDeleteResidentBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button id="confirmDeleteResidentBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-trash mr-2"></i>Delete Permanently
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ADD: Success Modals for Residents -->
<div id="editResidentSuccessModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
        <div class="p-8 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check-circle text-green-500 text-2xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Success!</h3>
            <p class="text-gray-600 text-sm mb-6">Resident information has been updated successfully.</p>
            <button id="editResidentSuccessOkBtn" type="button" 
                    class="px-6 py-3 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                <i class="fas fa-check mr-2"></i>OK
            </button>
        </div>
    </div>
</div>

<div id="archiveResidentSuccessModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
        <div class="p-8 text-center">
            <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-archive text-orange-500 text-2xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Resident Archived!</h3>
            <p class="text-gray-600 text-sm mb-6">The resident account has been archived successfully.</p>
            <button id="archiveResidentSuccessOkBtn" type="button" 
                    class="px-6 py-3 text-sm font-medium bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors">
                <i class="fas fa-check mr-2"></i>OK
            </button>
        </div>
    </div>
</div>

<div id="restoreResidentSuccessModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
        <div class="p-8 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user-check text-green-500 text-2xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Resident Restored!</h3>
            <p class="text-gray-600 text-sm mb-6">The resident account has been restored successfully.</p>
            <button id="restoreResidentSuccessOkBtn" type="button" 
                    class="px-6 py-3 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                <i class="fas fa-check mr-2"></i>OK
            </button>
        </div>
    </div>
</div>

<div id="deleteResidentSuccessModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
        <div class="p-8 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check-circle text-red-500 text-2xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Resident Deleted!</h3>
            <p class="text-gray-600 text-sm mb-6">The resident account has been permanently deleted.</p>
            <button id="deleteResidentSuccessOkBtn" type="button" 
                    class="px-6 py-3 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                <i class="fas fa-check mr-2"></i>OK
            </button>
        </div>
    </div>
</div>

   
    <!-- Logout Confirmation Modal -->
<div id="logoutConfirmationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-orange-50 rounded-t-xl">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-sign-out-alt text-orange-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">Logout Confirmation</h3>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-circle text-orange-500 text-2xl"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2">End Session</h4>
                <p class="text-gray-600 text-sm mb-4">
                    Are you sure you want to logout? You will need to login again to access the emergency management system.
                </p>
                <div class="bg-blue-50 border border-blue-200 p-3 rounded-lg mb-4">
                    <div class="flex items-center justify-center space-x-2 text-sm text-blue-800">
                        <i class="fas fa-info-circle text-blue-600"></i>
                        <span>Your session will be securely terminated</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-center space-x-4">
                <button id="cancelLogoutBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                    <i class="fas fa-times"></i>
                    <span>Stay Logged In</span>
                </button>
                <button id="confirmLogoutBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Profile Modal - Add this after the editAdminModal -->
<div id="profileModal" class="fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 max-h-[95vh] overflow-y-auto">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-blue-50 rounded-t-xl">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-blue-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">My Profile</h3>
            </div>
            <button onclick="closeProfileModal()" class="text-gray-400 p-2 rounded-full">
                <i class="fas fa-times text-lg !transition-none !transform-none"></i>
            </button>
        </div>

        <!-- Content -->
        <div class="p-6">
            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- Left Column -->
                <div class="space-y-6">
                    
                    <!-- Profile Photo Section -->
                    <div class="flex flex-col items-center space-y-4 p-6 bg-gray-50 rounded-lg">
                        <div class="relative w-24 h-24">
                            <img id="profilePhoto" src="" alt="Profile Photo"
                                class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg hidden">
                            <div id="profileInitials" class="w-24 h-24 bg-blue-500 rounded-full flex items-center justify-center border-4 border-white shadow-lg">
                                <span class="text-white text-2xl font-bold"></span>
                            </div>
                        </div>
                        <div class="text-center">
                            <h4 id="profileFullName" class="text-xl font-bold text-gray-900"></h4>
                            <p id="profilePosition" class="text-sm text-gray-600"></p>
                        </div>
                    </div>

                    <!-- Personal Information Section -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-blue-600 text-sm"></i>
                            </div>
                            <h4 class="text-lg font-medium text-gray-900">Personal Information</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <label class="block text-sm font-medium text-gray-500 mb-1">First Name</label>
                                <p id="profileFirstName" class="text-gray-900 font-medium">-</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <label class="block text-sm font-medium text-gray-500 mb-1">Last Name</label>
                                <p id="profileLastName" class="text-gray-900 font-medium">-</p>
                            </div>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Middle Name</label>
                            <p id="profileMiddleName" class="text-gray-900 font-medium">-</p>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Birthdate</label>
                            <p id="profileBirthdate" class="text-gray-900 font-medium">-</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    
                    <!-- Account Information Section -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-key text-green-600 text-sm"></i>
                            </div>
                            <h4 class="text-lg font-medium text-gray-900">Account Information</h4>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Admin ID</label>
                            <p id="profileAdminId" class="text-gray-900 font-medium">-</p>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Email Address</label>
                            <p id="profileEmail" class="text-gray-900 font-medium">-</p>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Username</label>
                            <p id="profileUsername" class="text-gray-900 font-medium">-</p>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Role</label>
                            <p id="profileRole" class="text-gray-900 font-medium">-</p>
                        </div>
                    </div>

                    <!-- Status Information Section -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-info-circle text-purple-600 text-sm"></i>
                            </div>
                            <h4 class="text-lg font-medium text-gray-900">Status Information</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <label class="block text-sm font-medium text-gray-500 mb-1">Account Status</label>
                                <div class="flex items-center space-x-2">
                                    <div id="profileAccountStatusDot" class="w-2 h-2 rounded-full bg-green-500"></div>
                                    <p id="profileAccountStatus" class="text-gray-900 font-medium capitalize">-</p>
                                </div>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <label class="block text-sm font-medium text-gray-500 mb-1">User Status</label>
                                <div class="flex items-center space-x-2">
                                    <div id="profileUserStatusDot" class="w-2 h-2 rounded-full bg-green-500"></div>
                                    <p id="profileUserStatus" class="text-gray-900 font-medium capitalize">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Account Created</label>
                            <p id="profileAccountCreated" class="text-gray-900 font-medium">-</p>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Last Active</label>
                            <p id="profileLastActive" class="text-gray-900 font-medium">-</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Close Button -->
            <div class="flex justify-end pt-6 border-t border-gray-200">
                <button onclick="closeProfileModal()" 
                        class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                    <i class="fas fa-times"></i>
                    <span>Close</span>
                </button>
            </div>
        </div>
    </div>
</div>






    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Main Footer Content -->
            <div class="py-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Company Info -->
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

                    <!-- Quick Links -->
                    <div>
                        <h4 class="text-md font-semibold mb-4">Quick Links</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/Prototype/html/Dashboard.html" class="text-gray-300 hover:text-white transition-colors">Dashboard</a></li>
                            <li><a href="/Prototype/html/Alerts.html" class="text-gray-300 hover:text-white transition-colors">Active Alerts</a></li>
                            <li><a href="/Prototype/html/Reports.html" class="text-gray-300 hover:text-white transition-colors">Reports</a></li>
                            <li><a href="/Prototype/html/Users.html" class="text-gray-300 hover:text-white transition-colors">User Management</a></li>
                        </ul>
                    </div>

                    <!-- Services -->
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

                    <!-- Contact Info -->
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

            <!-- Bottom Footer -->
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
                
                <!-- System Status -->
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



    <script src="/ALERTPOINT/javascript/footer.js"></script>
    <script src="/ALERTPOINT/javascript/nav-bar.js"></script>
    <script src="/ALERTPOINT/javascript/USERS/Users.js"></script>
    <script src="/ALERTPOINT/javascript/settings.js"></script>

    <script src="/ALERTPOINT/javascript/USERS/Add_Admin.js"></script>
    <script src="/ALERTPOINT/javascript/USERS/Archive_Admin.js"></script>
    <script src="/ALERTPOINT/javascript/USERS/Restore_Delete_Admin.js"></script>
    <script src="/ALERTPOINT/javascript/profile.js"></script>



    <!-- <script src="/ALERTPOINT/javascript/USERS/archive_edit_admin.js"></script> -->



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



// Make PHP functions available to JavaScript
function getFullName(firstName, middleName = '', lastName = '') {
    let fullName = firstName || '';
    if (middleName && middleName.trim() !== '') {
        fullName += " " + middleName;
    }
    if (lastName && lastName.trim() !== '') {
        fullName += " " + lastName;
    }
    return fullName;
}

function getInitials(firstName, middleName = '', lastName = '') {
    let initials = '';
    
    if (firstName && firstName.trim() !== '') {
        initials += firstName.charAt(0).toUpperCase();
    }
    
    if (middleName && middleName.trim() !== '') {
        initials += middleName.charAt(0).toUpperCase();
    } else if (lastName && lastName.trim() !== '') {
        initials += lastName.charAt(0).toUpperCase();
    }
    
    return initials;
}

function normalizePicturePath(picturePath) {
    if (!picturePath || picturePath === 'NULL' || picturePath.toLowerCase() === 'null') {
        return null;
    }
    
    if (picturePath.indexOf('../../') === 0) {
        return picturePath.replace('../../', '/ALERTPOINT/');
    }
    else if (picturePath.indexOf('/ALERTPOINT/') === 0) {
        return picturePath;
    }
    else if (picturePath.indexOf('/') !== 0 && picturePath.indexOf('http') !== 0) {
        return '/ALERTPOINT/' + picturePath;
    }
    
    return picturePath;
}









document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - checking for archive success modal...');
    const modal = document.getElementById('archiveSuccessModal');
    const messageEl = document.getElementById('archiveSuccessMessage');
    const okBtn = document.getElementById('archiveSuccessOkBtn');
    
    console.log('Archive Success Modal found:', !!modal);
    console.log('Archive Success Message found:', !!messageEl);
    console.log('Archive Success OK Button found:', !!okBtn);
    
    if (!modal) {
        console.error('CRITICAL: archiveSuccessModal not found in DOM!');
    }
});

// Make sure the archived admin functions are available globally
window.restoreAdmin = restoreAdmin;
window.deleteAdmin = deleteAdmin;
</script>

</body>
</html>