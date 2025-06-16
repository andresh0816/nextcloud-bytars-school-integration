<?php

/**
 * Diagnostic script for Bytars School Nextcloud App
 * Run this script from your Nextcloud installation directory to diagnose issues
 * Usage: php debug_install.php
 */

$appId = 'bytarsschool';

echo "=== Bytars School App Diagnostic ===\n\n";

// Check if we're in the right directory
if (!file_exists('occ')) {
    echo "❌ ERROR: Please run this script from your Nextcloud root directory\n";
    exit(1);
}

// Check if app directory exists
$appPath = "apps/$appId";
if (!is_dir($appPath)) {
    echo "❌ ERROR: App directory '$appPath' not found\n";
    echo "   Make sure the app is installed in the apps directory\n";
    exit(1);
}

echo "✅ App directory found: $appPath\n";

// Check required files
$requiredFiles = [
    "$appPath/appinfo/info.xml",
    "$appPath/lib/AppInfo/Application.php",
    "$appPath/lib/Settings/Admin.php",
    "$appPath/templates/admin.php",
    "$appPath/js/admin.js",
    "$appPath/css/admin.css"
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✅ Required file found: $file\n";
    } else {
        echo "❌ Missing required file: $file\n";
    }
}

// Check app info.xml
$infoXml = "$appPath/appinfo/info.xml";
if (file_exists($infoXml)) {
    $xml = simplexml_load_file($infoXml);
    if ($xml) {
        echo "\n--- App Info ---\n";
        echo "App ID: " . (string)$xml->id . "\n";
        echo "App Name: " . (string)$xml->name . "\n";
        echo "Namespace: " . (string)$xml->namespace . "\n";
        
        // Check settings registration
        if (isset($xml->settings->admin)) {
            echo "✅ Admin settings class: " . (string)$xml->settings->admin . "\n";
        } else {
            echo "❌ Admin settings not registered in info.xml\n";
        }
    }
}

echo "\n=== Installation Commands ===\n";
echo "To enable the app, run:\n";
echo "  php occ app:enable $appId\n\n";

echo "To check app status, run:\n";
echo "  php occ app:list | grep $appId\n\n";

echo "To check for errors, run:\n";
echo "  php occ app:enable $appId 2>&1\n\n";

echo "=== Troubleshooting ===\n";
echo "1. Make sure all required files are present\n";
echo "2. Run 'composer install' in the app directory if needed\n";
echo "3. Check Nextcloud logs for PHP errors\n";
echo "4. Ensure proper file permissions\n";
echo "5. Clear Nextcloud cache if needed\n";

?>
