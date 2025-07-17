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
 * Wrap email content with custom HTML template.
 */
add_filter( 'wp_mail', function( $args ) {
    error_log('[MoBooking Debug] Custom wp_mail filter triggered. Email to: ' . $args['to']); // DEBUG LINE
    $template_path = get_stylesheet_directory() . '/templates/email/default-email-template.php';

    if ( file_exists( $template_path ) ) {
        error_log('[MoBooking Debug] Email template file found at: ' . $template_path); // DEBUG LINE
        $email_template = file_get_contents( $template_path );

        // Replace placeholders
        // Header Content - Example: Site Logo and Name
        $site_logo_url = function_exists('get_custom_logo') ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : '';
        $header_content = '';
        if ($site_logo_url) {
            $header_content .= '<img src="' . esc_url($site_logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '" style="max-height:50px; margin-bottom:10px;" class="site-logo" />';
            $header_content .= '<h1 style="color:#ffffff; font-size:24px; margin:0; font-weight:normal;"><a href="' . esc_url(home_url()) . '" style="color:#ffffff; text-decoration:none;">' . esc_html(get_bloginfo('name')) . '</a></h1>';
        } else {
            $header_content .= '<h1 style="color:#ffffff; font-size:24px; margin:0; font-weight:normal;"><a href="' . esc_url(home_url()) . '" style="color:#ffffff; text-decoration:none;">' . esc_html(get_bloginfo('name')) . '</a></h1>';
        }
        $email_template = str_replace( '%%EMAIL_HEADER_CONTENT%%', $header_content, $email_template );

        // Main Content
        // Convert line breaks to <br> for HTML display if the content is plain text
        $message_content = nl2br( $args['message'] );
        $email_template = str_replace( '%%EMAIL_CONTENT%%', $message_content, $email_template );

        // Footer Content - Example: Copyright and Site Link
        $footer_content = '&copy; ' . date('Y') . ' <a href="' . esc_url(home_url('/')) . '" style="color:#0073aa;">' . esc_html(get_bloginfo('name')) . '</a>. ' . __('All rights reserved.', 'mobooking');
        $email_template = str_replace( '%%EMAIL_FOOTER_CONTENT%%', $footer_content, $email_template );

        // Blog name for title tag
        $email_template = str_replace( '%%BLOG_NAME%%', esc_html(get_bloginfo('name')), $email_template );


        $args['message'] = $email_template;
    }

    return $args;
}, 10, 1 );

// --- End Custom Email Template Functions ---
?>
