<?php
/**
 * System Cleanup Utility
 * Identifies and safely removes testing and debug files
 * 
 * @package NORDBOOKING\Classes
 */
namespace NORDBOOKING\Classes;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class SystemCleaner {
    
    private $theme_dir;
    private $backup_dir;
    private $test_files = [];
    private $debug_files = [];
    private $temp_files = [];
    
    public function __construct() {
        $this->theme_dir = get_template_directory();
        $this->backup_dir = $this->theme_dir . '/cleanup-backup/';
        
        // Create backup directory if it doesn't exist
        if (!file_exists($this->backup_dir)) {
            wp_mkdir_p($this->backup_dir);
        }
    }
    
    /**
     * Identify all test files in the system
     * 
     * @return array List of test files found
     */
    public function identifyTestFiles() {
        $this->test_files = [];
        
        // Scan root directory for test-*.php files
        $files = glob($this->theme_dir . '/test-*.php');
        
        foreach ($files as $file) {
            $this->test_files[] = [
                'path' => $file,
                'name' => basename($file),
                'size' => filesize($file),
                'modified' => filemtime($file),
                'type' => 'test'
            ];
        }
        
        error_log('[SystemCleaner] Found ' . count($this->test_files) . ' test files');
        return $this->test_files;
    }
    
    /**
     * Identify all debug files in the system
     * 
     * @return array List of debug files found
     */
    public function identifyDebugFiles() {
        $this->debug_files = [];
        
        // Scan root directory for debug-*.php files
        $files = glob($this->theme_dir . '/debug-*.php');
        
        foreach ($files as $file) {
            $this->debug_files[] = [
                'path' => $file,
                'name' => basename($file),
                'size' => filesize($file),
                'modified' => filemtime($file),
                'type' => 'debug'
            ];
        }
        
        error_log('[SystemCleaner] Found ' . count($this->debug_files) . ' debug files');
        return $this->debug_files;
    }
    
    /**
     * Identify temporary and development files
     * 
     * @return array List of temporary files found
     */
    public function identifyTempFiles() {
        $this->temp_files = [];
        
        // Common temporary file patterns
        $patterns = [
            '/migrate-*.php',
            '/fix-*.php',
            '/install-*.php',
            '/organize_files.php',
            '/flush-rewrite-rules.php',
            '/enhanced-*.php',
            '/optimized_*.php',
            '/direct-ajax-test.php',
            '/test-ajax-endpoint.html',
            '/worker-js-fixes.js',
            '/worker-styling-fixes.css'
        ];
        
        foreach ($patterns as $pattern) {
            $files = glob($this->theme_dir . $pattern);
            foreach ($files as $file) {
                $this->temp_files[] = [
                    'path' => $file,
                    'name' => basename($file),
                    'size' => filesize($file),
                    'modified' => filemtime($file),
                    'type' => 'temporary'
                ];
            }
        }
        
        error_log('[SystemCleaner] Found ' . count($this->temp_files) . ' temporary files');
        return $this->temp_files;
    }
    
    /**
     * Validate that files can be safely removed
     * 
     * @param array $files Files to validate
     * @return array Validation results
     */
    public function validateRemoval($files) {
        $results = [
            'safe_to_remove' => [],
            'dependencies_found' => [],
            'warnings' => []
        ];
        
        foreach ($files as $file) {
            $file_path = $file['path'];
            $file_name = $file['name'];
            
            // Check if file is referenced in other files
            $dependencies = $this->findFileDependencies($file_name);
            
            if (empty($dependencies)) {
                $results['safe_to_remove'][] = $file;
            } else {
                $results['dependencies_found'][] = [
                    'file' => $file,
                    'dependencies' => $dependencies
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Find dependencies for a specific file
     * 
     * @param string $filename Name of file to check
     * @return array List of files that reference this file
     */
    private function findFileDependencies($filename) {
        $dependencies = [];
        
        // Files to check for dependencies
        $check_files = [
            $this->theme_dir . '/functions.php',
            $this->theme_dir . '/index.php'
        ];
        
        // Add all PHP files in classes and functions directories
        $check_files = array_merge($check_files, glob($this->theme_dir . '/classes/*.php'));
        $check_files = array_merge($check_files, glob($this->theme_dir . '/functions/*.php'));
        $check_files = array_merge($check_files, glob($this->theme_dir . '/dashboard/*.php'));
        
        foreach ($check_files as $check_file) {
            if (!file_exists($check_file)) continue;
            
            $content = file_get_contents($check_file);
            
            // Check for various ways the file might be referenced
            $patterns = [
                "/require.*['\"].*{$filename}['\"]/" ,
                "/include.*['\"].*{$filename}['\"]/" ,
                "/href=['\"].*{$filename}['\"]/" ,
                "/src=['\"].*{$filename}['\"]/" ,
                "/{$filename}/" // General reference
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $dependencies[] = basename($check_file);
                    break; // Found dependency, no need to check other patterns
                }
            }
        }
        
        return array_unique($dependencies);
    }
    
    /**
     * Create backup of files before removal
     * 
     * @param array $files Files to backup
     * @return bool Success status
     */
    public function createBackup($files) {
        $backup_manifest = [];
        
        foreach ($files as $file) {
            $source = $file['path'];
            $backup_name = date('Y-m-d_H-i-s') . '_' . $file['name'];
            $backup_path = $this->backup_dir . $backup_name;
            
            if (copy($source, $backup_path)) {
                $backup_manifest[] = [
                    'original' => $source,
                    'backup' => $backup_path,
                    'timestamp' => time()
                ];
                error_log("[SystemCleaner] Backed up {$file['name']} to {$backup_name}");
            } else {
                error_log("[SystemCleaner] Failed to backup {$file['name']}");
                return false;
            }
        }
        
        // Save backup manifest
        $manifest_path = $this->backup_dir . 'backup_manifest_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($manifest_path, json_encode($backup_manifest, JSON_PRETTY_PRINT));
        
        return true;
    }
    
    /**
     * Remove files safely
     * 
     * @param array $files Files to remove
     * @return array Results of removal operation
     */
    public function removeFiles($files) {
        $results = [
            'removed' => [],
            'failed' => [],
            'skipped' => []
        ];
        
        foreach ($files as $file) {
            $file_path = $file['path'];
            
            if (!file_exists($file_path)) {
                $results['skipped'][] = $file['name'] . ' (file not found)';
                continue;
            }
            
            if (unlink($file_path)) {
                $results['removed'][] = $file['name'];
                error_log("[SystemCleaner] Removed {$file['name']}");
            } else {
                $results['failed'][] = $file['name'];
                error_log("[SystemCleaner] Failed to remove {$file['name']}");
            }
        }
        
        return $results;
    }
    
    /**
     * Get comprehensive cleanup report
     * 
     * @return array Complete analysis of files to be cleaned
     */
    public function getCleanupReport() {
        $test_files = $this->identifyTestFiles();
        $debug_files = $this->identifyDebugFiles();
        $temp_files = $this->identifyTempFiles();
        
        $all_files = array_merge($test_files, $debug_files, $temp_files);
        $validation = $this->validateRemoval($all_files);
        
        return [
            'summary' => [
                'test_files_count' => count($test_files),
                'debug_files_count' => count($debug_files),
                'temp_files_count' => count($temp_files),
                'total_files' => count($all_files),
                'safe_to_remove' => count($validation['safe_to_remove']),
                'has_dependencies' => count($validation['dependencies_found'])
            ],
            'files' => [
                'test' => $test_files,
                'debug' => $debug_files,
                'temporary' => $temp_files
            ],
            'validation' => $validation,
            'backup_dir' => $this->backup_dir
        ];
    }
    
    /**
     * Perform complete cleanup operation
     * 
     * @param bool $create_backup Whether to create backup before removal
     * @return array Results of cleanup operation
     */
    public function performCleanup($create_backup = true) {
        $report = $this->getCleanupReport();
        $safe_files = $report['validation']['safe_to_remove'];
        
        $results = [
            'backup_created' => false,
            'files_removed' => [],
            'files_with_dependencies' => $report['validation']['dependencies_found'],
            'summary' => []
        ];
        
        if (empty($safe_files)) {
            $results['summary'][] = 'No files safe to remove automatically';
            return $results;
        }
        
        // Create backup if requested
        if ($create_backup) {
            $results['backup_created'] = $this->createBackup($safe_files);
            if (!$results['backup_created']) {
                $results['summary'][] = 'Backup failed - cleanup aborted';
                return $results;
            }
        }
        
        // Remove safe files
        $removal_results = $this->removeFiles($safe_files);
        $results['files_removed'] = $removal_results;
        
        $results['summary'][] = count($removal_results['removed']) . ' files removed successfully';
        if (!empty($removal_results['failed'])) {
            $results['summary'][] = count($removal_results['failed']) . ' files failed to remove';
        }
        
        return $results;
    }
}