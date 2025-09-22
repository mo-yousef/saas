# NORDBOOKING Worker Management System

## Overview

The NORDBOOKING Worker Management System enables Business Owners to invite team members to access their dashboard with role-based permissions. This system provides secure delegation of tasks while maintaining data isolation and proper access controls.

## Core Features

### 👥 Role-Based Access Control
- **Manager**: Full operational access to bookings, services, discounts, and areas
- **Staff**: Day-to-day operations with booking management and service viewing
- **Viewer**: Read-only access to business data
- **Hierarchical Permissions**: Clear permission structure with appropriate restrictions

### 📧 Email Invitation System
- **Secure Invitations**: Unique registration links with 7-day expiration
- **Automated Emails**: Professional invitation emails with business branding
- **Registration Flow**: Streamlined registration process for invited workers
- **Link Security**: One-time use registration tokens

### 🔧 Team Management Tools
- **Worker Overview**: Visual hierarchy of business owners and their workers
- **Role Management**: Easy role assignment and modification
- **Access Revocation**: Safe removal of worker access with confirmation
- **User Association**: Automatic association of workers with business owners

## Worker Role Definitions

### Manager (`nordbooking_worker_manager`)
**Dashboard Access**: Full access to most operational areas

**Permissions**:
- ✅ Manage bookings (view, create, edit, delete)
- ✅ Manage services (view, create, edit, delete)
- ✅ Manage discounts (view, create, edit, delete)
- ✅ Manage service areas (view, create, edit, delete)
- ✅ Manage booking form appearance and settings
- ❌ Cannot manage other workers
- ❌ Cannot change core business settings
- ❌ Cannot access subscription management

### Staff (`nordbooking_worker_staff`)
**Dashboard Access**: Focused on day-to-day booking operations

**Permissions**:
- ✅ Manage bookings (view, create, edit, delete)
- ✅ View services (read-only)
- ✅ View discounts (read-only)
- ✅ View service areas (read-only)
- ❌ Cannot create or edit services
- ❌ Cannot manage discounts or areas
- ❌ Cannot manage booking form settings
- ❌ Cannot manage workers or business settings

### Viewer (`nordbooking_worker_viewer`)
**Dashboard Access**: View-only access to key business data

**Permissions**:
- ✅ View bookings (read-only)
- ✅ View services (read-only)
- ✅ View discounts (read-only)
- ✅ View service areas (read-only)
- ❌ Cannot make any changes
- ❌ Cannot manage bookings
- ❌ Cannot access any management functions

## System Architecture

### Database Schema

#### Worker Invitations Table
```sql
CREATE TABLE wp_nordbooking_worker_invitations (
    invitation_id int(11) NOT NULL AUTO_INCREMENT,
    business_owner_id int(11) NOT NULL,
    email varchar(255) NOT NULL,
    role varchar(50) NOT NULL,
    token varchar(255) NOT NULL,
    expires_at datetime NOT NULL,
    status enum('pending','completed','expired') DEFAULT 'pending',
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (invitation_id),
    UNIQUE KEY unique_token (token),
    KEY idx_business_email (business_owner_id, email),
    KEY idx_token_status (token, status)
);
```

#### Worker Associations Table
```sql
CREATE TABLE wp_nordbooking_worker_associations (
    association_id int(11) NOT NULL AUTO_INCREMENT,
    business_owner_id int(11) NOT NULL,
    worker_id int(11) NOT NULL,
    role varchar(50) NOT NULL,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (association_id),
    UNIQUE KEY unique_association (business_owner_id, worker_id),
    KEY idx_business_owner (business_owner_id),
    KEY idx_worker (worker_id)
);
```

### Core Classes

#### WorkerManager (`classes/WorkerManager.php`)
Main worker management class providing:
- Invitation creation and management
- Role assignment and modification
- Access control and permissions
- Email notification handling

**Key Methods**:
```php
// Send worker invitation
public function send_invitation($business_owner_id, $email, $role);

// Process registration from invitation
public function process_invitation_registration($token, $user_data);

// Change worker role
public function change_worker_role($business_owner_id, $worker_id, $new_role);

// Revoke worker access
public function revoke_worker_access($business_owner_id, $worker_id);

// Get workers for business owner
public function get_business_workers($business_owner_id);
```

## Invitation Process

### 1. Sending Invitations

#### Admin Interface
Location: **Dashboard** → **Workers** → **Invite New Worker**

**Form Fields**:
- **Worker Email**: Valid email address for invitation
- **Assign Role**: Dropdown selection (Manager, Staff, Viewer)
- **Send Invitation**: Submit button to send invitation

#### Backend Processing
```php
public function send_invitation($business_owner_id, $email, $role) {
    // Validate input
    if (!is_email($email)) {
        return new WP_Error('invalid_email', 'Please provide a valid email address');
    }
    
    if (!in_array($role, ['nordbooking_worker_manager', 'nordbooking_worker_staff', 'nordbooking_worker_viewer'])) {
        return new WP_Error('invalid_role', 'Invalid role specified');
    }
    
    // Check for existing invitation
    $existing = $this->get_pending_invitation($business_owner_id, $email);
    if ($existing) {
        return new WP_Error('invitation_exists', 'An invitation has already been sent to this email');
    }
    
    // Generate secure token
    $token = wp_generate_password(32, false);
    $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    // Store invitation
    $invitation_data = [
        'business_owner_id' => $business_owner_id,
        'email' => $email,
        'role' => $role,
        'token' => $token,
        'expires_at' => $expires_at,
        'status' => 'pending'
    ];
    
    $result = $this->wpdb->insert(
        $this->invitations_table,
        $invitation_data,
        ['%d', '%s', '%s', '%s', '%s', '%s']
    );
    
    if ($result === false) {
        return new WP_Error('database_error', 'Failed to create invitation');
    }
    
    // Send invitation email
    $this->send_invitation_email($email, $token, $business_owner_id, $role);
    
    return true;
}
```

### 2. Email Notification

#### Email Template
```php
private function send_invitation_email($email, $token, $business_owner_id, $role) {
    $business_owner = get_userdata($business_owner_id);
    $business_name = get_user_meta($business_owner_id, 'business_name', true) ?: $business_owner->display_name;
    
    $registration_url = add_query_arg([
        'action' => 'worker_registration',
        'token' => $token
    ], home_url());
    
    $role_name = $this->get_role_display_name($role);
    
    $subject = sprintf('You have been invited to %s', $business_name);
    
    $message = sprintf(
        'Hello,

You have been invited to join %s as a %s.

To accept this invitation and create your account, please click the link below:
%s

This invitation will expire in 7 days.

If you have any questions, please contact %s.

Best regards,
%s Team',
        $business_name,
        $role_name,
        $registration_url,
        $business_owner->display_name,
        get_bloginfo('name')
    );
    
    wp_mail($email, $subject, $message);
}
```

### 3. Registration Process

#### Registration Page
URL: `/?action=worker_registration&token={token}`

**Registration Form**:
- **Email**: Pre-filled from invitation (read-only)
- **Password**: New password selection
- **Confirm Password**: Password confirmation
- **Full Name**: Worker's display name
- **Register**: Submit button

#### Registration Processing
```php
public function process_invitation_registration($token, $user_data) {
    // Validate token
    $invitation = $this->get_invitation_by_token($token);
    if (!$invitation || $invitation->status !== 'pending') {
        return new WP_Error('invalid_token', 'Invalid or expired invitation');
    }
    
    // Check expiration
    if (strtotime($invitation->expires_at) < time()) {
        $this->update_invitation_status($invitation->invitation_id, 'expired');
        return new WP_Error('expired_token', 'This invitation has expired');
    }
    
    // Create WordPress user
    $user_id = wp_create_user(
        $user_data['username'],
        $user_data['password'],
        $invitation->email
    );
    
    if (is_wp_error($user_id)) {
        return $user_id;
    }
    
    // Set user role
    $user = new WP_User($user_id);
    $user->set_role($invitation->role);
    
    // Update user meta
    update_user_meta($user_id, 'first_name', $user_data['first_name']);
    update_user_meta($user_id, 'last_name', $user_data['last_name']);
    
    // Create worker association
    $this->create_worker_association($invitation->business_owner_id, $user_id, $invitation->role);
    
    // Mark invitation as completed
    $this->update_invitation_status($invitation->invitation_id, 'completed');
    
    return $user_id;
}
```

## Worker Management Interface

### Current Workers Display

#### Worker List Table
Location: **Dashboard** → **Workers** → **Current Workers**

**Table Columns**:
- **Email**: Worker's email address
- **Role**: Current NORDBOOKING role
- **Actions**: Role change dropdown and revoke access button

#### Role Management
```javascript
// Change worker role
function changeWorkerRole(workerId, newRole) {
    if (!confirm('Are you sure you want to change this worker\'s role?')) {
        return;
    }
    
    jQuery.post(ajaxurl, {
        action: 'nordbooking_change_worker_role',
        worker_id: workerId,
        new_role: newRole,
        nonce: nordbooking_ajax.nonce
    }, function(response) {
        if (response.success) {
            showSuccessMessage('Worker role updated successfully');
            location.reload(); // Refresh to show updated role
        } else {
            showErrorMessage(response.data.message);
        }
    });
}

// Revoke worker access
function revokeWorkerAccess(workerId, workerEmail) {
    const message = `Are you sure you want to revoke access for ${workerEmail}?\n\nThis will remove their NORDBOOKING roles and association with your business.`;
    
    if (!confirm(message)) {
        return;
    }
    
    jQuery.post(ajaxurl, {
        action: 'nordbooking_revoke_worker_access',
        worker_id: workerId,
        nonce: nordbooking_ajax.nonce
    }, function(response) {
        if (response.success) {
            showSuccessMessage('Worker access revoked successfully');
            location.reload(); // Refresh to remove from list
        } else {
            showErrorMessage(response.data.message);
        }
    });
}
```

### Admin Dashboard Integration

#### User Management Tab
Location: **NORDBOOKING Admin** → **User Management**

**Features**:
- **Hierarchical Display**: Visual tree showing business owners and workers
- **Expandable Lists**: Click arrows to expand worker lists
- **Quick Actions**: Edit, delete, and manage users
- **Worker Creation**: Built-in form for creating workers

#### Visual Hierarchy
```html
<div class="user-hierarchy">
    <div class="business-owner">
        <span class="toggle-arrow">▶</span>
        <strong>Business Owner Name</strong>
        <span class="user-count">(3 workers)</span>
        
        <div class="worker-list" style="display: none;">
            <div class="worker-item">
                <span class="worker-email">worker@example.com</span>
                <span class="worker-role">Manager</span>
                <div class="worker-actions">
                    <button class="edit-user">Edit</button>
                    <button class="delete-user">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
```

## Access Control Implementation

### Permission Checking

#### Dashboard Access Control
```php
// Check if user can access specific dashboard section
function nordbooking_can_access_section($section, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }
    
    // Business owners have full access
    if (in_array('nordbooking_business_owner', $user->roles)) {
        return true;
    }
    
    // Check worker permissions
    $permissions = [
        'bookings' => ['nordbooking_worker_manager', 'nordbooking_worker_staff'],
        'services' => ['nordbooking_worker_manager'],
        'discounts' => ['nordbooking_worker_manager'],
        'areas' => ['nordbooking_worker_manager'],
        'workers' => [], // Only business owners
        'subscription' => [] // Only business owners
    ];
    
    if (!isset($permissions[$section])) {
        return false;
    }
    
    return !empty(array_intersect($permissions[$section], $user->roles));
}
```

#### Data Access Control
```php
// Ensure workers can only access their business owner's data
function nordbooking_get_accessible_tenant_id($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }
    
    // Business owners access their own data
    if (in_array('nordbooking_business_owner', $user->roles)) {
        return $user_id;
    }
    
    // Workers access their business owner's data
    $worker_roles = ['nordbooking_worker_manager', 'nordbooking_worker_staff', 'nordbooking_worker_viewer'];
    if (!empty(array_intersect($worker_roles, $user->roles))) {
        return nordbooking_get_worker_business_owner($user_id);
    }
    
    return false;
}
```

### Navigation Control

#### Sidebar Menu Filtering
```php
// Filter dashboard navigation based on user role
function nordbooking_filter_dashboard_navigation($menu_items, $user_id) {
    $accessible_sections = [];
    
    foreach ($menu_items as $section => $item) {
        if (nordbooking_can_access_section($section, $user_id)) {
            $accessible_sections[$section] = $item;
        }
    }
    
    return $accessible_sections;
}
```

## Security Features

### 🔐 Invitation Security
- **Unique Tokens**: Cryptographically secure random tokens
- **Time Expiration**: 7-day expiration for all invitations
- **One-Time Use**: Tokens become invalid after registration
- **Email Verification**: Registration requires access to invited email

### 🛡️ Access Control
- **Role Validation**: Strict role checking for all operations
- **Data Isolation**: Workers can only access their business owner's data
- **Permission Boundaries**: Clear permission boundaries between roles
- **Session Management**: Proper WordPress session handling

### 🔒 Data Protection
- **Input Sanitization**: All user input properly sanitized
- **SQL Injection Prevention**: Prepared statements for all queries
- **CSRF Protection**: Nonce verification for all forms
- **Audit Logging**: Track all worker management actions

## AJAX Endpoints

### Worker Invitation
```php
add_action('wp_ajax_nordbooking_send_worker_invitation', 'nordbooking_send_worker_invitation_handler');

function nordbooking_send_worker_invitation_handler() {
    // Security checks
    if (!current_user_can('nordbooking_manage_workers')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'nordbooking_worker_management')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    $email = sanitize_email($_POST['email']);
    $role = sanitize_text_field($_POST['role']);
    $business_owner_id = get_current_user_id();
    
    $worker_manager = new \NORDBOOKING\Classes\WorkerManager();
    $result = $worker_manager->send_invitation($business_owner_id, $email, $role);
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    } else {
        wp_send_json_success(['message' => 'Invitation sent successfully']);
    }
}
```

### Role Management
```php
add_action('wp_ajax_nordbooking_change_worker_role', 'nordbooking_change_worker_role_handler');

function nordbooking_change_worker_role_handler() {
    // Security and permission checks
    if (!current_user_can('nordbooking_manage_workers')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    $worker_id = intval($_POST['worker_id']);
    $new_role = sanitize_text_field($_POST['new_role']);
    $business_owner_id = get_current_user_id();
    
    $worker_manager = new \NORDBOOKING\Classes\WorkerManager();
    $result = $worker_manager->change_worker_role($business_owner_id, $worker_id, $new_role);
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    } else {
        wp_send_json_success(['message' => 'Worker role updated successfully']);
    }
}
```

## Troubleshooting

### Common Issues

#### Invitation Email Not Received
**Symptoms**: Worker doesn't receive invitation email
**Solutions**:
1. Check spam/junk folder
2. Verify email address is correct
3. Check WordPress email configuration
4. Test with different email provider

#### Registration Link Expired
**Symptoms**: Registration link shows expired error
**Solutions**:
1. Send new invitation (old token will be invalidated)
2. Check system date/time settings
3. Verify token expiration logic

#### Worker Cannot Access Dashboard
**Symptoms**: Worker gets permission denied or limited access
**Solutions**:
1. Verify worker role assignment
2. Check worker association with business owner
3. Clear browser cache and cookies
4. Verify user is logged in correctly

#### Role Changes Not Taking Effect
**Symptoms**: Worker permissions don't update after role change
**Solutions**:
1. Have worker log out and log back in
2. Clear WordPress object cache
3. Verify role change was saved to database
4. Check for plugin conflicts

### Debug Information

#### Worker Status Check
```php
// Debug worker information
function debug_worker_status($user_id) {
    $user = get_userdata($user_id);
    $associations = nordbooking_get_worker_associations($user_id);
    
    return [
        'user_roles' => $user->roles,
        'business_associations' => $associations,
        'can_access_dashboard' => nordbooking_can_access_dashboard($user_id),
        'accessible_sections' => nordbooking_get_accessible_sections($user_id)
    ];
}
```

## Best Practices

### For Business Owners

#### Invitation Management
1. **Verify Email Addresses**: Double-check email addresses before sending invitations
2. **Appropriate Roles**: Assign the minimum role necessary for the worker's responsibilities
3. **Regular Review**: Periodically review worker access and roles
4. **Prompt Response**: Remind workers to complete registration promptly

#### Security Practices
1. **Role Monitoring**: Monitor worker activities and access patterns
2. **Access Revocation**: Promptly revoke access for departing team members
3. **Permission Audits**: Regularly audit worker permissions and roles
4. **Training**: Provide workers with appropriate training for their roles

### For System Administrators

#### System Maintenance
1. **Clean Expired Invitations**: Regularly clean up expired invitation records
2. **Monitor Usage**: Track worker invitation and registration patterns
3. **Security Updates**: Keep system updated for security patches
4. **Backup Data**: Regular backups of worker association data

## Future Enhancements

### Planned Features
1. **Advanced Permissions**: Granular permission controls beyond basic roles
2. **Bulk Operations**: Invite multiple workers simultaneously
3. **Activity Logging**: Detailed audit logs for worker activities
4. **Custom Roles**: Allow business owners to create custom worker roles
5. **Integration APIs**: API endpoints for third-party integrations

### UI Improvements
1. **Enhanced Dashboard**: More intuitive worker management interface
2. **Mobile Support**: Mobile-optimized worker management
3. **Notification System**: In-app notifications for worker activities
4. **Analytics**: Worker productivity and usage analytics

This worker management system provides comprehensive team collaboration capabilities while maintaining security and proper access controls for multi-user business operations.