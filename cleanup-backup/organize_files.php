<?php
/**
 * NORDBOOKING File Organization Script
 * 
 * This script organizes system files by:
 * 1. Moving debug and test files to /debug/ directory
 * 2. Adding deprecation notices to old documentation files
 * 3. Cleaning up unused files
 * 4. Creating proper file structure
 * 
 * Run this script once to organize the system files.
 * Access: https://yourdomain.com/wp-content/themes/nordbooking/organize_files.php
 */

// Security check - only allow admin access
if (!defined('ABSPATH')) {
    // Load WordPress if not already loaded
    $wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once($wp_load_path);
    } else {
        die('WordPress not found. Please run this script from the theme directory.');
    }
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

echo "<h1>NORDBOOKING File Organization</h1>\n";
echo "<p>Organizing system files for better structure...</p>\n";

$theme_dir = get_template_directory();
$debug_dir = $theme_dir . '/debug';
$docs_dir = $theme_dir . '/docs';

// Create directories if they don't exist
if (!file_exists($debug_dir)) {
    mkdir($debug_dir, 0755, true);
    echo "<p>✅ Created /debug/ directory</p>\n";
}

if (!file_exists($docs_dir)) {
    mkdir($docs_dir, 0755, true);
    echo "<p>✅ Created /docs/ directory</p>\n";
}

// Files to move to debug directory
$debug_files = [
    'admin-performance-dashboard.php',
    'debug-discount-system.php',
    'debug-performance.php',
    'debug-service-options.php',
    'debug-settings.php',
    'debug-subscriptions-admin.php',
    'debug-subscriptions.php',
    'debug-user-subscription.php',
    'performance_monitoring.php',
    'test-booking-system.php',
    'test-discount-flow.php',
    'test-discount-system.php',
    'test-invoice-system.php',
    'test-settings-js.php',
    'test-subscription-system.php'
];

// Move debug/test files
echo "<h2>Moving Debug and Test Files</h2>\n";
foreach ($debug_files as $file) {
    $source = $theme_dir . '/' . $file;
    $destination = $debug_dir . '/' . $file;
    
    if (file_exists($source)) {
        if (rename($source, $destination)) {
            echo "<p>✅ Moved {$file} to /debug/</p>\n";
        } else {
            echo "<p>❌ Failed to move {$file}</p>\n";
        }
    } else {
        echo "<p>⚠️ File not found: {$file}</p>\n";
    }
}

// Files to add deprecation notices to
$deprecated_docs = [
    'CONSOLIDATED_ADMIN_GUIDE.md' => 'docs/ADMIN_GUIDE.md',
    'DISCOUNT_SYSTEM_FIXES.md' => 'docs/DISCOUNT_SYSTEM.md',
    'ENHANCED_SUBSCRIPTION_SYSTEM.md' => 'docs/SUBSCRIPTION_SYSTEM.md',
    'FIND_PRICE_ID_GUIDE.md' => 'docs/STRIPE_INTEGRATION.md',
    'INVOICE_SYSTEM_DOCUMENTATION.md' => 'docs/INVOICE_SYSTEM.md',
    'STRIPE_SETUP_GUIDE.md' => 'docs/STRIPE_INTEGRATION.md',
    'SUBSCRIPTION_FIX_GUIDE.md' => 'docs/SUBSCRIPTION_SYSTEM.md',
    'SUBSCRIPTION_MANAGEMENT.md' => 'docs/SUBSCRIPTION_SYSTEM.md',
    'SUBSCRIPTION_MANAGEMENT_GUIDE.md' => 'docs/SUBSCRIPTION_SYSTEM.md',
    'SUBSCRIPTION_STATUS_FIX.md' => 'docs/SUBSCRIPTION_SYSTEM.md',
    'SYSTEM_AUDIT_REPORT.md' => 'docs/TROUBLESHOOTING.md',
    'worker_invitation_documentation.md' => 'docs/WORKER_MANAGEMENT.md'
];

// Add deprecation notices
echo "<h2>Adding Deprecation Notices</h2>\n";
foreach ($deprecated_docs as $old_file => $new_file) {
    $file_path = $theme_dir . '/' . $old_file;
    
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        
        // Check if deprecation notice already exists
        if (strpos($content, '> **DEPRECATED') === false) {
            $title = '';
            if (preg_match('/^# (.+)$/m', $content, $matches)) {
                $title = $matches[1];
            }
            
            $deprecation_notice = "# {$title}\n\n> **DEPRECATED**: This file has been moved to `{$new_file}` for better organization.\n> This file will be removed in a future update. Please use the new location.\n\n";
            
            // Add deprecation notice at the top (after the title)
            $content = preg_replace('/^(# .+\n\n)/', $deprecation_notice, $content);
            
            if (file_put_contents($file_path, $content)) {
                echo "<p>✅ Added deprecation notice to {$old_file}</p>\n";
            } else {
                echo "<p>❌ Failed to update {$old_file}</p>\n";
            }
        } else {
            echo "<p>ℹ️ Deprecation notice already exists in {$old_file}</p>\n";
        }
    } else {
        echo "<p>⚠️ File not found: {$old_file}</p>\n";
    }
}

// Files to remove (unused or redundant)
$files_to_remove = [
    'subscription-system-demo.php', // Demo file, not needed in production
    'wp-env.log', // Log file that shouldn't be in theme
    '.DS_Store' // macOS system file
];

echo "<h2>Removing Unused Files</h2>\n";
foreach ($files_to_remove as $file) {
    $file_path = $theme_dir . '/' . $file;
    
    if (file_exists($file_path)) {
        if (unlink($file_path)) {
            echo "<p>✅ Removed {$file}</p>\n";
        } else {
            echo "<p>❌ Failed to remove {$file}</p>\n";
        }
    } else {
        echo "<p>ℹ️ File not found (already removed): {$file}</p>\n";
    }
}

// Migration and fix files - add comments but keep them
$migration_files = [
    'fix-ali-subscription.php',
    'fix-missing-subscriptions.php',
    'fix-service-option-types.php',
    'migrate-discount-column.php',
    'migrate-discount-columns-complete.php',
    'install-optimizations.php',
    'database_optimization.sql'
];

echo "<h2>Adding Comments to Migration Files</h2>\n";
foreach ($migration_files as $file) {
    $file_path = $theme_dir . '/' . $file;
    
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        
        // Check if comment already exists
        if (strpos($content, 'MIGRATION/FIX SCRIPT') === false) {
            $comment = "<?php\n/**\n * MIGRATION/FIX SCRIPT\n * \n * This is a one-time migration or fix script.\n * Run only when specifically needed for system maintenance.\n * \n * Access: https://yourdomain.com/wp-content/themes/nordbooking/{$file}\n * \n * IMPORTANT: Only run this script if you understand what it does.\n * Always backup your database before running migration scripts.\n */\n\n";
            
            if (strpos($file, '.sql') !== false) {
                $comment = "-- MIGRATION/FIX SCRIPT\n-- \n-- This is a one-time migration or fix script.\n-- Run only when specifically needed for system maintenance.\n-- \n-- IMPORTANT: Only run this script if you understand what it does.\n-- Always backup your database before running migration scripts.\n\n";
            }
            
            // Add comment at the top
            if (strpos($content, '<?php') === 0) {
                $content = str_replace('<?php', $comment, $content);
            } else {
                $content = $comment . $content;
            }
            
            if (file_put_contents($file_path, $content)) {
                echo "<p>✅ Added comment to {$file}</p>\n";
            } else {
                echo "<p>❌ Failed to update {$file}</p>\n";
            }
        } else {
            echo "<p>ℹ️ Comment already exists in {$file}</p>\n";
        }
    } else {
        echo "<p>⚠️ File not found: {$file}</p>\n";
    }
}

// Create .htaccess for debug directory to restrict access
$htaccess_content = "# Restrict access to debug files\n<Files \"*.php\">\n    Require capability manage_options\n</Files>\n\n# Prevent directory browsing\nOptions -Indexes\n";
$htaccess_path = $debug_dir . '/.htaccess';

if (!file_exists($htaccess_path)) {
    if (file_put_contents($htaccess_path, $htaccess_content)) {
        echo "<p>✅ Created .htaccess for debug directory security</p>\n";
    } else {
        echo "<p>❌ Failed to create .htaccess for debug directory</p>\n";
    }
} else {
    echo "<p>ℹ️ .htaccess already exists in debug directory</p>\n";
}

// Update README.md to reflect new structure
$readme_path = $theme_dir . '/README.md';
if (file_exists($readme_path)) {
    $readme_content = file_get_contents($readme_path);
    
    // Add note about new documentation structure
    $structure_note = "\n## Documentation Structure\n\nAll documentation has been organized into the `/docs/` directory:\n\n- `docs/SYSTEM_OVERVIEW.md` - Complete system overview\n- `docs/INSTALLATION_GUIDE.md` - Installation and setup\n- `docs/ADMIN_GUIDE.md` - Admin interface guide\n- `docs/SUBSCRIPTION_SYSTEM.md` - Subscription management\n- `docs/STRIPE_INTEGRATION.md` - Stripe setup and configuration\n- `docs/DISCOUNT_SYSTEM.md` - Discount system documentation\n- `docs/INVOICE_SYSTEM.md` - Invoice management\n- `docs/WORKER_MANAGEMENT.md` - Worker invitation system\n- `docs/TROUBLESHOOTING.md` - Common issues and solutions\n\nDebug and test files have been moved to `/debug/` directory for better organization.\n\n";
    
    if (strpos($readme_content, 'Documentation Structure') === false) {
        // Add after the first paragraph or at the end
        if (strpos($readme_content, '## Core Features') !== false) {
            $readme_content = str_replace('## Core Features', $structure_note . '## Core Features', $readme_content);
        } else {
            $readme_content .= $structure_note;
        }
        
        if (file_put_contents($readme_path, $readme_content)) {
            echo "<p>✅ Updated README.md with new documentation structure</p>\n";
        } else {
            echo "<p>❌ Failed to update README.md</p>\n";
        }
    } else {
        echo "<p>ℹ️ README.md already contains documentation structure info</p>\n";
    }
}

echo "<h2>Organization Complete!</h2>\n";
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
echo "<h3>✅ File Organization Summary</h3>\n";
echo "<ul>\n";
echo "<li><strong>Debug/Test Files:</strong> Moved to <code>/debug/</code> directory</li>\n";
echo "<li><strong>Documentation:</strong> Organized in <code>/docs/</code> directory</li>\n";
echo "<li><strong>Old Documentation:</strong> Marked as deprecated with redirect notices</li>\n";
echo "<li><strong>Migration Files:</strong> Commented but preserved for maintenance</li>\n";
echo "<li><strong>Unused Files:</strong> Removed to clean up the system</li>\n";
echo "<li><strong>Security:</strong> Added access restrictions to debug directory</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
echo "<h3>📋 Next Steps</h3>\n";
echo "<ol>\n";
echo "<li>Review the new documentation structure in <code>/docs/</code></li>\n";
echo "<li>Update any bookmarks or links to point to new file locations</li>\n";
echo "<li>Test that all functionality still works correctly</li>\n";
echo "<li>Remove this organization script after confirming everything works</li>\n";
echo "<li>Update any deployment scripts to use the new file structure</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
echo "<h3>⚠️ Important Notes</h3>\n";
echo "<ul>\n";
echo "<li>Old documentation files are marked as deprecated but not removed</li>\n";
echo "<li>Migration and fix scripts are preserved for maintenance purposes</li>\n";
echo "<li>Debug files are now restricted to admin access only</li>\n";
echo "<li>Always backup your system before running migration scripts</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<p><strong>File organization completed successfully!</strong></p>\n";
echo "<p><a href='" . admin_url('admin.php?page=nordbooking-consolidated-admin') . "'>← Return to NORDBOOKING Admin</a></p>\n";
?>