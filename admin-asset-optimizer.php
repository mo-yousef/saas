<?php
/**
 * Asset Optimization Admin Interface
 * Analyze and optimize CSS/JS files for better performance
 * 
 * Access: /wp-content/themes/yourtheme/admin-asset-optimizer.php
 */

// WordPress environment
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Load the AssetOptimizer class
require_once get_template_directory() . '/classes/AssetOptimizer.php';

$optimizer = new \NORDBOOKING\Classes\AssetOptimizer();
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'analyze';

?>
<!DOCTYPE html>
<html>
<head>
    <title>NORDBOOKING Asset Optimizer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f1f1f1; }
        .container { max-width: 1400px; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
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
        .file-list { max-height: 400px; overflow-y: auto; }
        .nav-tabs { display: flex; border-bottom: 1px solid #ddd; margin-bottom: 20px; }
        .nav-tab { padding: 10px 20px; background: #f8f9fa; border: 1px solid #ddd; border-bottom: none; margin-right: 5px; text-decoration: none; color: #333; }
        .nav-tab.active { background: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .group-box { border: 1px solid #ddd; margin: 10px 0; padding: 10px; border-radius: 5px; }
        .group-header { font-weight: bold; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚡ NORDBOOKING Asset Optimizer</h1>
            <p>Analyze and optimize CSS/JS files for better performance</p>
        </div>

        <div class="nav-tabs">
            <a href="#analysis" class="nav-tab active" onclick="showTab('analysis')">📊 Analysis</a>
            <a href="#consolidation" class="nav-tab" onclick="showTab('consolidation')">📦 Consolidation</a>
            <a href="#performance" class="nav-tab" onclick="showTab('performance')">🚀 Performance</a>
            <a href="#strategy" class="nav-tab" onclick="showTab('strategy')">💡 Strategy</a>
        </div>

        <?php
        $report = $optimizer->getOptimizationReport();
        $css_analysis = $report['css_analysis'];
        $js_analysis = $report['js_analysis'];
        $performance = $report['performance_impact'];
        ?>

        <!-- Analysis Tab -->
        <div id="analysis-tab" class="tab-content active">
            <div class="metrics-grid">
                <div class="metric-card <?php echo $css_analysis['total_files'] > 20 ? 'danger' : ($css_analysis['total_files'] > 10 ? 'warning' : 'success'); ?>">
                    <div class="metric-value"><?php echo $css_analysis['total_files']; ?></div>
                    <div class="metric-label">CSS Files</div>
                </div>
                
                <div class="metric-card <?php echo $css_analysis['total_size'] > 500*1024 ? 'danger' : ($css_analysis['total_size'] > 200*1024 ? 'warning' : 'success'); ?>">
                    <div class="metric-value"><?php echo size_format($css_analysis['total_size']); ?></div>
                    <div class="metric-label">Total CSS Size</div>
                </div>
                
                <div class="metric-card <?php echo $js_analysis['total_files'] > 10 ? 'warning' : 'success'; ?>">
                    <div class="metric-value"><?php echo $js_analysis['total_files']; ?></div>
                    <div class="metric-label">JS Files</div>
                </div>
                
                <div class="metric-card <?php echo count($css_analysis['duplicates']) > 0 ? 'warning' : 'success'; ?>">
                    <div class="metric-value"><?php echo count($css_analysis['duplicates']); ?></div>
                    <div class="metric-label">Duplicate Files</div>
                </div>
            </div>

            <div class="section">
                <h3>📁 CSS Files Analysis</h3>
                <div class="file-list">
                    <table>
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Size</th>
                                <th>Lines</th>
                                <th>Selectors</th>
                                <th>Last Modified</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($css_analysis['files'] as $filename => $data): ?>
                            <tr>
                                <td><code><?php echo esc_html($filename); ?></code></td>
                                <td><?php echo $data['size_formatted']; ?></td>
                                <td><?php echo $data['lines']; ?></td>
                                <td><?php echo $data['selectors']; ?></td>
                                <td><?php echo date('Y-m-d', $data['last_modified']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (!empty($css_analysis['duplicates'])): ?>
            <div class="section warning">
                <h3>⚠️ Duplicate Files</h3>
                <table>
                    <thead>
                        <tr><th>Original</th><th>Duplicate</th><th>Size</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($css_analysis['duplicates'] as $dup): ?>
                        <tr>
                            <td><?php echo esc_html($dup['original']); ?></td>
                            <td><?php echo esc_html($dup['duplicate']); ?></td>
                            <td><?php echo size_format($dup['size']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if (!empty($css_analysis['unused_files'])): ?>
            <div class="section info">
                <h3>🗑️ Potentially Unused Files</h3>
                <ul>
                    <?php foreach ($css_analysis['unused_files'] as $file): ?>
                    <li><code><?php echo esc_html($file); ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($css_analysis['recommendations'])): ?>
            <div class="section warning">
                <h3>💡 Recommendations</h3>
                <ul>
                    <?php foreach ($css_analysis['recommendations'] as $rec): ?>
                    <li><?php echo esc_html($rec); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- Consolidation Tab -->
        <div id="consolidation-tab" class="tab-content">
            <h3>📦 CSS Consolidation Groups</h3>
            <p>Files can be grouped and consolidated to reduce HTTP requests:</p>
            
            <?php foreach ($css_analysis['consolidation_groups'] as $group_name => $files): ?>
            <div class="group-box">
                <div class="group-header"><?php echo ucfirst($group_name); ?> Group (<?php echo count($files); ?> files)</div>
                <ul>
                    <?php foreach ($files as $file): ?>
                    <li><code><?php echo esc_html($file); ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>

            <?php if ($action === 'consolidate'): ?>
                <?php
                $consolidation_results = $optimizer->consolidateCSS($css_analysis['consolidation_groups'], true);
                ?>
                
                <div class="section <?php echo empty($consolidation_results['errors']) ? 'success' : 'warning'; ?>">
                    <h3>🎯 Consolidation Results</h3>
                    
                    <div class="metrics-grid">
                        <div class="metric-card success">
                            <div class="metric-value"><?php echo $consolidation_results['original_count']; ?> → <?php echo $consolidation_results['new_count']; ?></div>
                            <div class="metric-label">Files Reduced</div>
                        </div>
                        <div class="metric-card success">
                            <div class="metric-value"><?php echo size_format($consolidation_results['size_before']); ?> → <?php echo size_format($consolidation_results['size_after']); ?></div>
                            <div class="metric-label">Size Change</div>
                        </div>
                        <div class="metric-value"><?php echo round((1 - $consolidation_results['size_after'] / $consolidation_results['size_before']) * 100, 1); ?>%</div>
                        <div class="metric-label">Size Reduction</div>
                    </div>
                    
                    <?php if (!empty($consolidation_results['consolidated_files'])): ?>
                    <h4>✅ Created Files:</h4>
                    <table>
                        <thead>
                            <tr><th>Group</th><th>New File</th><th>Original Files</th><th>Compression</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consolidation_results['consolidated_files'] as $file): ?>
                            <tr>
                                <td><?php echo esc_html($file['group']); ?></td>
                                <td><code><?php echo esc_html($file['filename']); ?></code></td>
                                <td><?php echo count($file['original_files']); ?> files</td>
                                <td><?php echo $file['compression_ratio']; ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                    
                    <?php if (!empty($consolidation_results['errors'])): ?>
                    <div class="danger">
                        <h4>❌ Errors:</h4>
                        <ul>
                            <?php foreach ($consolidation_results['errors'] as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="section">
                    <a href="?action=consolidate" class="btn btn-success" onclick="return confirm('This will create consolidated CSS files. Continue?')">
                        📦 Consolidate CSS Files
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Performance Tab -->
        <div id="performance-tab" class="tab-content">
            <h3>🚀 Performance Impact Analysis</h3>
            
            <div class="metrics-grid">
                <div class="metric-card info">
                    <div class="metric-value"><?php echo $performance['current_http_requests']; ?> → <?php echo $performance['optimized_http_requests']; ?></div>
                    <div class="metric-label">HTTP Requests</div>
                </div>
                
                <div class="metric-card success">
                    <div class="metric-value">-<?php echo $performance['request_reduction']; ?></div>
                    <div class="metric-label">Requests Saved</div>
                </div>
                
                <div class="metric-card success">
                    <div class="metric-value"><?php echo $performance['estimated_load_time_improvement']; ?>ms</div>
                    <div class="metric-label">Load Time Improvement</div>
                </div>
                
                <div class="metric-card success">
                    <div class="metric-value"><?php echo size_format($performance['bandwidth_savings']); ?></div>
                    <div class="metric-label">Bandwidth Savings</div>
                </div>
            </div>

            <div class="section info">
                <h4>📊 Current vs Optimized</h4>
                <table>
                    <tr>
                        <td><strong>Current CSS Size:</strong></td>
                        <td><?php echo size_format($performance['current_css_size']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Estimated Minified Size:</strong></td>
                        <td><?php echo size_format($performance['estimated_minified_size']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>HTTP Requests:</strong></td>
                        <td><?php echo $performance['current_http_requests']; ?> → <?php echo $performance['optimized_http_requests']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Estimated Load Time Improvement:</strong></td>
                        <td><?php echo $performance['estimated_load_time_improvement']; ?>ms</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Strategy Tab -->
        <div id="strategy-tab" class="tab-content">
            <h3>💡 Loading Strategy Recommendations</h3>
            
            <?php $strategy = $report['loading_strategy']; ?>
            
            <div class="section info">
                <h4>🎯 Critical CSS (Load Inline)</h4>
                <ul>
                    <?php foreach ($strategy['critical_css'] as $file): ?>
                    <li><code><?php echo esc_html($file); ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="section warning">
                <h4>⏳ Deferred CSS (Load After Page Load)</h4>
                <ul>
                    <?php foreach ($strategy['deferred_css'] as $file): ?>
                    <li><code><?php echo esc_html($file); ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="section success">
                <h4>🔀 Conditional CSS (Load Based on Page Type)</h4>
                <?php foreach ($strategy['conditional_css'] as $condition => $files): ?>
                <div class="group-box">
                    <div class="group-header"><?php echo ucfirst($condition); ?> Pages</div>
                    <ul>
                        <?php foreach ($files as $file): ?>
                        <li><code><?php echo esc_html($file); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="section info">
                <h4>📋 Implementation Recommendations</h4>
                <ul>
                    <?php foreach ($strategy['recommendations'] as $rec): ?>
                    <li><?php echo esc_html($rec); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="section">
            <a href="?action=analyze" class="btn btn-primary">🔄 Refresh Analysis</a>
            <a href="admin-performance-analysis.php" class="btn btn-success">📊 Performance Analysis</a>
            <a href="admin-database-optimizer.php" class="btn btn-warning">🗄️ Database Optimizer</a>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
    </script>
</body>
</html>