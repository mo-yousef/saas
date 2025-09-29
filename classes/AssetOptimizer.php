<?php
/**
 * Asset Optimization Utility
 * Handles CSS/JS optimization, minification, and combination
 * 
 * @package NORDBOOKING\Classes
 */
namespace NORDBOOKING\Classes;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class AssetOptimizer {
    
    private $theme_dir;
    private $theme_uri;
    private $css_dir;
    private $js_dir;
    
    public function __construct() {
        $this->theme_dir = get_template_directory();
        $this->theme_uri = get_template_directory_uri();
        $this->css_dir = $this->theme_dir . '/assets/css/';
        $this->js_dir = $this->theme_dir . '/assets/js/';
    }
    
    /**
     * Analyze CSS files and identify optimization opportunities
     */
    public function analyzeCSSFiles() {
        $analysis = [
            'total_files' => 0,
            'total_size' => 0,
            'files' => [],
            'duplicates' => [],
            'unused_files' => [],
            'consolidation_groups' => [],
            'recommendations' => []
        ];
        
        if (!is_dir($this->css_dir)) {
            return $analysis;
        }
        
        $css_files = glob($this->css_dir . '*.css');
        $analysis['total_files'] = count($css_files);
        
        foreach ($css_files as $file) {
            $filename = basename($file);
            $size = filesize($file);
            $content = file_get_contents($file);
            
            $analysis['files'][$filename] = [
                'size' => $size,
                'size_formatted' => size_format($size),
                'lines' => substr_count($content, "\n") + 1,
                'selectors' => substr_count($content, '{'),
                'last_modified' => filemtime($file),
                'content_hash' => md5($content)
            ];
            
            $analysis['total_size'] += $size;
        }
        
        // Group files by purpose for consolidation
        $analysis['consolidation_groups'] = $this->groupCSSFiles($analysis['files']);
        
        // Find potential duplicates
        $analysis['duplicates'] = $this->findDuplicateCSS($analysis['files']);
        
        // Identify unused files
        $analysis['unused_files'] = $this->identifyUnusedCSS($analysis['files']);
        
        // Generate recommendations
        if ($analysis['total_files'] > 10) {
            $analysis['recommendations'][] = 'Too many CSS files (' . $analysis['total_files'] . ') - consider consolidation';
        }
        
        if ($analysis['total_size'] > 500 * 1024) { // 500KB
            $analysis['recommendations'][] = 'Large total CSS size (' . size_format($analysis['total_size']) . ') - minification needed';
        }
        
        if (!empty($analysis['duplicates'])) {
            $analysis['recommendations'][] = count($analysis['duplicates']) . ' duplicate CSS files found - remove duplicates';
        }
        
        if (!empty($analysis['unused_files'])) {
            $analysis['recommendations'][] = count($analysis['unused_files']) . ' potentially unused CSS files - review and remove';
        }
        
        return $analysis;
    }
    
    /**
     * Group CSS files by functionality for consolidation
     */
    private function groupCSSFiles($files) {
        $groups = [
            'dashboard' => [],
            'booking-form' => [],
            'front-page' => [],
            'auth' => [],
            'components' => [],
            'utilities' => []
        ];
        
        foreach ($files as $filename => $data) {
            $name_lower = strtolower($filename);
            
            if (strpos($name_lower, 'dashboard') !== false) {
                $groups['dashboard'][] = $filename;
            } elseif (strpos($name_lower, 'booking') !== false) {
                $groups['booking-form'][] = $filename;
            } elseif (strpos($name_lower, 'front') !== false || strpos($name_lower, 'hero') !== false || strpos($name_lower, 'pricing') !== false) {
                $groups['front-page'][] = $filename;
            } elseif (strpos($name_lower, 'auth') !== false || strpos($name_lower, 'login') !== false) {
                $groups['auth'][] = $filename;
            } elseif (strpos($name_lower, 'header') !== false || strpos($name_lower, 'footer') !== false || strpos($name_lower, 'dialog') !== false) {
                $groups['components'][] = $filename;
            } else {
                $groups['utilities'][] = $filename;
            }
        }
        
        // Remove empty groups
        return array_filter($groups);
    }
    
    /**
     * Find duplicate CSS files
     */
    private function findDuplicateCSS($files) {
        $duplicates = [];
        $hashes = [];
        
        foreach ($files as $filename => $data) {
            $hash = $data['content_hash'];
            if (isset($hashes[$hash])) {
                $duplicates[] = [
                    'original' => $hashes[$hash],
                    'duplicate' => $filename,
                    'size' => $data['size']
                ];
            } else {
                $hashes[$hash] = $filename;
            }
        }
        
        return $duplicates;
    }
    
    /**
     * Identify potentially unused CSS files
     */
    private function identifyUnusedCSS($files) {
        $unused = [];
        
        // Files that might be unused based on naming patterns
        $suspicious_patterns = [
            'old', 'backup', 'temp', 'test', 'unused', 'deprecated',
            'v1', 'v2', 'legacy', 'archive'
        ];
        
        foreach ($files as $filename => $data) {
            $name_lower = strtolower($filename);
            
            foreach ($suspicious_patterns as $pattern) {
                if (strpos($name_lower, $pattern) !== false) {
                    $unused[] = $filename;
                    break;
                }
            }
            
            // Check if file is very old and small (might be unused)
            if ($data['size'] < 1024 && time() - $data['last_modified'] > 30 * DAY_IN_SECONDS) {
                $unused[] = $filename;
            }
        }
        
        return array_unique($unused);
    }
    
    /**
     * Consolidate CSS files by group
     */
    public function consolidateCSS($groups, $minify = true) {
        $results = [
            'consolidated_files' => [],
            'original_count' => 0,
            'new_count' => 0,
            'size_before' => 0,
            'size_after' => 0,
            'errors' => []
        ];
        
        foreach ($groups as $group_name => $files) {
            if (empty($files)) continue;
            
            $consolidated_content = '';
            $group_size_before = 0;
            
            // Add header comment
            $consolidated_content .= "/* NORDBOOKING Consolidated CSS - {$group_name} */\n";
            $consolidated_content .= "/* Generated: " . date('Y-m-d H:i:s') . " */\n";
            $consolidated_content .= "/* Original files: " . implode(', ', $files) . " */\n\n";
            
            foreach ($files as $filename) {
                $file_path = $this->css_dir . $filename;
                if (!file_exists($file_path)) {
                    $results['errors'][] = "File not found: $filename";
                    continue;
                }
                
                $content = file_get_contents($file_path);
                $group_size_before += filesize($file_path);
                
                // Add file separator comment
                $consolidated_content .= "/* === {$filename} === */\n";
                $consolidated_content .= $content . "\n\n";
                
                $results['original_count']++;
            }
            
            // Minify if requested
            if ($minify) {
                $consolidated_content = $this->minifyCSS($consolidated_content);
            }
            
            // Save consolidated file
            $consolidated_filename = "consolidated-{$group_name}.css";
            $consolidated_path = $this->css_dir . $consolidated_filename;
            
            if (file_put_contents($consolidated_path, $consolidated_content)) {
                $size_after = filesize($consolidated_path);
                
                $results['consolidated_files'][] = [
                    'group' => $group_name,
                    'filename' => $consolidated_filename,
                    'original_files' => $files,
                    'size_before' => $group_size_before,
                    'size_after' => $size_after,
                    'compression_ratio' => round((1 - $size_after / $group_size_before) * 100, 1)
                ];
                
                $results['size_before'] += $group_size_before;
                $results['size_after'] += $size_after;
                $results['new_count']++;
            } else {
                $results['errors'][] = "Failed to create consolidated file: $consolidated_filename";
            }
        }
        
        return $results;
    }
    
    /**
     * Simple CSS minification
     */
    private function minifyCSS($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove whitespace around specific characters
        $css = str_replace([' {', '{ ', ' }', '} ', ': ', ' :', '; ', ' ;', ', ', ' ,'], ['{', '{', '}', '}', ':', ':', ';', ';', ',', ','], $css);
        
        // Remove trailing semicolon before closing brace
        $css = str_replace(';}', '}', $css);
        
        return trim($css);
    }
    
    /**
     * Analyze JavaScript files
     */
    public function analyzeJSFiles() {
        $analysis = [
            'total_files' => 0,
            'total_size' => 0,
            'files' => [],
            'consolidation_groups' => [],
            'duplicates' => [],
            'unused_files' => [],
            'recommendations' => []
        ];
        
        if (!is_dir($this->js_dir)) {
            return $analysis;
        }
        
        $js_files = glob($this->js_dir . '*.js');
        $analysis['total_files'] = count($js_files);
        
        foreach ($js_files as $file) {
            $filename = basename($file);
            $size = filesize($file);
            $content = file_get_contents($file);
            
            $analysis['files'][$filename] = [
                'size' => $size,
                'size_formatted' => size_format($size),
                'lines' => substr_count($content, "\n") + 1,
                'functions' => substr_count($content, 'function'),
                'last_modified' => filemtime($file),
                'content_hash' => md5($content)
            ];
            
            $analysis['total_size'] += $size;
        }
        
        // Group JS files for consolidation
        $analysis['consolidation_groups'] = $this->groupJSFiles($analysis['files']);
        
        // Find duplicates
        $analysis['duplicates'] = $this->findDuplicateJS($analysis['files']);
        
        // Find unused files
        $analysis['unused_files'] = $this->identifyUnusedJS($analysis['files']);
        
        if ($analysis['total_files'] > 5) {
            $analysis['recommendations'][] = 'Consider combining JavaScript files to reduce HTTP requests';
        }
        
        if ($analysis['total_size'] > 200 * 1024) { // 200KB
            $analysis['recommendations'][] = 'Large JavaScript size - consider minification';
        }
        
        if (!empty($analysis['duplicates'])) {
            $analysis['recommendations'][] = count($analysis['duplicates']) . ' duplicate JS files found';
        }
        
        return $analysis;
    }
    
    /**
     * Group JavaScript files by functionality
     */
    private function groupJSFiles($files) {
        $groups = [
            'dashboard-core' => [],
            'dashboard-pages' => [],
            'booking-system' => [],
            'front-end' => [],
            'utilities' => []
        ];
        
        foreach ($files as $filename => $data) {
            $name_lower = strtolower($filename);
            
            if (in_array($filename, ['dashboard.js', 'dashboard-header.js', 'toast.js', 'dialog.js'])) {
                $groups['dashboard-core'][] = $filename;
            } elseif (strpos($name_lower, 'dashboard-') !== false) {
                $groups['dashboard-pages'][] = $filename;
            } elseif (strpos($name_lower, 'booking') !== false) {
                $groups['booking-system'][] = $filename;
            } elseif (strpos($name_lower, 'auth') !== false || strpos($name_lower, 'hero') !== false || strpos($name_lower, 'pricing') !== false || strpos($name_lower, 'features') !== false) {
                $groups['front-end'][] = $filename;
            } else {
                $groups['utilities'][] = $filename;
            }
        }
        
        return array_filter($groups);
    }
    
    /**
     * Find duplicate JavaScript files
     */
    private function findDuplicateJS($files) {
        $duplicates = [];
        $hashes = [];
        
        foreach ($files as $filename => $data) {
            $hash = $data['content_hash'];
            if (isset($hashes[$hash])) {
                $duplicates[] = [
                    'original' => $hashes[$hash],
                    'duplicate' => $filename,
                    'size' => $data['size']
                ];
            } else {
                $hashes[$hash] = $filename;
            }
        }
        
        return $duplicates;
    }
    
    /**
     * Identify potentially unused JavaScript files
     */
    private function identifyUnusedJS($files) {
        $unused = [];
        
        $suspicious_patterns = [
            'old', 'backup', 'temp', 'test', 'unused', 'deprecated',
            'v1', 'v2', 'legacy', 'archive'
        ];
        
        foreach ($files as $filename => $data) {
            $name_lower = strtolower($filename);
            
            foreach ($suspicious_patterns as $pattern) {
                if (strpos($name_lower, $pattern) !== false) {
                    $unused[] = $filename;
                    break;
                }
            }
            
            // Check if file is very small and old
            if ($data['size'] < 512 && time() - $data['last_modified'] > 30 * DAY_IN_SECONDS) {
                $unused[] = $filename;
            }
        }
        
        return array_unique($unused);
    }
    
    /**
     * Consolidate JavaScript files
     */
    public function consolidateJS($groups, $minify = true) {
        $results = [
            'consolidated_files' => [],
            'original_count' => 0,
            'new_count' => 0,
            'size_before' => 0,
            'size_after' => 0,
            'errors' => []
        ];
        
        foreach ($groups as $group_name => $files) {
            if (empty($files)) continue;
            
            $consolidated_content = '';
            $group_size_before = 0;
            
            // Add header comment
            $consolidated_content .= "/* NORDBOOKING Consolidated JS - {$group_name} */\n";
            $consolidated_content .= "/* Generated: " . date('Y-m-d H:i:s') . " */\n";
            $consolidated_content .= "/* Original files: " . implode(', ', $files) . " */\n\n";
            
            foreach ($files as $filename) {
                $file_path = $this->js_dir . $filename;
                if (!file_exists($file_path)) {
                    $results['errors'][] = "File not found: $filename";
                    continue;
                }
                
                $content = file_get_contents($file_path);
                $group_size_before += filesize($file_path);
                
                // Add file separator comment
                $consolidated_content .= "/* === {$filename} === */\n";
                $consolidated_content .= $content . "\n\n";
                
                $results['original_count']++;
            }
            
            // Minify if requested
            if ($minify) {
                $consolidated_content = $this->minifyJS($consolidated_content);
            }
            
            // Save consolidated file
            $consolidated_filename = "consolidated-{$group_name}.js";
            $consolidated_path = $this->js_dir . $consolidated_filename;
            
            if (file_put_contents($consolidated_path, $consolidated_content)) {
                $size_after = filesize($consolidated_path);
                
                $results['consolidated_files'][] = [
                    'group' => $group_name,
                    'filename' => $consolidated_filename,
                    'original_files' => $files,
                    'size_before' => $group_size_before,
                    'size_after' => $size_after,
                    'compression_ratio' => round((1 - $size_after / $group_size_before) * 100, 1)
                ];
                
                $results['size_before'] += $group_size_before;
                $results['size_after'] += $size_after;
                $results['new_count']++;
            } else {
                $results['errors'][] = "Failed to create consolidated file: $consolidated_filename";
            }
        }
        
        return $results;
    }
    
    /**
     * Simple JavaScript minification
     */
    private function minifyJS($js) {
        // Remove single-line comments (but preserve URLs)
        $js = preg_replace('/(?<!:)\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remove unnecessary whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remove whitespace around operators and punctuation
        $js = preg_replace('/\s*([{}();,=+\-*\/])\s*/', '$1', $js);
        
        return trim($js);
    }
    
    /**
     * Generate optimized asset loading strategy
     */
    public function generateLoadingStrategy() {
        return [
            'critical_css' => [
                'reset.css',
                'dashboard-main.css'
            ],
            'deferred_css' => [
                'dashboard-services-enhanced.css',
                'dashboard-overview-enhanced.css',
                'booking-form-modern.css'
            ],
            'conditional_css' => [
                'front-page' => ['front-page.css', 'hero-section.css', 'pricing-section.css'],
                'dashboard' => ['dashboard.css', 'dashboard-sidebar-enhanced.css'],
                'booking' => ['booking-form-redesigned.css', 'booking-form-validation.css']
            ],
            'recommendations' => [
                'Load critical CSS inline for above-the-fold content',
                'Defer non-critical CSS using media="print" onload technique',
                'Use conditional loading based on page type',
                'Implement CSS preloading for next-page resources'
            ]
        ];
    }
    
    /**
     * Get comprehensive asset optimization report
     */
    public function getOptimizationReport() {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'css_analysis' => $this->analyzeCSSFiles(),
            'js_analysis' => $this->analyzeJSFiles(),
            'loading_strategy' => $this->generateLoadingStrategy(),
            'performance_impact' => $this->calculatePerformanceImpact()
        ];
    }
    
    /**
     * Calculate potential performance impact of optimizations
     */
    private function calculatePerformanceImpact() {
        $css_analysis = $this->analyzeCSSFiles();
        
        return [
            'current_http_requests' => $css_analysis['total_files'],
            'optimized_http_requests' => count($css_analysis['consolidation_groups']),
            'request_reduction' => $css_analysis['total_files'] - count($css_analysis['consolidation_groups']),
            'estimated_load_time_improvement' => ($css_analysis['total_files'] - count($css_analysis['consolidation_groups'])) * 50, // 50ms per request saved
            'current_css_size' => $css_analysis['total_size'],
            'estimated_minified_size' => round($css_analysis['total_size'] * 0.7), // Estimate 30% reduction
            'bandwidth_savings' => round($css_analysis['total_size'] * 0.3)
        ];
    }
}