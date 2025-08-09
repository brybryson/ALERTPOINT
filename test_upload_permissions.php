<?php
// Test script to check upload directory permissions
// Place this in your ALERTPOINT folder and run it via browser

$upload_dir = __DIR__ . '/uploads/admin/';

echo "<h2>Upload Directory Test</h2>";
echo "<p><strong>Testing directory:</strong> " . $upload_dir . "</p>";

// Check if directory exists
if (is_dir($upload_dir)) {
    echo "<p>✅ Directory exists</p>";
} else {
    echo "<p>❌ Directory does not exist</p>";
    echo "<p>Attempting to create directory...</p>";
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p>✅ Directory created successfully</p>";
    } else {
        echo "<p>❌ Failed to create directory</p>";
    }
}

// Check if directory is writable
if (is_writable($upload_dir)) {
    echo "<p>✅ Directory is writable</p>";
} else {
    echo "<p>❌ Directory is not writable</p>";
    echo "<p>Try running: chmod 755 " . $upload_dir . "</p>";
}

// Test file creation
$test_file = $upload_dir . 'test.txt';
$test_content = "This is a test file created at " . date('Y-m-d H:i:s');

if (file_put_contents($test_file, $test_content)) {
    echo "<p>✅ Test file created successfully</p>";
    
    // Clean up test file
    if (unlink($test_file)) {
        echo "<p>✅ Test file deleted successfully</p>";
    }
} else {
    echo "<p>❌ Failed to create test file</p>";
}

// Show directory permissions
$perms = fileperms($upload_dir);
echo "<p><strong>Directory permissions:</strong> " . substr(sprintf('%o', $perms), -4) . "</p>";

// Show directory owner
$owner = fileowner($upload_dir);
$group = filegroup($upload_dir);
echo "<p><strong>Directory owner/group:</strong> " . $owner . "/" . $group . "</p>";

// Show current user
echo "<p><strong>Current PHP user:</strong> " . get_current_user() . "</p>";
echo "<p><strong>Web server user:</strong> " . $_SERVER['USER'] ?? 'Unknown' . "</p>";
?>