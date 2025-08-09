<?php
// Fix permissions script for XAMPP
// Run this script once to fix the permissions

$upload_dir = __DIR__ . '/uploads/admin/';

echo "<h2>Fixing Upload Directory Permissions</h2>";
echo "<p><strong>Directory:</strong> " . $upload_dir . "</p>";

// Try to change permissions
if (chmod($upload_dir, 0777)) {
    echo "<p>✅ Permissions changed to 0777</p>";
} else {
    echo "<p>❌ Failed to change permissions via PHP</p>";
    echo "<p>Please run in Terminal:</p>";
    echo "<code>sudo chmod 777 " . $upload_dir . "</code>";
}

// Test if it's now writable
if (is_writable($upload_dir)) {
    echo "<p>✅ Directory is now writable!</p>";
    
    // Test file creation
    $test_file = $upload_dir . 'test_write.txt';
    if (file_put_contents($test_file, 'Test successful at ' . date('Y-m-d H:i:s'))) {
        echo "<p>✅ Test file write successful</p>";
        unlink($test_file); // Clean up
    }
} else {
    echo "<p>❌ Directory is still not writable</p>";
    echo "<p><strong>Manual steps needed:</strong></p>";
    echo "<ol>";
    echo "<li>Open Terminal</li>";
    echo "<li>Run: <code>sudo chmod 777 " . $upload_dir . "</code></li>";
    echo "<li>Enter your Mac password when prompted</li>";
    echo "<li>Refresh this page to test again</li>";
    echo "</ol>";
}

// Show current status
clearstatcache(); // Clear file status cache
$perms = fileperms($upload_dir);
echo "<p><strong>Current permissions:</strong> " . substr(sprintf('%o', $perms), -4) . "</p>";
?>