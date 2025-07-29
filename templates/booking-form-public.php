<?php
get_header('booking');

$tenant_slug = get_query_var('mobooking_slug');
$tenant_user_id = \MoBooking\Classes\Routes\BookingFormRouter::get_user_id_by_slug($tenant_slug);

if (!$tenant_user_id) {
    echo '<div class="mobooking-error"><p>Booking form not found.</p></div>';
    return;
}

wp_enqueue_style('mobooking-booking-form', MOBOOKING_PLUGIN_URL . 'assets/css/booking-form.css');
wp_enqueue_script('mobooking-booking-form-public', MOBOOKING_PLUGIN_URL . 'assets/js/booking-form-public.js', ['jquery'], null, true);

wp_localize_script('mobooking-booking-form-public', 'mobooking_booking_form_params', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mobooking_booking_nonce'),
    'tenant_id' => $tenant_user_id,
]);
?>

<div id="mobooking-booking-form-container">
    <div id="mobooking-step-1" class="mobooking-step active">
        <h2>Select a Service</h2>
        <div id="mobooking-services-container"></div>
    </div>

    <div id="mobooking-step-2" class="mobooking-step">
        <h2>Configure Options</h2>
        <div id="mobooking-service-options-container"></div>
    </div>
</div>

<?php
get_footer('booking');
