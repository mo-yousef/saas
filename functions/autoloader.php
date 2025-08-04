<?php
// Custom Class Autoloader
spl_autoload_register(function ($class_name) {
    // Check if the class belongs to our theme's namespace
    if (strpos($class_name, 'MoBooking\\') !== 0) {
        return false; // Not our class, skip
    }

    // Remove the root namespace prefix 'MoBooking\'
    $relative_class_name = substr($class_name, strlen('MoBooking\\'));

    // Convert namespace separators to directory separators
    $file_path = str_replace('\\', '/', $relative_class_name);

    // Prepend 'classes/' to the path
    $file_path = 'classes/' . $file_path;

    $file = MOBOOKING_THEME_DIR . $file_path . '.php';

    // Check if the file exists
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});
?>
