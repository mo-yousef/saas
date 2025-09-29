<?php
/**
 * Booking Form Restrictions for Expired Subscriptions
 * @package NORDBOOKING
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add booking form restriction check to all pages with booking forms
 */
function nordbooking_add_booking_form_restrictions() {
    // Add JavaScript to check for booking forms on all pages
    add_action('wp_footer', function() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to check if a tenant's subscription is expired
            function checkTenantSubscription(tenantId, callback) {
                if (!tenantId) {
                    callback(false);
                    return;
                }
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'nordbooking_check_booking_form_status',
                        tenant_id: tenantId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        callback(data.data.disabled, data.data.message);
                    } else {
                        callback(false);
                    }
                })
                .catch(error => {
                    console.error('Error checking subscription status:', error);
                    callback(false);
                });
            }
            
            // Function to disable a booking form
            function disableBookingForm(form, message) {
                // Disable all form inputs
                const inputs = form.querySelectorAll('input, select, textarea, button');
                inputs.forEach(function(input) {
                    input.disabled = true;
                    input.style.opacity = '0.5';
                });
                
                // Add expired message
                const expiredMessage = document.createElement('div');
                expiredMessage.className = 'booking-form-expired-notice';
                expiredMessage.innerHTML = `
                    <div style="
                        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
                        border: 2px solid #fca5a5;
                        border-radius: 0.75rem;
                        padding: 1.5rem;
                        margin: 1rem 0;
                        text-align: center;
                        box-shadow: 0 4px 6px rgba(239, 68, 68, 0.1);
                    ">
                        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⚠️</div>
                        <h3 style="color: #dc2626; font-size: 1.25rem; font-weight: 600; margin: 0 0 0.5rem 0;">
                            Service Temporarily Unavailable
                        </h3>
                        <p style="color: #7f1d1d; margin: 0; line-height: 1.5;">
                            ${message || 'This service is temporarily unavailable. Please try again later.'}
                        </p>
                    </div>
                `;
                
                // Insert message at the top of the form
                form.insertBefore(expiredMessage, form.firstChild);
                
                // Add overlay to prevent interaction
                const overlay = document.createElement('div');
                overlay.style.cssText = `
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(255, 255, 255, 0.8);
                    z-index: 1000;
                    cursor: not-allowed;
                `;
                
                // Make form container relative if not already
                if (getComputedStyle(form).position === 'static') {
                    form.style.position = 'relative';
                }
                
                form.appendChild(overlay);
            }
            
            // Find all booking forms and check their status
            const bookingForms = document.querySelectorAll('form[id*="booking"], form[class*="booking"], .nordbooking-form, #nordbooking-booking-form, form[action*="booking"]');
            
            bookingForms.forEach(function(form) {
                // Try to get tenant ID from various sources
                let tenantId = 0;
                
                // Check form inputs
                const tenantInput = form.querySelector('input[name="tenant_id"]');
                if (tenantInput) {
                    tenantId = parseInt(tenantInput.value);
                }
                
                // Check URL parameters
                if (!tenantId) {
                    const urlParams = new URLSearchParams(window.location.search);
                    tenantId = parseInt(urlParams.get('tenant_id')) || 0;
                }
                
                // Check data attributes
                if (!tenantId) {
                    tenantId = parseInt(form.dataset.tenantId) || 0;
                }
                
                if (tenantId) {
                    checkTenantSubscription(tenantId, function(isExpired, message) {
                        if (isExpired) {
                            disableBookingForm(form, message);
                        }
                    });
                }
            });
            
            // Also check standalone booking buttons and links
            const bookingButtons = document.querySelectorAll('a[href*="booking"], button[class*="booking"], .book-now, .schedule-appointment, a[href*="tenant_id"]');
            bookingButtons.forEach(function(button) {
                let tenantId = 0;
                
                // Extract tenant ID from href
                if (button.href) {
                    const url = new URL(button.href, window.location.origin);
                    tenantId = parseInt(url.searchParams.get('tenant_id')) || 0;
                }
                
                // Check data attributes
                if (!tenantId) {
                    tenantId = parseInt(button.dataset.tenantId) || 0;
                }
                
                if (tenantId) {
                    checkTenantSubscription(tenantId, function(isExpired, message) {
                        if (isExpired) {
                            button.style.opacity = '0.5';
                            button.style.pointerEvents = 'none';
                            button.style.cursor = 'not-allowed';
                            
                            if (button.tagName === 'A') {
                                button.href = '#';
                            }
                            
                            button.addEventListener('click', function(e) {
                                e.preventDefault();
                                // Use NordbookingDialog if available, otherwise fallback to alert
                                if (typeof NordbookingDialog !== 'undefined') {
                                    new NordbookingDialog({
                                        title: 'Service Unavailable',
                                        content: message || 'This service is temporarily unavailable. Please try again later.',
                                        icon: 'warning',
                                        buttons: [{
                                            label: 'OK',
                                            class: 'primary',
                                            onClick: (dialog) => dialog.close()
                                        }]
                                    }).show();
                                } else {
                                    alert(message || 'This service is temporarily unavailable. Please try again later.');
                                }
                                return false;
                            });
                        }
                    });
                }
            });
        });
        </script>
        <?php
    });
}

add_action('wp', 'nordbooking_add_booking_form_restrictions');

/**
 * Disable booking form AJAX submissions for expired users
 */
function nordbooking_check_booking_submission_access() {
    // Check if this is a booking form submission
    if (!isset($_POST['action']) || $_POST['action'] !== 'nordbooking_create_booking') {
        return;
    }
    
    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    
    if (!$tenant_id) {
        return; // Can't determine tenant
    }
    
    // Check if tenant's subscription is expired
    if (nordbooking_is_subscription_expired($tenant_id)) {
        wp_send_json_error(array(
            'message' => nordbooking_get_booking_form_expired_message(),
            'expired' => true
        ), 403);
        exit;
    }
}

add_action('wp_ajax_nopriv_nordbooking_create_booking', 'nordbooking_check_booking_submission_access', 1);
add_action('wp_ajax_nordbooking_create_booking', 'nordbooking_check_booking_submission_access', 1);

/**
 * Add expired subscription notice to booking form shortcodes
 */
function nordbooking_booking_form_shortcode_filter($content, $tag, $attr) {
    if ($tag !== 'nordbooking_booking_form' && $tag !== 'booking_form') {
        return $content;
    }
    
    // Get tenant ID from shortcode attributes
    $tenant_id = isset($attr['tenant_id']) ? intval($attr['tenant_id']) : 0;
    
    if (!$tenant_id) {
        return $content; // Can't determine tenant
    }
    
    // Check if tenant's subscription is expired
    if (nordbooking_is_subscription_expired($tenant_id)) {
        $expired_message = '
        <div class="nordbooking-form-expired" style="
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 2px solid #fca5a5;
            border-radius: 0.75rem;
            padding: 2rem;
            margin: 2rem 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(239, 68, 68, 0.1);
        ">
            <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
            <h3 style="color: #dc2626; font-size: 1.5rem; font-weight: 600; margin: 0 0 1rem 0;">
                ' . esc_html__('Service Temporarily Unavailable', 'NORDBOOKING') . '
            </h3>
            <p style="color: #7f1d1d; margin: 0; line-height: 1.6; font-size: 1.1rem;">
                ' . esc_html(nordbooking_get_booking_form_expired_message()) . '
            </p>
        </div>';
        
        return $expired_message;
    }
    
    return $content;
}

add_filter('do_shortcode_tag', 'nordbooking_booking_form_shortcode_filter', 10, 3);