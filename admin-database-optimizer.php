<?php
/**
 * Database Optimization Admin Interface
 * Apply database optimizations and monitor performance improvements
 * 
 * Access: /wp-content/themes/yourtheme/admin-database-optimizer.php
 */

// WordPress environment
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Load the DatabaseOptimizer class
require_once get_template_directory() . '/classes/DatabaseOptimizer.php';

$optimizer = new \NORDBOOKING\Classes\DatabaseOptimizer();
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'status';

?>
<!DOCTYPE html>
<html>
<head>
    <title>NORDBOOKING Database Optimizer</title>
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
        .code { background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px; margin: 5px 0; }
        .progress-bar { width: 100%; height: 20px; background: #f8f9fa; border-radius: 10px; overflow: hidden; margin: 10px 0; }
        .progress-fill { height: 100%; background: #28a745; transition: width 0.3s ease; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 NORDBOOKING Database Optimizer</h1>
            <p>Optimize database performance with indexes, query optimization, and caching</p>
        </div>

        <?php if ($action === 'status'): ?>
            <?php 
            $status = $optimizer->getOptimizationStatus();
            $last_opt = $status['last_optimization'];
            ?>
            
            <div class="section info">
                <h3>📊 Optimization Status</h3>
                <p><strong>Last Optimization:</strong> 
                    <?php echo $last_opt ? date('Y-m-d H:i:s', $last_opt) : 'Never'; ?>
                    <?php if ($last_opt): ?>
                        (<?php echo human_time_diff($last_opt); ?> ago)
                    <?php endif; ?>
                </p>
                
                <?php if (time() - $last_opt > DAY_IN_SECONDS): ?>
                    <div class="warning">
                        <p>⚠️ Database optimization is overdue. Consider running optimization.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="section">
                <h3>🗄️ Table Analysis</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Rows</th>
                            <th>Indexes</th>
                            <th>Status</th>
                            <th>Recommendation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($status['tables_analyzed'] as $table => $data): ?>
                        <tr>
                            <td><strong><?php echo ucfirst($table); ?></strong></td>
                            <td><?php echo number_format($data['row_count']); ?></td>
                            <td><?php echo $data['index_count']; ?></td>
                            <td>
                                <?php if ($data['needs_optimization']): ?>
                                    <span style="color: #dc3545;">⚠️ Needs Optimization</span>
                                <?php else: ?>
                                    <span style="color: #28a745;">✅ Optimized</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($data['needs_optimization']): ?>
                                    Add performance indexes
                                <?php else: ?>
                                    Well optimized
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($status['recommendations'])): ?>
            <div class="section warning">
                <h3>💡 Recommendations</h3>
                <ul>
                    <?php foreach ($status['recommendations'] as $rec): ?>
                    <li><?php echo esc_html($rec); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="section">
                <h3>🎯 Actions</h3>
                <a href="?action=optimize" class="btn btn-success" onclick="return confirm('This will add database indexes and optimize tables. Continue?')">
                    🚀 Run Database Optimization
                </a>
                <a href="?action=analyze" class="btn btn-primary">
                    🔍 Analyze Slow Queries
                </a>
                <a href="admin-performance-analysis.php" class="btn btn-warning">
                    📊 Performance Analysis
                </a>
            </div>

        <?php elseif ($action === 'optimize'): ?>
            <?php
            $start_time = microtime(true);
            $results = $optimizer->applyOptimizations();
            $execution_time = microtime(true) - $start_time;
            ?>
            
            <div class="section <?php echo empty($results['indexes_failed']) ? 'success' : 'warning'; ?>">
                <h3>🚀 Optimization Results</h3>
                <p><strong>Execution Time:</strong> <?php echo round($execution_time, 3); ?> seconds</p>
                
                <?php if (!empty($results['indexes_added'])): ?>
                <div class="success">
                    <h4>✅ Indexes Added Successfully (<?php echo count($results['indexes_added']); ?>)</h4>
                    <table>
                        <thead>
                            <tr><th>Table</th><th>Index</th><th>SQL</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['indexes_added'] as $index): ?>
                            <tr>
                                <td><?php echo esc_html($index['table']); ?></td>
                                <td><code><?php echo esc_html($index['index']); ?></code></td>
                                <td><div class="code"><?php echo esc_html($index['sql']); ?></div></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($results['indexes_failed'])): ?>
                <div class="danger">
                    <h4>❌ Failed Indexes (<?php echo count($results['indexes_failed']); ?>)</h4>
                    <table>
                        <thead>
                            <tr><th>Table</th><th>Index</th><th>Error</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['indexes_failed'] as $index): ?>
                            <tr>
                                <td><?php echo esc_html($index['table']); ?></td>
                                <td><code><?php echo esc_html($index['index']); ?></code></td>
                                <td><?php echo esc_html($index['error']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($results['summary'])): ?>
                <div class="info">
                    <h4>📋 Summary</h4>
                    <ul>
                        <?php foreach ($results['summary'] as $message): ?>
                        <li><?php echo esc_html($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <a href="?action=status" class="btn btn-primary">📊 View Status</a>
                <a href="admin-performance-analysis.php" class="btn btn-success">📈 Check Performance</a>
            </div>

        <?php elseif ($action === 'analyze'): ?>
            <?php
            $analysis = $optimizer->analyzeSlowQueries();
            ?>
            
            <div class="section info">
                <h3>🔍 Slow Query Analysis</h3>
                
                <?php if (!empty($analysis['slow_queries'])): ?>
                <div class="warning">
                    <h4>⚠️ Slow Queries Detected (<?php echo count($analysis['slow_queries']); ?>)</h4>
                    <table>
                        <thead>
                            <tr><th>Query</th><th>Time (ms)</th><th>Stack</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analysis['slow_queries'] as $query): ?>
                            <tr>
                                <td><div class="code"><?php echo esc_html(substr($query['query'], 0, 200)) . '...'; ?></div></td>
                                <td><?php echo round($query['time'] * 1000, 2); ?>ms</td>
                                <td><small><?php echo esc_html(substr($query['stack'], 0, 100)) . '...'; ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="success">
                    <p>✅ No slow queries detected in current session.</p>
                    <p><em>Note: Enable SAVEQUERIES in wp-config.php for detailed query logging.</em></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($analysis['suggestions'])): ?>
                <div class="info">
                    <h4>💡 Optimization Suggestions</h4>
                    <ul>
                        <?php foreach (array_unique($analysis['suggestions']) as $suggestion): ?>
                        <li><?php echo esc_html($suggestion); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h3>🛠️ Optimized Query Examples</h3>
                <p>Use these optimized queries in your code for better performance:</p>
                
                <?php 
                $optimized_queries = $optimizer->getOptimizedQueries();
                foreach ($optimized_queries as $name => $query): 
                ?>
                <div class="info">
                    <h5><?php echo ucwords(str_replace('_', ' ', $name)); ?></h5>
                    <div class="code"><?php echo esc_html(trim($query)); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="section">
                <a href="?action=status" class="btn btn-primary">📊 Back to Status</a>
            </div>
        <?php endif; ?>
        
        <div class="section info">
            <h3>ℹ️ Information</h3>
            <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
            <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
            <p><strong>MySQL Version:</strong> <?php echo $GLOBALS['wpdb']->db_version(); ?></p>
        </div>
    </div>
</body>
</html>