<?php
// Custom Class Autoloader
spl_autoload_register(function ($class_name) {
    // Check if the class belongs to our theme's namespace
    if (strpos($class_name, 'NORDBOOKING\\') !== 0) {
        return false; // Not our class, skip
    }

    // Remove the root namespace prefix 'NORDBOOKING\'
    $relative_class_name = substr($class_name, strlen('NORDBOOKING\\')); // e.g., Classes\Services or Payments\Manager

    // Split the relative class name into parts
    $parts = explode('\\', $relative_class_name);

    // If the first part is "Classes", change it to "classes" for the path
    if (count($parts) > 0 && $parts[0] === 'Classes') { // Check count > 0 before accessing $parts[0]
        $parts[0] = 'classes';
    }
    // Potentially add more rules here if other top-level namespace directories (like Payments) are also lowercase
    // For now, only 'Classes' -> 'classes' is confirmed as an issue.

    $file_path_part = implode(DIRECTORY_SEPARATOR, $parts);
    $file = NORDBOOKING_THEME_DIR . $file_path_part . '.php';

    // Check if the file exists (case-sensitive check on Linux/macOS)
    // error_log("Autoloader trying: " . $file); // Debugging line
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    // error_log("Autoloader FAILED for: " . $class_name . " (tried path: " . $file . ")"); // Debugging line
    return false;
});
?>
