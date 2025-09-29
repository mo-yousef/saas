<?php
/**
 * Functions.php Refactoring Admin Interface
 * Break down the massive functions.php file for better performance
 * 
 * Access: /wp-content/themes/yourtheme/admin-functions-refactor.php
 */

// WordPress environment
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Load the FunctionsRefactor class
require_once get_template_directory() . '/classes/FunctionsRefactor.php';

$refactor = new \NORDBOOKING\Classes\FunctionsRefactor();
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'analyze';

?>
<!DOCTYPE html>
<html>
<head>
    <title>NORDBOOKING Functions Refactor</title>
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
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .metric-card { padding: 15px; border-radius: 5px; text-align: center; }
        .metric-value { font-size: 24px; font-weight: bold; }
        .metric-label { font-size: 14px; color: #666; margin-top: 5px; }
        .progress-bar { width: 100%; height: 20px; background: #f8f9fa; border-radius: 10px; overflow: hidden; margin: 10px 0; }
        .progress-fill { height: 100%; background: #28a745; transition: width 0.3s ease; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚡ NORDBOOKING Functions Refactor</h1>
            <p>Break down the massive functions.php file for better performance and organization</p>
        </div>

        <?php if ($action === 'analyze'): ?>
            <?php 
            $analysis = $refactor->analyzeFunctionsFile();
            $recommendations = $refactor->getRefactoringRecommendations();
            ?>
            
            <div class="section <?php echo $analysis['total_lines'] > 2000 ? 'danger' : ($analysis['total_lines'] > 1000 ? 'warning' : 'success'); ?>">
                <h3>📊 Current Functions.php Analysis</h3>
                
                <div class="metrics-grid">
                    <div class="metric-card <?php echo $analysis['total_lines'] > 2000 ? 'danger' : 'warning'; ?>">
                        <div class="metric-value"><?php echo number_format($analysis['total_lines']); ?></div>
                        <div class="metric-label">Total Lines</div>
                    </div>
                    
                    <div class="metric-card info">
                        <div class="metric-value"><?php echo size_format($analysis['file_size']); ?></div>
                        <div class="metric-label">File Size</div>
                    </div>
                    
                    <div class="metric-card warning">
                        <div class="metric-value"><?php echo count($analysis['functions']); ?></div>
                        <div class="metric-label">Functions</div>
                    </div>
                    
                    <div class="metric-card warning">
                        <div class="metric-value"><?php echo count($analysis['hooks']); ?></div>
                        <div class="metric-label">Hooks</div>
                    </div>
                </div>
            </div>

            <div class="section info">
                <h3>📁 File Sections</h3>
                <table>
                    <thead>
                        <tr><th>Section</th><th>Lines</th><th>Start Line</th><th>End Line</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis['sections'] as $name => $data): ?>
                        <tr>
                            <td><strong><?php echo ucwords(str_replace('_', ' ', $name)); ?></strong></td>
                            <td><?php echo $data['line_count']; ?></td>
                            <td><?php echo $data['start_line']; ?></td>
                            <td><?php echo $data['end_line']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($analysis['recommendations'])): ?>
            <div class="section warning">
                <h3>⚠️ Issues Identified</h3>
                <ul>
                    <?php foreach ($analysis['recommendations'] as $rec): ?>
                    <li><?php echo esc_html($rec); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="section success">
                <h3>🎯 Refactoring Benefits</h3>
                <div class="metrics-grid">
                    <div class="metric-card success">
                        <div class="metric-value"><?php echo $recommendations['estimated_improvement']['load_time_reduction']; ?></div>
                        <div class="metric-label">Load Time Reduction</div>
                    </div>
                    
                    <div class="metric-card success">
                        <div class="metric-value"><?php echo $recommendations['estimated_improvement']['memory_usage_reduction']; ?></div>
                        <div class="metric-label">Memory Reduction</div>
                    </div>
                    
                    <div class="metric-card success">
                        <div class="metric-value"><?php echo $recommendations['estimated_improvement']['maintainability_improvement']; ?></div>
                        <div class="metric-label">Maintainability</div>
                    </div>
                </div>
                
                <h4>Benefits:</h4>
                <ul>
                    <?php foreach ($recommendations['benefits'] as $benefit): ?>
                    <li><?php echo esc_html($benefit); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="section">
                <h3>🚀 Actions</h3>
                <a href="?action=refactor" class="btn btn-success" onclick="return confirm('This will refactor functions.php into organized files. A backup will be created. Continue?')">
                    ⚡ Refactor Functions.php
                </a>
                <a href="?action=analyze" class="btn btn-primary">🔄 Refresh Analysis</a>
            </div>

        <?php elseif ($action === 'refactor'): ?>
            <?php
            $start_time = microtime(true);
            $results = $refactor->refactorFunctionsFile();
            $execution_time = microtime(true) - $start_time;
            ?>
            
            <div class="section <?php echo empty($results['errors']) ? 'success' : 'warning'; ?>">
                <h3>⚡ Refactoring Results</h3>
                <p><strong>Execution Time:</strong> <?php echo round($execution_time, 3); ?> seconds</p>
                
                <?php if ($results['backup_created']): ?>
                <div class="success">
                    <p>✅ <strong>Backup Created:</strong> <?php echo basename($results['backup_created']); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($results['new_functions_php'])): ?>
                <div class="success">
                    <h4>📄 New Functions.php</h4>
                    <div class="metrics-grid">
                        <div class="metric-card success">
                            <div class="metric-value"><?php echo $results['new_functions_php']['lines_before']; ?> → <?php echo $results['new_functions_php']['lines_after']; ?></div>
                            <div class="metric-label">Lines Reduced</div>
                        </div>
                        
                        <div class="metric-card success">
                            <div class="metric-value"><?php echo size_format($results['new_functions_php']['size_before']); ?> → <?php echo size_format($results['new_functions_php']['size_after']); ?></div>
                            <div class="metric-label">Size Reduced</div>
                        </div>
                        
                        <div class="metric-card success">
                            <div class="metric-value"><?php echo $results['new_functions_php']['reduction_percentage']; ?>%</div>
                            <div class="metric-label">Size Reduction</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($results['created_files'])): ?>
                <div class="info">
                    <h4>📁 Created Files (<?php echo count($results['created_files']); ?>)</h4>
                    <table>
                        <thead>
                            <tr><th>Section</th><th>Filename</th><th>Lines</th><th>Size</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['created_files'] as $file): ?>
                            <tr>
                                <td><?php echo ucwords(str_replace('_', ' ', $file['section'])); ?></td>
                                <td><code><?php echo esc_html($file['filename']); ?></code></td>
                                <td><?php echo $file['lines']; ?></td>
                                <td><?php echo size_format($file['size']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($results['errors'])): ?>
                <div class="danger">
                    <h4>❌ Errors</h4>
                    <ul>
                        <?php foreach ($results['errors'] as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="section success">
                <h3>🎉 Refactoring Complete!</h3>
                <p>Your functions.php file has been successfully refactored. The system should now load much faster!</p>
                
                <h4>Next Steps:</h4>
                <ol>
                    <li>Test your website to ensure everything works correctly</li>
                    <li>Monitor performance improvements in the dashboard</li>
                    <li>If issues occur, restore from backup: <code><?php echo basename($results['backup_created']); ?></code></li>
                </ol>
            </div>
            
            <div class="section">
                <a href="?action=analyze" class="btn btn-primary">📊 View New Analysis</a>
                <a href="admin-performance-analysis.php" class="btn btn-success">📈 Check Performance</a>
            </div>
        <?php endif; ?>
        
        <div class="section info">
            <h3>ℹ️ Information</h3>
            <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>Functions Directory:</strong> <?php echo get_template_directory() . '/functions/'; ?></p>
            <p><strong>Backup Location:</strong> Same directory as functions.php</p>
        </div>
    </div>
</body>
</html>