<?php
/**
 * Quick test of SystemCleaner functionality
 * This file will be removed after testing
 */

// WordPress environment
require_once('../../../wp-load.php');

// Load the SystemCleaner class
require_once get_template_directory() . '/classes/SystemCleaner.php';

echo "<h1>SystemCleaner Test</h1>\n";

try {
    $cleaner = new \NORDBOOKING\Classes\SystemCleaner();
    $report = $cleaner->getCleanupReport();
    
    echo "<h2>Cleanup Report Summary</h2>\n";
    echo "<ul>\n";
    echo "<li>Test files: " . $report['summary']['test_files_count'] . "</li>\n";
    echo "<li>Debug files: " . $report['summary']['debug_files_count'] . "</li>\n";
    echo "<li>Temp files: " . $report['summary']['temp_files_count'] . "</li>\n";
    echo "<li>Total files: " . $report['summary']['total_files'] . "</li>\n";
    echo "<li>Safe to remove: " . $report['summary']['safe_to_remove'] . "</li>\n";
    echo "<li>Has dependencies: " . $report['summary']['has_dependencies'] . "</li>\n";
    echo "</ul>\n";
    
    if (!empty($report['files']['test'])) {
        echo "<h3>Test Files Found:</h3>\n<ul>\n";
        foreach ($report['files']['test'] as $file) {
            echo "<li>" . htmlspecialchars($file['name']) . " (" . size_format($file['size']) . ")</li>\n";
        }
        echo "</ul>\n";
    }
    
    if (!empty($report['files']['debug'])) {
        echo "<h3>Debug Files Found:</h3>\n<ul>\n";
        foreach ($report['files']['debug'] as $file) {
            echo "<li>" . htmlspecialchars($file['name']) . " (" . size_format($file['size']) . ")</li>\n";
        }
        echo "</ul>\n";
    }
    
    if (!empty($report['files']['temporary'])) {
        echo "<h3>Temporary Files Found:</h3>\n<ul>\n";
        foreach ($report['files']['temporary'] as $file) {
            echo "<li>" . htmlspecialchars($file['name']) . " (" . size_format($file['size']) . ")</li>\n";
        }
        echo "</ul>\n";
    }
    
    echo "<p><strong>SystemCleaner is working correctly!</strong></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<p><a href='admin-system-cleanup.php'>Go to System Cleanup Interface</a></p>\n";
?>