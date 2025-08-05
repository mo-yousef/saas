<?php
// Custom Class Autoloader
spl_autoload_register(function ($class_name) {
    // Check if the class belongs to our theme's namespace
    if (strpos($class_name, 'MoBooking\\') !== 0) {
        return; // Not our class, skip
    }

    // Remove the root namespace prefix 'MoBooking\'
    $relative_class_name = substr($class_name, strlen('MoBooking\\'));

    // Convert namespace separators to directory separators
    $file_path = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class_name);

    // Build the full file path
    $file = MOBOOKING_THEME_DIR . 'classes' . DIRECTORY_SEPARATOR . $file_path . '.php';

    // for debugging:
    // error_log("Trying to load class: $class_name");
    // error_log("Looking for file: $file");

    // Check if the file exists and require it
    if (file_exists($file)) {
        require_once $file;
    }
});
?>
