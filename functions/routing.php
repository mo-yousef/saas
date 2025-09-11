<?php
// Routing and Template Handling Refactored to BookingFormRouter class

// Initialize the new router
if (class_exists('NORDBOOKING\\Classes\\Routes\\BookingFormRouter')) {
    new \NORDBOOKING\Classes\Routes\BookingFormRouter();
}

// Theme activation/deactivation hook for flushing rewrite rules
function nordbooking_flush_rewrite_rules_on_activation_deactivation() {
    // The BookingFormRouter hooks its rule registration to 'init'.
    // WordPress calls 'init' before 'flush_rewrite_rules' during theme activation.
    // So, just calling flush_rewrite_rules() here is sufficient.
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'nordbooking_flush_rewrite_rules_on_activation_deactivation');
add_action('switch_theme', 'nordbooking_flush_rewrite_rules_on_activation_deactivation'); // Flushes on deactivation too

// The function 'nordbooking_enqueue_dashboard_scripts' was previously here.
// It has been removed as it was deprecated and causing a conflict
// with the main script enqueueing function in 'functions/theme-setup.php'.
// All script enqueueing is now handled by `nordbooking_scripts()` in that file.
?>
