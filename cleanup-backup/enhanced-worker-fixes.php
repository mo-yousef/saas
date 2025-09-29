<?php
/**
 * Enhanced Worker Management Fixes
 * 
 * This file contains fixes and improvements for the worker management system.
 * Apply these changes to ensure both "Invite New Worker via Email" and "Add Worker Directly" work correctly.
 */

// This file should be integrated into the existing Auth class

class EnhancedWorkerManagement {
    
    /**
     * Enhanced invitation email sending with better error handling
     */
    public function send_enhanced_invitation($business_owner_id, $worker_email, $role) {
        // Validate inputs
        if (!is_email($worker_email)) {
            return new WP_Error('invalid_email', __('Please provide a valid email address.', 'NORDBOOKING'));
        }
        
        if ($role !== \NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF) {
            return new WP_Error('invalid_role', __('Invalid role specified.', 'NORDBOOKING'));
        }
        
        // Check if email already exists
        if (email_exists($worker_email)) {
            return new WP_Error('email_exists', __('This email address is already registered.', 'NORDBOOKING'));
        }
        
        // Check for existing pending invitation
        $existing_invitations = $this->get_pending_invitations_for_email($worker_email);
        if (!empty($existing_invitations)) {
            return new WP_Error('invitation_exists', __('An invitation has already been sent to this email address.', 'NORDBOOKING'));
        }
        
        // Generate secure token
        $token = wp_generate_password(32, false);
        $expiration = 7 * DAY_IN_SECONDS; // 7 days
        
        $invitation_data = [
            'inviter_id'    => $business_owner_id,
            'worker_email'  => $worker_email,
            'assigned_role' => $role,
            'timestamp'     => time(),
        ];
        
        // Store invitation
        $transient_key = 'nordbooking_invitation_' . $token;
        $stored = set_transient($transient_key, $invitation_data, $expiration);
        
        if (!$stored) {
            return new WP_Error('storage_failed', __('Could not save invitation. Please try again.', 'NORDBOOKING'));
        }
        
        // Send email
        $registration_link = add_query_arg('invitation_token', $token, home_url('/register/'));
        $inviter_user = get_userdata($business_owner_id);
        $inviter_name = $inviter_user ? $inviter_user->display_name : get_bloginfo('name');
        
        $notifications = new \NORDBOOKING\Classes\Notifications();
        $email_sent = $notifications->send_invitation_email($worker_email, $role, $inviter_name, $registration_link);
        
        if (!$email_sent) {
            // Clean up the transient if email fails
            delete_transient($transient_key);
            return new WP_Error('email_failed', __('Failed to send invitation email. Please check your email configuration.', 'NORDBOOKING'));
        }
        
        return true;
    }
    
    /**
     * Get pending invitations for an email address
     */
    private function get_pending_invitations_for_email($email) {
        global $wpdb;
        
        // Get all transients that match our invitation pattern
        $transients = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE %s AND option_name LIKE %s",
            '_transient_nordbooking_invitation_%',
            '%'
        ));
        
        $pending_invitations = [];
        foreach ($transients as $transient) {
            $data = maybe_unserialize($transient->option_value);
            if (is_array($data) && isset($data['worker_email']) && $data['worker_email'] === $email) {
                $pending_invitations[] = $data;
            }
        }
        
        return $pending_invitations;
    }
    
    /**
     * Enhanced direct worker creation with better validation
     */
    public function create_worker_directly($business_owner_id, $email, $password, $first_name = '', $last_name = '') {
        // Validate inputs
        if (!is_email($email)) {
            return new WP_Error('invalid_email', __('Please provide a valid email address.', 'NORDBOOKING'));
        }
        
        if (strlen($password) < 8) {
            return new WP_Error('weak_password', __('Password must be at least 8 characters long.', 'NORDBOOKING'));
        }
        
        if (email_exists($email)) {
            return new WP_Error('email_exists', __('This email address is already registered.', 'NORDBOOKING'));
        }
        
        // Create user
        $user_data = [
            'user_login' => $email,
            'user_email' => $email,
            'user_pass'  => $password,
            'role'       => \NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF,
            'first_name' => $first_name,
            'last_name'  => $last_name,
        ];
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Associate with business owner
        update_user_meta($user_id, \NORDBOOKING\Classes\Auth::META_KEY_OWNER_ID, $business_owner_id);
        
        // Send welcome email
        $this->send_worker_welcome_email($user_id, $email, $password, $business_owner_id);
        
        return $user_id;
    }
    
    /**
     * Send welcome email to newly created worker
     */
    private function send_worker_welcome_email($user_id, $email, $password, $business_owner_id) {
        $business_owner = get_userdata($business_owner_id);
        $business_name = $business_owner ? $business_owner->display_name : get_bloginfo('name');
        
        $subject = sprintf(__('Your Worker Account at %s', 'NORDBOOKING'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Hello,

A worker account has been created for you at %s by %s.

Your login details:
Email: %s
Password: %s

You can log in here: %s

We recommend changing your password after your first login.

Best regards,
%s Team', 'NORDBOOKING'),
            get_bloginfo('name'),
            $business_name,
            $email,
            $password,
            home_url('/login/'),
            get_bloginfo('name')
        );
        
        wp_mail($email, $subject, $message);
    }
    
    /**
     * Enhanced worker access revocation
     */
    public function revoke_worker_access($business_owner_id, $worker_id) {
        // Verify worker belongs to this business owner
        $actual_owner_id = get_user_meta($worker_id, \NORDBOOKING\Classes\Auth::META_KEY_OWNER_ID, true);
        if ((int)$actual_owner_id !== $business_owner_id) {
            return new WP_Error('unauthorized', __('This worker is not associated with your business.', 'NORDBOOKING'));
        }
        
        $worker_user = get_userdata($worker_id);
        if (!$worker_user) {
            return new WP_Error('user_not_found', __('Worker user not found.', 'NORDBOOKING'));
        }
        
        // Remove NORDBOOKING roles
        $worker_user->remove_role(\NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF);
        
        // Remove association
        delete_user_meta($worker_id, \NORDBOOKING\Classes\Auth::META_KEY_OWNER_ID);
        
        // Set to subscriber if no other roles
        if (empty($worker_user->roles)) {
            $worker_user->set_role('subscriber');
        }
        
        return true;
    }
}

/**
 * JavaScript fixes for the worker management page
 */
?>
<script>
// Enhanced form validation
function validateWorkerEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function validateWorkerPassword(password) {
    return password && password.length >= 8;
}

// Enhanced error handling
function showWorkerError(message, formId) {
    const alertArea = document.getElementById(formId + '-feedback');
    if (alertArea) {
        alertArea.className = 'NORDBOOKING-inline-alert NORDBOOKING-inline-alert-error';
        alertArea.querySelector('.NORDBOOKING-inline-alert-message').textContent = message;
        alertArea.style.display = 'block';
    }
}

function showWorkerSuccess(message, formId) {
    const alertArea = document.getElementById(formId + '-feedback');
    if (alertArea) {
        alertArea.className = 'NORDBOOKING-inline-alert NORDBOOKING-inline-alert-success';
        alertArea.querySelector('.NORDBOOKING-inline-alert-message').textContent = message;
        alertArea.style.display = 'block';
        
        // Hide after 5 seconds
        setTimeout(() => {
            alertArea.style.display = 'none';
        }, 5000);
    }
}

// Enhanced AJAX error handling
function handleWorkerAjaxError(xhr, status, error) {
    console.error('Worker AJAX Error:', {xhr, status, error});
    
    let message = 'An error occurred. Please try again.';
    
    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
        message = xhr.responseJSON.data.message;
    } else if (xhr.responseText) {
        try {
            const response = JSON.parse(xhr.responseText);
            if (response.data && response.data.message) {
                message = response.data.message;
            }
        } catch (e) {
            // Use default message
        }
    }
    
    return message;
}
</script>

<style>
/* Enhanced styling for worker management */
.NORDBOOKING-inline-alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.NORDBOOKING-inline-alert-success {
    background-color: #f0f9ff;
    border: 1px solid #0ea5e9;
    color: #0c4a6e;
}

.NORDBOOKING-inline-alert-error {
    background-color: #fef2f2;
    border: 1px solid #ef4444;
    color: #991b1b;
}

.NORDBOOKING-inline-alert-icon svg {
    width: 16px;
    height: 16px;
}

.NORDBOOKING-inline-alert-success .NORDBOOKING-inline-alert-icon-error,
.NORDBOOKING-inline-alert-error .NORDBOOKING-inline-alert-icon-success {
    display: none;
}

.NORDBOOKING-loading {
    opacity: 0.7;
    pointer-events: none;
}

.NORDBOOKING-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<?php
/**
 * Integration instructions:
 * 
 * 1. Add the EnhancedWorkerManagement methods to the existing Auth class
 * 2. Update the AJAX handlers to use the enhanced methods
 * 3. Include the JavaScript and CSS fixes in the worker page
 * 4. Test both invitation and direct creation workflows
 */
?>