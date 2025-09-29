<?php
/**
 * Directory Migration Utility
 * Helps migrate files to the new organized structure
 */

class DirectoryMigrator {
    
    private $backup_dir = 'migration-backup';
    private $dry_run = false;
    
    public function __construct($dry_run = false) {
        $this->dry_run = $dry_run;
        if (!$dry_run && !is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
    }
    
    /**
     * Plan the migration of classes from /classes to /src/Classes
     */
    public function planClassesMigration() {
        $classes_dir = get_template_directory() . '/classes';
        $target_dir = get_template_directory() . '/src/Classes';
        
        if (!is_dir($classes_dir)) {
            return ['error' => 'Classes directory not found'];
        }
        
        $files = glob($classes_dir . '/*.php');
        $migration_plan = [];
        
        foreach ($files as $file) {
            $filename = basename($file);
            $source = $file;
            $target = $target_dir . '/' . $filename;
            
            $migration_plan[] = [
                'source' => $source,
                'target' => $target,
                'filename' => $filename,
                'size' => filesize($source),
                'references' => $this->findFileReferences($filename)
            ];
        }
        
        return $migration_plan;
    }
    
    /**
     * Find references to a file in the codebase
     */
    private function findFileReferences($filename) {
        $references = [];
        $search_dirs = [
            get_template_directory(),
            get_template_directory() . '/dashboard',
            get_template_directory() . '/includes'
        ];
        
        foreach ($search_dirs as $dir) {
            if (!is_dir($dir)) continue;
            
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir)
            );
            
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') continue;
                
                $content = file_get_contents($file->getPathname());
                if (strpos($content, $filename) !== false) {
                    $references[] = $file->getPathname();
                }
            }
        }
        
        return $references;
    }
    
    /**
     * Execute the classes migration
     */
    public function migrateClasses($plan) {
        $results = [];
        
        foreach ($plan as $item) {
            if ($this->dry_run) {
                $results[] = [
                    'file' => $item['filename'],
                    'action' => 'would_move',
                    'from' => $item['source'],
                    'to' => $item['target']
                ];
                continue;
            }
            
            // Create backup
            $backup_path = $this->backup_dir . '/' . $item['filename'];
            copy($item['source'], $backup_path);
            
            // Ensure target directory exists
            $target_dir = dirname($item['target']);
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            
            // Move file
            if (rename($item['source'], $item['target'])) {
                $results[] = [
                    'file' => $item['filename'],
                    'action' => 'moved',
                    'success' => true,
                    'backup' => $backup_path
                ];
            } else {
                $results[] = [
                    'file' => $item['filename'],
                    'action' => 'failed',
                    'success' => false,
                    'error' => 'Could not move file'
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Update file references after migration
     */
    public function updateReferences($migration_results) {
        $updates = [];
        
        foreach ($migration_results as $result) {
            if (!$result['success']) continue;
            
            $old_path = "classes/{$result['file']}";
            $new_path = "src/Classes/{$result['file']}";
            
            // Find and update references
            $references = $this->findFileReferences($result['file']);
            
            foreach ($references as $ref_file) {
                if ($this->dry_run) {
                    $updates[] = [
                        'file' => $ref_file,
                        'action' => 'would_update',
                        'old_path' => $old_path,
                        'new_path' => $new_path
                    ];
                    continue;
                }
                
                $content = file_get_contents($ref_file);
                $updated_content = str_replace($old_path, $new_path, $content);
                
                if ($content !== $updated_content) {
                    file_put_contents($ref_file, $updated_content);
                    $updates[] = [
                        'file' => $ref_file,
                        'action' => 'updated',
                        'success' => true
                    ];
                }
            }
        }
        
        return $updates;
    }
    
    /**
     * Validate migration success
     */
    public function validateMigration() {
        $validation = [
            'classes_moved' => 0,
            'references_updated' => 0,
            'errors' => []
        ];
        
        // Check if classes directory is empty or can be removed
        $classes_dir = get_template_directory() . '/classes';
        if (is_dir($classes_dir)) {
            $remaining_files = glob($classes_dir . '/*.php');
            if (empty($remaining_files)) {
                $validation['classes_directory_empty'] = true;
            } else {
                $validation['remaining_files'] = count($remaining_files);
            }
        }
        
        // Check if new classes directory has files
        $new_classes_dir = get_template_directory() . '/src/Classes';
        if (is_dir($new_classes_dir)) {
            $new_files = glob($new_classes_dir . '/*.php');
            $validation['classes_moved'] = count($new_files);
        }
        
        return $validation;
    }
    
    /**
     * Rollback migration if needed
     */
    public function rollback() {
        if (!is_dir($this->backup_dir)) {
            return ['error' => 'No backup directory found'];
        }
        
        $backup_files = glob($this->backup_dir . '/*.php');
        $restored = [];
        
        foreach ($backup_files as $backup_file) {
            $filename = basename($backup_file);
            $original_path = get_template_directory() . '/classes/' . $filename;
            
            if (copy($backup_file, $original_path)) {
                $restored[] = $filename;
                
                // Remove from new location
                $new_path = get_template_directory() . '/src/Classes/' . $filename;
                if (file_exists($new_path)) {
                    unlink($new_path);
                }
            }
        }
        
        return ['restored' => $restored];
    }
}