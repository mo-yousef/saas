<?php
/**
 * Performance Analysis Admin Interface
 * Provides detailed performance metrics and optimization recommendations
 * 
 * Access: /wp-content/themes/yourtheme/admin-performance-analysis.php
 */

// WordPress environment
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Load the PerformanceAnalyzer class
require_once get_template_directory() . '/classes/PerformanceAnalyzer.php';

$analyzer = new \NORDBOOKING\Classes\PerformanceAnalyzer();
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'dashboard';

?>
<!DOCTYPE html>
<html>
<head>
    <title>NORDBOOKING Performance Analysis</title>
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
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .metric-card { padding: 15px; border-radius: 5px; text-align: center; }
        .metric-value { font-size: 24px; font-weight: bold; }
        .metric-label { font-size: 14px; color: #666; margin-top: 5px; }
        .query-list { max-height: 400px; overflow-y: auto; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px; }
        .nav-tabs { display: flex; border-bottom: 1px solid #ddd; margin-bottom: 20px; }
        .nav-tab { padding: 10px 20px; background: #f8f9fa; border: 1px solid #ddd; border-bottom: none; margin-right: 5px; text-decoration: none; color: #333; }
        .nav-tab.active { background: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚡ NORDBOOKING Performance Analysis</h1>
            <p>Analyze system performance and get optimization recommendations</p>
        </div>

        <div class="nav-tabs">
            <a href="#dashboard" class="nav-tab active" onclick="showTab('dashboard')">📊 Dashboard</a>
            <a href="#database" class="nav-tab" onclick="showTab('database')">🗄️ Database</a>
            <a href="#queries" class="nav-tab" onclick="showTab('queries')">🔍 Queries</a>
            <a href="#recommendations" class="nav-tab" onclick="showTab('recommendations')">💡 Recommendations</a>
        </div>

        <?php
        // Generate performance report
        $report = $analyzer->generatePerformanceReport();
        $dashboard_perf = $report['dashboard_performance'];
        $table_analysis = $report['table_analysis'];
        ?>

        <!-- Dashboard Tab -->
        <div id="dashboard-tab" class="tab-content active">
            <div class="metrics-grid">
                <div class="metric-card <?php echo $dashboard_perf['page_load_time'] > 2 ? 'danger' : ($dashboard_perf['page_load_time'] > 1 ? 'warning' : 'success'); ?>">
                    <div class="metric-value"><?php echo round($dashboard_perf['page_load_time'], 3); ?>s</div>
                    <div class="metric-label">Page Load Time</div>
                </div>
                
                <div class="metric-card <?php echo $dashboard_perf['query_count'] > 50 ? 'danger' : ($dashboard_perf['query_count'] > 25 ? 'warning' : 'success'); ?>">
                    <div class="metric-value"><?php echo $dashboard_perf['query_count']; ?></div>
                    <div class="metric-label">Database Queries</div>
                </div>
                
                <div class="metric-card <?php echo count($dashboard_perf['slow_queries']) > 5 ? 'danger' : (count($dashboard_perf['slow_queries']) > 0 ? 'warning' : 'success'); ?>">
                    <div class="metric-value"><?php echo count($dashboard_perf['slow_queries']); ?></div>
                    <div class="metric-label">Slow Queries</div>
                </div>
                
                <div class="metric-card <?php echo $dashboard_perf['memory_usage'] > 50*1024*1024 ? 'danger' : ($dashboard_perf['memory_usage'] > 25*1024*1024 ? 'warning' : 'success'); ?>">
                    <div class="metric-value"><?php echo round($dashboard_perf['memory_usage'] / 1024 / 1024, 1); ?>MB</div>
                    <div class="metric-label">Memory Usage</div>
                </div>
            </div>

            <div class="section info">
                <h3>📈 System Information</h3>
                <table>
                    <tr><td><strong>PHP Version:</strong></td><td><?php echo $report['system_info']['php_version']; ?></td></tr>
                    <tr><td><strong>WordPress Version:</strong></td><td><?php echo $report['system_info']['wordpress_version']; ?></td></tr>
                    <tr><td><strong>Memory Limit:</strong></td><td><?php echo $report['system_info']['memory_limit']; ?></td></tr>
                    <tr><td><strong>Max Execution Time:</strong></td><td><?php echo $report['system_info']['max_execution_time']; ?>s</td></tr>
                    <tr><td><strong>Peak Memory:</strong></td><td><?php echo round($dashboard_perf['peak_memory'] / 1024 / 1024, 1); ?>MB</td></tr>
                </table>
            </div>
        </div>

        <!-- Database Tab -->
        <div id="database-tab" class="tab-content">
            <h3>🗄️ Database Table Analysis</h3>
            
            <?php foreach ($table_analysis as $table_name => $analysis): ?>
            <div class="section">
                <h4><?php echo ucfirst($table_name); ?> Table</h4>
                <div class="metrics-grid">
                    <div class="metric-card info">
                        <div class="metric-value"><?php echo number_format($analysis['row_count']); ?></div>
                        <div class="metric-label">Rows</div>
                    </div>
                    <div class="metric-card info">
                        <div class="metric-value"><?php echo $analysis['size_mb']; ?>MB</div>
                        <div class="metric-label">Size</div>
                    </div>
                    <div class="metric-card info">
                        <div class="metric-value"><?php echo $analysis['index_count']; ?></div>
                        <div class="metric-label">Indexes</div>
                    </div>
                    <div class="metric-card <?php echo count($analysis['missing_indexes']) > 0 ? 'warning' : 'success'; ?>">
                        <div class="metric-value"><?php echo count($analysis['missing_indexes']); ?></div>
                        <div class="metric-label">Missing Indexes</div>
                    </div>
                </div>
                
                <?php if (!empty($analysis['missing_indexes'])): ?>
                <div class="warning">
                    <h5>Suggested Indexes:</h5>
                    <?php foreach ($analysis['missing_indexes'] as $index): ?>
                    <div class="code"><?php echo esc_html($index['sql']); ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($analysis['recommendations'])): ?>
                <div class="info">
                    <h5>Recommendations:</h5>
                    <ul>
                        <?php foreach ($analysis['recommendations'] as $rec): ?>
                        <li><?php echo esc_html($rec); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Queries Tab -->
        <div id="queries-tab" class="tab-content">
            <h3>🔍 Query Analysis</h3>
            
            <?php if (!empty($dashboard_perf['slow_queries'])): ?>
            <div class="section danger">
                <h4>⚠️ Slow Queries (>100ms)</h4>
                <div class="query-list">
                    <table>
                        <thead>
                            <tr><th>Query</th><th>Time (ms)</th><th>Rows</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dashboard_perf['slow_queries'] as $query): ?>
                            <tr>
                                <td><code><?php echo esc_html($query['query']); ?></code></td>
                                <td><?php echo round($query['time'] * 1000, 2); ?>ms</td>
                                <td><?php echo $query['rows']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="section info">
                <h4>📊 Query Statistics</h4>
                <p><strong>Total Queries:</strong> <?php echo $dashboard_perf['query_count']; ?></p>
                <p><strong>Slow Queries:</strong> <?php echo count($dashboard_perf['slow_queries']); ?></p>
                <p><strong>Average Query Time:</strong> <?php 
                    $total_time = array_sum(array_column($report['query_log'], 'execution_time'));
                    echo round(($total_time / max(1, $dashboard_perf['query_count'])) * 1000, 2); 
                ?>ms</p>
            </div>
        </div>

        <!-- Recommendations Tab -->
        <div id="recommendations-tab" class="tab-content">
            <h3>💡 Optimization Recommendations</h3>
            
            <?php if (!empty($dashboard_perf['recommendations'])): ?>
            <div class="section warning">
                <h4>🎯 Performance Recommendations</h4>
                <ul>
                    <?php foreach ($dashboard_perf['recommendations'] as $rec): ?>
                    <li><?php echo esc_html($rec); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="section info">
                <h4>🔧 General Optimization Tips</h4>
                <ul>
                    <li><strong>Database:</strong> Add missing indexes for frequently queried columns</li>
                    <li><strong>Caching:</strong> Implement object caching for repeated database queries</li>
                    <li><strong>Assets:</strong> Minify and combine CSS/JS files</li>
                    <li><strong>Images:</strong> Optimize image sizes and use appropriate formats</li>
                    <li><strong>Code:</strong> Remove unused functions and optimize loops</li>
                </ul>
            </div>
            
            <div class="section success">
                <h4>✅ Next Steps</h4>
                <ol>
                    <li>Apply suggested database indexes</li>
                    <li>Optimize slow queries identified above</li>
                    <li>Implement caching for repeated operations</li>
                    <li>Minify and combine assets</li>
                    <li>Monitor performance regularly</li>
                </ol>
            </div>
        </div>

        <div class="section">
            <a href="?refresh=1" class="btn btn-primary">🔄 Refresh Analysis</a>
            <a href="admin-system-cleanup.php" class="btn btn-success">🧹 System Cleanup</a>
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