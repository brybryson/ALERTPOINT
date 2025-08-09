<?php
// Create this as "test_paths.php" in your /javascript/USERS/ directory
// Run it to check what's wrong

echo "<h3>Path Diagnostics</h3>";

// Check current directory
echo "<p><strong>Current directory:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Current file:</strong> " . __FILE__ . "</p>";

// Check possible config paths
$possible_config_paths = [
    '../../config/database.php',
    '../../../config/database.php',
    '../../ALERTPOINT/config/database.php',
    '../config/database.php',
    'config/database.php',
    '../../html/config/database.php'
];

echo "<h4>Config File Check:</h4>";
foreach ($possible_config_paths as $path) {
    $full_path = realpath($path);
    $exists = file_exists($path);
    $readable = $exists ? is_readable($path) : false;
    
    echo "<p>Path: <code>$path</code><br>";
    echo "Exists: " . ($exists ? "✅ YES" : "❌ NO") . "<br>";
    echo "Full path: " . ($full_path ? $full_path : "N/A") . "<br>";
    echo "Readable: " . ($readable ? "✅ YES" : "❌ NO") . "</p>";
}

// Check uploads directory
echo "<h4>Upload Directory Check:</h4>";
$upload_dirs = [
    'uploads/admin/',
    '../uploads/admin/',
    '../../uploads/admin/',
    '../../../uploads/admin/'
];

foreach ($upload_dirs as $dir) {
    $full_path = realpath($dir);
    $exists = file_exists($dir);
    $writable = $exists ? is_writable($dir) : false;
    
    echo "<p>Path: <code>$dir</code><br>";
    echo "Exists: " . ($exists ? "✅ YES" : "❌ NO") . "<br>";
    echo "Full path: " . ($full_path ? $full_path : "N/A") . "<br>";
    echo "Writable: " . ($writable ? "✅ YES" : "❌ NO") . "</p>";
}

// Check if create_admin.php exists
echo "<h4>create_admin.php Check:</h4>";
$create_admin_exists = file_exists('create_admin.php');
$create_admin_readable = $create_admin_exists ? is_readable('create_admin.php') : false;

echo "<p>create_admin.php exists: " . ($create_admin_exists ? "✅ YES" : "❌ NO") . "</p>";
echo "<p>create_admin.php readable: " . ($create_admin_readable ? "✅ YES" : "❌ NO") . "</p>";

if ($create_admin_exists) {
    echo "<p>File size: " . filesize('create_admin.php') . " bytes</p>";
    echo "<p>Last modified: " . date('Y-m-d H:i:s', filemtime('create_admin.php')) . "</p>";
}
?>