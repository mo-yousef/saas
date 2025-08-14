<?php
// Routing and Template Handling Refactored to BookingFormRouter class

// Initialize the new router
if (class_exists('MoBooking\\Classes\\Routes\\BookingFormRouter')) {
    new \MoBooking\Classes\Routes\BookingFormRouter();
}

// Theme activation/deactivation hook for flushing rewrite rules
function mobooking_flush_rewrite_rules_on_activation_deactivation() {
    // The BookingFormRouter hooks its rule registration to 'init'.
    // WordPress calls 'init' before 'flush_rewrite_rules' during theme activation.
    // So, just calling flush_rewrite_rules() here is sufficient.
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'mobooking_flush_rewrite_rules_on_activation_deactivation');
add_action('switch_theme', 'mobooking_flush_rewrite_rules_on_activation_deactivation'); // Flushes on deactivation too
?>
