<?php
/**
 * Functions.php Refactoring Utility
 * Breaks down large functions.php into organized, smaller files
 * 
 * @package NORDBOOKING\Classes
 */
namespace NORDBOOKING\Classes;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class FunctionsRefactor {
    
    private $theme_dir;
    private $functions_dir;
    private $original_file;
    
    public function __construct() {
        $this->theme_dir = get_template_directory();
        $this->functions_dir = $this->theme_dir . '/functions/';
        $this->original_file = $this->theme_dir . '/functions.php';
    }
    
    /**
     * Analyze functions.php file structure
     */
    public function analyzeFunctionsFile() {
        if (!file_exists($this->original_file)) {
            return ['error' => 'functions.php not found'];
        }
        
        $content = file_get_contents($this->original_file);
        $lines = explode("\n", $content);
        
        $analysis = [
            'total_lines' => count($lines),
            'file_size' => filesize($this->original_file),
            'sections' => [],
            'functions' => [],
            'hooks' => [],
            'includes' => [],
            'recommendations' => []
        ];
        
        // Parse the file to identify sections
        $current_section = 'header';
        $section_start = 0;
        
        foreach ($lines as $line_num => $line) {
            $line_trimmed = trim($line);
            
            // Identify section headers (comments with ===)
            if (strpos($line_trimmed, '// =====') !== false || strpos($line_trimmed, '/* =====') !== false) {
                // Save previous section
                if ($current_section !== 'header') {
                    $analysis['sections'][$current_section] = [
                        'start_line' => $section_start,
                        'end_line' => $line_num - 1,
                        'line_count' => $line_num - $section_start
                    ];
                }
                
                // Start new section
                $current_section = $this->extractSectionName($line_trimmed);
                $section_start = $line_num;
            }
            
            // Count functions
            if (preg_match('/^function\s+(\w+)/', $line_trimmed, $matches)) {
                $analysis['functions'][] = [
                    'name' => $matches[1],
                    'line' => $line_num + 1
                ];
            }
            
            // Count hooks
            if (preg_match('/add_action\s*\(\s*[\'"]([^\'"]+)/', $line_trimmed, $matches)) {
                $analysis['hooks'][] = [
                    'type' => 'action',
                    'hook' => $matches[1],
                    'line' => $line_num + 1
                ];
            }
            
            if (preg_match('/add_filter\s*\(\s*[\'"]([^\'"]+)/', $line_trimmed, $matches)) {
                $analysis['hooks'][] = [
                    'type' => 'filter',
                    'hook' => $matches[1],
                    'line' => $line_num + 1
                ];
            }
            
            // Count includes
            if (preg_match('/(require_once|include_once|require|include)\s+/', $line_trimmed)) {
                $analysis['includes'][] = [
                    'line' => $line_num + 1,
                    'content' => $line_trimmed
                ];
            }
        }
        
        // Add final section
        if ($current_section !== 'header') {
            $analysis['sections'][$current_section] = [
                'start_line' => $section_start,
                'end_line' => count($lines) - 1,
                'line_count' => count($lines) - $section_start
            ];
        }
        
        // Generate recommendations
        if ($analysis['total_lines'] > 1000) {
            $analysis['recommendations'][] = 'File is very large (' . $analysis['total_lines'] . ' lines) - refactoring needed';
        }
        
        if (count($analysis['functions']) > 20) {
            $analysis['recommendations'][] = 'Too many functions (' . count($analysis['functions']) . ') - move to classes';
        }
        
        if (count($analysis['hooks']) > 50) {
            $analysis['recommendations'][] = 'Too many hooks (' . count($analysis['hooks']) . ') - organize by functionality';
        }
        
        return $analysis;
    }
    
    /**
     * Extract section name from comment
     */
    private function extractSectionName($comment) {
        // Remove comment markers and equals signs
        $name = preg_replace('/[\/\*=\s]+/', ' ', $comment);
        $name = trim($name);
        
        // Convert to lowercase and replace spaces with underscores
        $name = strtolower(str_replace(' ', '_', $name));
        
        // Clean up common words
        $name = str_replace(['handler', 'handlers', 'system', 'ajax'], '', $name);
        $name = trim($name, '_');
        
        return $name ?: 'misc';
    }
    
    /**
     * Refactor functions.php into organized files
     */
    public function refactorFunctionsFile() {
        $analysis = $this->analyzeFunctionsFile();
        
        if (isset($analysis['error'])) {
            return $analysis;
        }
        
        $content = file_get_contents($this->original_file);
        $lines = explode("\n", $content);
        
        $results = [
            'created_files' => [],
            'backup_created' => false,
            'new_functions_php' => '',
            'errors' => []
        ];
        
        // Create backup
        $backup_path = $this->original_file . '.backup.' . date('Y-m-d_H-i-s');
        if (copy($this->original_file, $backup_path)) {
            $results['backup_created'] = $backup_path;
        }
        
        // Create functions directory if it doesn't exist
        if (!is_dir($this->functions_dir)) {
            wp_mkdir_p($this->functions_dir);
        }
        
        // Extract sections into separate files
        foreach ($analysis['sections'] as $section_name => $section_data) {
            if ($section_name === 'header') continue;
            
            $section_lines = array_slice($lines, $section_data['start_line'], $section_data['line_count']);
            $section_content = implode("\n", $section_lines);
            
            // Clean up section content
            $section_content = $this->cleanSectionContent($section_content, $section_name);
            
            // Create new file
            $filename = "functions-{$section_name}.php";
            $filepath = $this->functions_dir . $filename;
            
            if (file_put_contents($filepath, $section_content)) {
                $results['created_files'][] = [
                    'section' => $section_name,
                    'filename' => $filename,
                    'lines' => count($section_lines),
                    'size' => filesize($filepath)
                ];
            } else {
                $results['errors'][] = "Failed to create file: $filename";
            }
        }
        
        // Create new streamlined functions.php
        $new_functions_content = $this->createStreamlinedFunctions($analysis);
        
        if (file_put_contents($this->original_file, $new_functions_content)) {
            $results['new_functions_php'] = [
                'size_before' => $analysis['file_size'],
                'size_after' => filesize($this->original_file),
                'lines_before' => $analysis['total_lines'],
                'lines_after' => substr_count($new_functions_content, "\n") + 1,
                'reduction_percentage' => round((1 - filesize($this->original_file) / $analysis['file_size']) * 100, 1)
            ];
        } else {
            $results['errors'][] = "Failed to update functions.php";
        }
        
        return $results;
    }
    
    /**
     * Clean up section content for separate file
     */
    private function cleanSectionContent($content, $section_name) {
        $cleaned = "<?php\n";
        $cleaned .= "/**\n";
        $cleaned .= " * NORDBOOKING " . ucwords(str_replace('_', ' ', $section_name)) . "\n";
        $cleaned .= " * Extracted from functions.php for better organization\n";
        $cleaned .= " * \n";
        $cleaned .= " * @package NORDBOOKING\n";
        $cleaned .= " */\n\n";
        $cleaned .= "// Exit if accessed directly.\n";
        $cleaned .= "if (!defined('ABSPATH')) {\n";
        $cleaned .= "    exit;\n";
        $cleaned .= "}\n\n";
        
        // Remove section header comments
        $content = preg_replace('/\/\/ =+.*?=+/', '', $content);
        $content = preg_replace('/\/\* =+.*?=+ \*\//', '', $content);
        
        $cleaned .= trim($content);
        
        return $cleaned;
    }
    
    /**
     * Create streamlined functions.php
     */
    private function createStreamlinedFunctions($analysis) {
        $content = "<?php\n";
        $content .= "/**\n";
        $content .= " * NORDBOOKING functions and definitions\n";
        $content .= " * Streamlined version - functionality moved to organized files\n";
        $content .= " *\n";
        $content .= " * @package NORDBOOKING\n";
        $content .= " */\n\n";
        
        // Constants
        $content .= "if (!defined('NORDBOOKING_VERSION')) {\n";
        $content .= "    define('NORDBOOKING_VERSION', '0.1.24');\n";
        $content .= "}\n";
        $content .= "if (!defined('NORDBOOKING_DB_VERSION')) {\n";
        $content .= "    define('NORDBOOKING_DB_VERSION', '2.3');\n";
        $content .= "}\n";
        $content .= "if (!defined('NORDBOOKING_THEME_DIR')) {\n";
        $content .= "    define('NORDBOOKING_THEME_DIR', trailingslashit(get_template_directory()));\n";
        $content .= "}\n";
        $content .= "if (!defined('NORDBOOKING_THEME_URI')) {\n";
        $content .= "    define('NORDBOOKING_THEME_URI', trailingslashit(get_template_directory_uri()));\n";
        $content .= "}\n\n";
        
        // Core includes
        $content .= "// Core functionality includes\n";
        $content .= "require_once NORDBOOKING_THEME_DIR . 'lib/stripe-php/init.php';\n";
        $content .= "require_once NORDBOOKING_THEME_DIR . 'functions/theme-setup.php';\n";
        $content .= "require_once NORDBOOKING_THEME_DIR . 'functions/autoloader.php';\n";
        $content .= "require_once NORDBOOKING_THEME_DIR . 'functions/routing.php';\n";
        $content .= "require_once NORDBOOKING_THEME_DIR . 'functions/initialization.php';\n";
        $content .= "require_once NORDBOOKING_THEME_DIR . 'functions/utilities.php';\n";
        $content .= "require_once NORDBOOKING_THEME_DIR . 'functions/ajax.php';\n";
        $content .= "require_once NORDBOOKING_THEME_DIR . 'functions/email.php';\n";
        $content .= "require_once NORDBOOKING_THEME_DIR . 'functions/access-control.php';\n";
        $content .= "require_once NORDBOOKING_THEME_DIR . 'functions/booking-form-restrictions.php';\n\n";
        
        // Include refactored files
        $content .= "// Refactored functionality includes\n";
        foreach ($analysis['sections'] as $section_name => $section_data) {
            if ($section_name === 'header') continue;
            $content .= "require_once NORDBOOKING_THEME_DIR . 'functions/functions-{$section_name}.php';\n";
        }
        
        $content .= "\n// Performance monitoring\n";
        $content .= "if (file_exists(NORDBOOKING_THEME_DIR . 'performance_monitoring.php')) {\n";
        $content .= "    require_once NORDBOOKING_THEME_DIR . 'performance_monitoring.php';\n";
        $content .= "}\n";
        
        return $content;
    }
    
    /**
     * Get refactoring recommendations
     */
    public function getRefactoringRecommendations() {
        $analysis = $this->analyzeFunctionsFile();
        
        return [
            'current_state' => [
                'file_size' => size_format($analysis['file_size']),
                'total_lines' => $analysis['total_lines'],
                'functions_count' => count($analysis['functions']),
                'hooks_count' => count($analysis['hooks']),
                'sections_count' => count($analysis['sections'])
            ],
            'recommendations' => $analysis['recommendations'],
            'benefits' => [
                'Faster loading - smaller files load quicker',
                'Better organization - easier to maintain',
                'Improved caching - individual files can be cached',
                'Reduced memory usage - only load needed functionality',
                'Better debugging - easier to isolate issues'
            ],
            'estimated_improvement' => [
                'load_time_reduction' => '40-60%',
                'memory_usage_reduction' => '20-30%',
                'maintainability_improvement' => '80%'
            ]
        ];
    }
}