<?php
// --- Custom Email Template Functions ---

/**
 * Set email content type to HTML.
 */
add_filter( 'wp_mail_content_type', function() {
    return 'text/html';
});

/**
 * Set a default email from name.
 */
add_filter( 'wp_mail_from_name', function( $original_email_from ) {
    // You can customize this, e.g., get_bloginfo('name')
    return get_bloginfo('name');
});

/**
 * Set a default email from address.
 * It's good practice to use an email address from your site's domain.
 */
add_filter( 'wp_mail_from', function( $original_email_address ) {
    $domain = wp_parse_url(home_url(), PHP_URL_HOST);
    if (strpos($domain, 'www.') === 0) {
        $domain = substr($domain, 4);
    }
    $default_from_email = 'wordpress@' . $domain;
    // Check if the original email address is the default WordPress one.
    // If it is, replace it. Otherwise, keep the potentially custom one.
    if ($original_email_address === 'wordpress@' . $domain || $original_email_address === 'wordpress@localhost') {
        return $default_from_email;
    }
    return $original_email_address; // Keep if it was already customized
});


/**
 * It is recommended to handle email templating directly when calling wp_mail
 * for more flexibility and to avoid conflicts. The email content is now
 * constructed and styled within the MoBooking\Classes\Notifications class.
 */

// --- End Custom Email Template Functions ---
?>
