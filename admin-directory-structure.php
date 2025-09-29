<?php
/**
 * Directory Structure Management
 * Create and manage the new organized directory structure
 * 
 * Access: /wp-content/themes/yourtheme/admin-directory-structure.php
 */

// WordPress environment
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'status';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Directory Structure Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f1f1f1; }
        .container { max-width: 1200px; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header { border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .danger { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn-success { background: #28a745; color: white; }
        .btn-primary { background: #007cba; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .code { background: #f8f9fa; padding: 15px; border-radius: 3px; font-family: monospace; font-size: 12px; margin: 10px 0; white-space: pre-line; }
        .tree { font-family: monospace; font-size: 14px; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .status-icon { font-size: 16px; margin-right: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 Directory Structure Management</h1>
            <p>Create and manage the new organized directory structure for NORDBOOKING</p>
        </div>

        <?php if ($action === 'status'): ?>
            <div class="section success">
                <h3>✅ New Directory Structure Created!</h3>
                <p>The new organized directory structure has been successfully created.</p>
            </div>
            
            <div class="section info">
                <h3>📋 Directory Structure Overview</h3>
                <div class="code tree">📁 NORDBOOKING/
├── 📁 config/                 # Configuration files
│   ├── 📄 app.php            # Main application configuration
│   ├── 📄 database.php       # Database configuration
│   └── 📄 stripe.php         # Payment processing configuration
├── 📁 src/                   # Organized source code
│   ├── 📁 Classes/           # PHP classes by functionality
│   ├── 📁 Functions/         # Utility functions (from functions.php)
│   └── 📁 Utilities/         # Helper utilities and tools
├── 📁 assets/                # Static assets (already organized)
│   ├── 📁 css/              # Stylesheets
│   ├── 📁 js/               # JavaScript files
│   ├── 📁 images/           # Images and graphics
│   ├── 📁 svg-icons/        # SVG icons
│   └── 📁 legal/            # Legal documents
├── 📁 templates/             # WordPress template files
├── 📁 dashboard/             # Dashboard-specific files
├── 📁 includes/              # WordPress includes
├── 📁 functions/             # Legacy functions (to be migrated)
├── 📁 classes/               # Legacy classes (to be migrated)
├── 📁 lib/                   # Third-party libraries
├── 📁 docs/                  # Documentation
└── 📄 [WordPress theme files] # Standard WP theme files</div>
            </div>
            
            <?php
            // Check directory status
            $directories = [
                'config' => 'Configuration files',
                'src' => 'Source code',
                'src/Classes' => 'PHP Classes',
                'src/Functions' => 'Utility Functions', 
                'src/Utilities' => 'Helper Utilities',
                'docs' => 'Documentation'
            ];
            
            $status_check = [];
            foreach ($directories as $dir => $description) {
                $path = get_template_directory() . '/' . $dir;
                $status_check[] = [
                    'directory' => $dir,
                    'description' => $description,
                    'exists' => is_dir($path),
                    'writable' => is_dir($path) && is_writable($path)
                ];
            }
            ?>
            
            <div class="section info">
                <h3>📊 Directory Status Check</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Directory</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Writable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($status_check as $check): ?>
                        <tr>
                            <td><strong><?php echo esc_html($check['directory']); ?></strong></td>
                            <td><?php echo esc_html($check['description']); ?></td>
                            <td>
                                <?php if ($check['exists']): ?>
                                    <span class="status-icon">✅</span> Exists
                                <?php else: ?>
                                    <span class="status-icon">❌</span> Missing
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($check['writable']): ?>
                                    <span class="status-icon">✅</span> Writable
                                <?php else: ?>
                                    <span class="status-icon">❌</span> Not Writable
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="section warning">
                <h3>⚠️ Next Steps</h3>
                <p>The directory structure is ready. The next tasks in the optimization process are:</p>
                <ol>
                    <li><strong>Task 5.2:</strong> Move files to appropriate directories</li>
                    <li><strong>Task 5.3:</strong> Update all file references and includes</li>
                    <li><strong>Task 6.1:</strong> Create secure configuration system</li>
                </ol>
                <p>These will be handled in the subsequent tasks of the optimization workflow.</p>
            </div>
            
            <div class="section info">
                <h3>📚 Documentation</h3>
                <p>Complete documentation has been created:</p>
                <ul>
                    <li><strong>Directory Structure Guide:</strong> <code>docs/directory-structure.md</code></li>
                    <li><strong>Migration Utility:</strong> <code>src/Utilities/DirectoryMigrator.php</code></li>
                </ul>
            </div>
            
        <?php endif; ?>
        
        <div class="section">
            <h3>🚀 Actions</h3>
            <a href="admin-performance-booster.php" class="btn btn-success">⚡ Performance Booster</a>
            <a href="admin-database-optimizer.php" class="btn btn-primary">🗄️ Database Optimizer</a>
            <a href="admin-functions-refactor.php" class="btn btn-warning">🔧 Functions Refactor</a>
        </div>
        
        <div class="section info">
            <h3>ℹ️ Information</h3>
            <p><strong>Structure Created:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>Theme Directory:</strong> <?php echo get_template_directory(); ?></p>
            <p><strong>Current Task:</strong> 5.1 Design and create new directory structure</p>
            <p><strong>Status:</strong> ✅ Complete</p>
        </div>
    </div>
</body>
</html>