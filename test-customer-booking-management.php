<?php
/**
 * Test Customer Booking Management System
 * 
 * This file demonstrates how to generate customer booking management links
 * and shows the booking management functionality.
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Ensure we have the necessary classes
if (!class_exists('NORDBOOKING\Classes\Database') || !class_exists('NORDBOOKING\Classes\Bookings')) {
    die('NORDBOOKING classes not found. Please ensure the plugin is properly installed.');
}

get_header();
?>

<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 2rem 1rem;">
    <h1 style="text-align: center; margin-bottom: 2rem; color: #333;">Customer Booking Management System Test</h1>

    <div style="background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="color: #007cba; margin-bottom: 1.5rem;">System Overview</h2>
        <p style="margin-bottom: 1rem; line-height: 1.6;">
            The Customer Booking Management System allows customers to:
        </p>
        <ul style="margin-bottom: 1.5rem; padding-left: 2rem; line-height: 1.8;">
            <li><strong>Reschedule bookings:</strong> Select a new date and time for their service</li>
            <li><strong>Cancel bookings:</strong> Cancel their booking with an optional reason</li>
            <li><strong>View booking details:</strong> See all their booking information in one place</li>
        </ul>
        
        <div style="background: #e3f2fd; padding: 1rem; border-radius: 8px; border-left: 4px solid #2196f3;">
            <h3 style="margin: 0 0 0.5rem 0; color: #1976d2;">How it works:</h3>
            <ol style="margin: 0; padding-left: 1.5rem; line-height: 1.6;">
                <li>When a booking is created, a unique secure link is generated</li>
                <li>This link is included in the booking confirmation email</li>
                <li>Customers can click the link to access their booking management page</li>
                <li>All changes are automatically updated in the business owner's dashboard</li>
                <li>Email notifications are sent to the business owner for any changes</li>
            </ol>
        </div>
    </div>

    <?php
    // Get recent bookings to demonstrate the system
    global $wpdb;
    $bookings_table = \NORDBOOKING\Classes\Database::get_table_name('bookings');
    
    $recent_bookings = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $bookings_table 
         WHERE status IN ('pending', 'confirmed') 
         ORDER BY created_at DESC 
         LIMIT 5"
    ));
    ?>

    <div style="background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="color: #007cba; margin-bottom: 1.5rem;">Recent Bookings with Management Links</h2>
        
        <?php if (!empty($recent_bookings)): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 1rem; text-align: left; border-bottom: 2px solid #dee2e6;">Reference</th>
                            <th style="padding: 1rem; text-align: left; border-bottom: 2px solid #dee2e6;">Customer</th>
                            <th style="padding: 1rem; text-align: left; border-bottom: 2px solid #dee2e6;">Date & Time</th>
                            <th style="padding: 1rem; text-align: left; border-bottom: 2px solid #dee2e6;">Status</th>
                            <th style="padding: 1rem; text-align: left; border-bottom: 2px solid #dee2e6;">Management Link</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <?php
                            $management_link = \NORDBOOKING\Classes\Bookings::generate_customer_booking_link(
                                $booking->booking_id, 
                                $booking->customer_email
                            );
                            ?>
                            <tr style="border-bottom: 1px solid #dee2e6;">
                                <td style="padding: 1rem; font-weight: 600; color: #007cba;">
                                    <?php echo esc_html($booking->booking_reference); ?>
                                </td>
                                <td style="padding: 1rem;">
                                    <div style="font-weight: 600;"><?php echo esc_html($booking->customer_name); ?></div>
                                    <div style="font-size: 0.875rem; color: #666;"><?php echo esc_html($booking->customer_email); ?></div>
                                </td>
                                <td style="padding: 1rem;">
                                    <div><?php echo date('M j, Y', strtotime($booking->booking_date)); ?></div>
                                    <div style="font-size: 0.875rem; color: #666;"><?php echo date('g:i A', strtotime($booking->booking_time)); ?></div>
                                </td>
                                <td style="padding: 1rem;">
                                    <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 500; background: #e3f2fd; color: #1976d2;">
                                        <?php echo esc_html(ucfirst($booking->status)); ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem;">
                                    <a href="<?php echo esc_url($management_link); ?>" 
                                       style="display: inline-block; padding: 0.5rem 1rem; background: #007cba; color: white; text-decoration: none; border-radius: 6px; font-size: 0.875rem; font-weight: 600;"
                                       target="_blank">
                                        Manage Booking
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="background: #fff3cd; padding: 1rem; border-radius: 8px; border-left: 4px solid #ffc107; margin-top: 1rem;">
                <h4 style="margin: 0 0 0.5rem 0; color: #856404;">Testing Instructions:</h4>
                <p style="margin: 0; color: #856404; line-height: 1.6;">
                    Click on any "Manage Booking" link above to test the customer booking management functionality. 
                    You can reschedule or cancel the booking, and the changes will be reflected in the business owner's dashboard.
                </p>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px;">
                <h3 style="margin: 0 0 1rem 0; color: #666;">No Recent Bookings Found</h3>
                <p style="margin: 0; color: #666;">
                    Create some test bookings first to see the customer booking management links in action.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <div style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="color: #007cba; margin-bottom: 1.5rem;">Technical Implementation</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin: 0 0 1rem 0; color: #333;">Security Features</h3>
                <ul style="margin: 0; padding-left: 1.5rem; line-height: 1.6;">
                    <li>Secure token-based authentication</li>
                    <li>Hash-based verification using WordPress salt</li>
                    <li>Time-safe token comparison</li>
                    <li>CSRF protection with nonces</li>
                </ul>
            </div>
            
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin: 0 0 1rem 0; color: #333;">User Experience</h3>
                <ul style="margin: 0; padding-left: 1.5rem; line-height: 1.6;">
                    <li>Mobile-responsive design</li>
                    <li>Business branding integration</li>
                    <li>Real-time form validation</li>
                    <li>Success/error messaging</li>
                </ul>
            </div>
            
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin: 0 0 1rem 0; color: #333;">Business Integration</h3>
                <ul style="margin: 0; padding-left: 1.5rem; line-height: 1.6;">
                    <li>Automatic dashboard updates</li>
                    <li>Email notifications to business owner</li>
                    <li>Booking history tracking</li>
                    <li>Status change logging</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .container {
        padding: 1rem 0.5rem !important;
    }
    
    table {
        font-size: 0.875rem;
    }
    
    th, td {
        padding: 0.5rem !important;
    }
}
</style>

<?php get_footer(); ?>