<?php
/**
 * System Cleanup Admin Interface
 * Provides a web interface for cleaning up test and debug files
 * 
 * Access: /wp-content/themes/yourtheme/admin-system-cleanup.php
 */

// WordPress environment
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Load the SystemCleaner class
require_once get_template_directory() . '/classes/SystemCleaner.php';

$cleaner = new \NORDBOOKING\Classes\SystemCleaner();
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'report';

?>
<!DOCTYPE html>
<html>
<head>
    <title>NORDBOOKING System Cleanup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f1f1f1; }
        .container { max-width: 1200px; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header { border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .danger { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .btn { display: inline-block; padding: 8px 16px; margin: 5px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn-primary { background: #007cba; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .file-list { max-height: 300px; overflow-y: auto; }
        .summary-stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { flex: 1; padding: 15px; text-align: center; border-radius: 5px; }
        .stat-number { font-size: 24px; font-weight: bold; }
        .stat-label { font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧹 NORDBOOKING System Cleanup</h1>
            <p>Clean up test files, debug scripts, and temporary development files</p>
        </div>

        <?php if ($action === 'report'): ?>
            <?php 
            $report = $cleaner->getCleanupReport();
            $summary = $report['summary'];
            ?>
            
            <div class="summary-stats">
                <div class="stat-box info">
                    <div class="stat-number"><?php echo $summary['test_files_count']; ?></div>
                    <div class="stat-label">Test Files</div>
                </div>
                <div class="stat-box warning">
                    <div class="stat-number"><?php echo $summary['debug_files_count']; ?></div>
                    <div class="stat-label">Debug Files</div>
                </div>
                <div class="stat-box danger">
                    <div class="stat-number"><?php echo $summary['temp_files_count']; ?></div>
                    <div class="stat-label">Temp Files</div>
                </div>
                <div class="stat-box success">
                    <div class="stat-number"><?php echo $summary['safe_to_remove']; ?></div>
                    <div class="stat-label">Safe to Remove</div>
                </div>
            </div>

            <?php if ($summary['has_dependencies'] > 0): ?>
            <div class="section warning">
                <h3>⚠️ Files with Dependencies</h3>
                <p><?php echo $summary['has_dependencies']; ?> files have dependencies and require manual review:</p>
                <div class="file-list">
                    <table>
                        <thead>
                            <tr><th>File</th><th>Dependencies</th><th>Type</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['validation']['dependencies_found'] as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item['file']['name']); ?></td>
                                <td><?php echo esc_html(implode(', ', $item['dependencies'])); ?></td>
                                <td><?php echo esc_html($item['file']['type']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="section success">
                <h3>✅ Files Safe to Remove</h3>
                <p><?php echo $summary['safe_to_remove']; ?> files can be safely removed:</p>
                <div class="file-list">
                    <table>
                        <thead>
                            <tr><th>File</th><th>Size</th><th>Modified</th><th>Type</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['validation']['safe_to_remove'] as $file): ?>
                            <tr>
                                <td><?php echo esc_html($file['name']); ?></td>
                                <td><?php echo size_format($file['size']); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', $file['modified']); ?></td>
                                <td><?php echo esc_html($file['type']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section">
                <h3>🎯 Actions</h3>
                <?php if ($summary['safe_to_remove'] > 0): ?>
                    <a href="?action=cleanup&backup=1" class="btn btn-success" onclick="return confirm('This will remove <?php echo $summary['safe_to_remove']; ?> files after creating backups. Continue?')">
                        🗑️ Remove Safe Files (with backup)
                    </a>
                    <a href="?action=cleanup&backup=0" class="btn btn-danger" onclick="return confirm('This will permanently remove <?php echo $summary['safe_to_remove']; ?> files WITHOUT backup. Are you sure?')">
                        ⚠️ Remove Safe Files (no backup)
                    </a>
                <?php else: ?>
                    <p class="info">No files are safe to remove automatically. Manual review required.</p>
                <?php endif; ?>
                <a href="?action=report" class="btn btn-secondary">🔄 Refresh Report</a>
            </div>

        <?php elseif ($action === 'cleanup'): ?>
            <?php
            $create_backup = isset($_GET['backup']) && $_GET['backup'] === '1';
            $results = $cleaner->performCleanup($create_backup);
            ?>
            
            <div class="section <?php echo $results['backup_created'] || !$create_backup ? 'success' : 'danger'; ?>">
                <h3>🧹 Cleanup Results</h3>
                
                <?php if ($create_backup): ?>
                    <p><strong>Backup:</strong> <?php echo $results['backup_created'] ? '✅ Created' : '❌ Failed'; ?></p>
                <?php endif; ?>
                
                <?php if (!empty($results['files_removed']['removed'])): ?>
                    <p><strong>Removed Files:</strong></p>
                    <ul>
                        <?php foreach ($results['files_removed']['removed'] as $file): ?>
                            <li>✅ <?php echo esc_html($file); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (!empty($results['files_removed']['failed'])): ?>
                    <p><strong>Failed to Remove:</strong></p>
                    <ul>
                        <?php foreach ($results['files_removed']['failed'] as $file): ?>
                            <li>❌ <?php echo esc_html($file); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (!empty($results['summary'])): ?>
                    <p><strong>Summary:</strong></p>
                    <ul>
                        <?php foreach ($results['summary'] as $message): ?>
                            <li><?php echo esc_html($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <a href="?action=report" class="btn btn-primary">📊 View New Report</a>
            </div>
        <?php endif; ?>
        
        <div class="section info">
            <h3>ℹ️ Information</h3>
            <p><strong>Backup Location:</strong> <?php echo esc_html($cleaner->getCleanupReport()['backup_dir']); ?></p>
            <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>Theme Directory:</strong> <?php echo esc_html(get_template_directory()); ?></p>
        </div>
    </div>
</body>
</html>