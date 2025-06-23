<?php
/**
 * Dashboard Page: Overview
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Enqueue Chart.js and other required scripts
wp_enqueue_script('chart-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js', array(), '3.9.1', true);

// Get current user and initialize managers
$user = wp_get_current_user();
$current_user_id = get_current_user_id();

// Initialize managers (assuming they exist in your structure)
$services_manager = new \MoBooking\Classes\Services();
$discounts_manager = new \MoBooking\Classes\Discounts($current_user_id);
$notifications_manager = new \MoBooking\Classes\Notifications();
$bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);

// Get currency symbol
$currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol('USD');
if ($current_user_id && isset($GLOBALS['mobooking_settings_manager'])) {
    $currency_code_setting = $GLOBALS['mobooking_settings_manager']->get_setting($current_user_id, 'biz_currency_code', 'USD');
    $currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol($currency_code_setting);
}

// Determine user for data fetching (handle workers)
$data_user_id = $current_user_id;
$is_worker = false;
if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
    $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
    if ($owner_id) {
        $data_user_id = $owner_id;
        $is_worker = true;
    }
}

// Get dashboard URLs
$dashboard_base_url = home_url('/dashboard/');
?>

<style>

.overview-header {
    margin-bottom: 2rem;
}

.overview-title {
    font-size: 2rem;
    font-weight: 700;
    color: hsl(222.2 84% 4.9%);
    margin-bottom: 0.5rem;
}

.overview-subtitle {
    color: hsl(215.4 16.3% 46.9%);
    font-size: 1rem;
}

/* KPI Cards Grid */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.kpi-card {
    background: hsl(0 0% 100%);
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    transition: all 0.2s ease-in-out;
    position: relative;
    overflow: hidden;
}

.kpi-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    transform: translateY(-1px);
}

.kpi-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.kpi-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(215.4 16.3% 46.9%);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.kpi-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.kpi-icon.bookings { background: hsl(221.2 83.2% 53.3% / 0.1); color: hsl(221.2 83.2% 53.3%); }
.kpi-icon.revenue { background: hsl(142.1 76.2% 36.3% / 0.1); color: hsl(142.1 76.2% 36.3%); }
.kpi-icon.upcoming { background: hsl(47.9 95.8% 53.1% / 0.1); color: hsl(45.4 93.4% 47.5%); }
.kpi-icon.services { background: hsl(262.1 83.3% 57.8% / 0.1); color: hsl(262.1 83.3% 57.8%); }

.kpi-value {
    font-size: 2.25rem;
    font-weight: 700;
    color: hsl(222.2 84% 4.9%);
    margin-bottom: 0.25rem;
    line-height: 1.2;
}

.kpi-trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.kpi-trend.positive { color: hsl(142.1 76.2% 36.3%); }
.kpi-trend.negative { color: hsl(0 84.2% 60.2%); }
.kpi-trend.neutral { color: hsl(215.4 16.3% 46.9%); }

/* Main Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

@media (max-width: 1024px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

/* Chart Container */
.chart-container {
    background: hsl(0 0% 100%);
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.chart-header {
    display: flex;
    align-items: center;
    justify-content: between;
    margin-bottom: 1.5rem;
}

.chart-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(222.2 84% 4.9%);
}

.chart-tabs {
    display: flex;
    gap: 0.25rem;
    background: hsl(210 40% 96%);
    padding: 0.25rem;
    border-radius: 0.5rem;
}

.chart-tab {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(215.4 16.3% 46.9%);
    background: transparent;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.2s;
}

.chart-tab.active {
    background: hsl(0 0% 100%);
    color: hsl(222.2 84% 4.9%);
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.chart-wrapper {
    position: relative;
    height: 300px;
}

/* Recent Activity */
.activity-container {
    background: hsl(0 0% 100%);
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.activity-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.activity-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(222.2 84% 4.9%);
}

.view-all-btn {
    font-size: 0.875rem;
    color: hsl(221.2 83.2% 53.3%);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.view-all-btn:hover {
    color: hsl(221.2 83.2% 43.3%);
}

.activity-list {
    space-y: 1rem;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-radius: 0.5rem;
    border: 1px solid hsl(214.3 31.8% 91.4%);
    transition: all 0.2s;
}

.activity-item:hover {
    background: hsl(210 40% 98%);
}

.activity-avatar {
    width: 2.5rem;
    height: 2.5rem;
    background: hsl(221.2 83.2% 53.3% / 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: hsl(221.2 83.2% 53.3%);
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
    min-width: 0;
}

.activity-name {
    font-weight: 500;
    color: hsl(222.2 84% 4.9%);
    margin-bottom: 0.25rem;
}

.activity-details {
    font-size: 0.875rem;
    color: hsl(215.4 16.3% 46.9%);
}

.activity-meta {
    text-align: right;
    flex-shrink: 0;
}

.activity-price {
    font-weight: 600;
    color: hsl(222.2 84% 4.9%);
    margin-bottom: 0.25rem;
}

.activity-time {
    font-size: 0.75rem;
    color: hsl(215.4 16.3% 46.9%);
}

/* Quick Actions */
.quick-actions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
}

.quick-action-card {
    background: hsl(0 0% 100%);
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.75rem;
    padding: 1.5rem;
    text-align: center;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.quick-action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.quick-action-icon {
    width: 3rem;
    height: 3rem;
    background: hsl(221.2 83.2% 53.3% / 0.1);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
    color: hsl(221.2 83.2% 53.3%);
}

.quick-action-title {
    font-weight: 600;
    color: hsl(222.2 84% 4.9%);
    margin-bottom: 0.5rem;
}

.quick-action-desc {
    font-size: 0.875rem;
    color: hsl(215.4 16.3% 46.9%);
}

/* Loading States */
.loading {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    border: 2px solid hsl(214.3 31.8% 91.4%);
    border-radius: 50%;
    border-top-color: hsl(221.2 83.2% 53.3%);
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Status badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
}

.status-badge.pending {
    background: hsl(47.9 95.8% 53.1% / 0.1);
    color: hsl(45.4 93.4% 47.5%);
}

.status-badge.confirmed {
    background: hsl(142.1 76.2% 36.3% / 0.1);
    color: hsl(142.1 76.2% 36.3%);
}

.status-badge.completed {
    background: hsl(221.2 83.2% 53.3% / 0.1);
    color: hsl(221.2 83.2% 53.3%);
}

.status-badge.cancelled {
    background: hsl(0 84.2% 60.2% / 0.1);
    color: hsl(0 84.2% 60.2%);
}
</style>

<div class="mobooking-overview">
    <!-- Header -->
    <div class="overview-header">
        <h1 class="overview-title">
            <?php printf(esc_html__('Welcome back, %s!', 'mobooking'), esc_html($user->display_name)); ?>
        </h1>
        <p class="overview-subtitle">
            <?php esc_html_e('Here\'s what\'s happening with your cleaning business today.', 'mobooking'); ?>
        </p>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Bookings This Month', 'mobooking'); ?></span>
                <div class="kpi-icon bookings">📅</div>
            </div>
            <div class="kpi-value" id="kpi-bookings-month">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend positive" id="bookings-trend">
                <span>↗</span> +12% from last month
            </div>
        </div>

        <?php if (!$is_worker): ?>
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Revenue This Month', 'mobooking'); ?></span>
                <div class="kpi-icon revenue">💰</div>
            </div>
            <div class="kpi-value" id="kpi-revenue-month">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend positive" id="revenue-trend">
                <span>↗</span> +8% from last month
            </div>
        </div>
        <?php endif; ?>

        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Upcoming Bookings', 'mobooking'); ?></span>
                <div class="kpi-icon upcoming">⏰</div>
            </div>
            <div class="kpi-value" id="kpi-upcoming-count">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend neutral" id="upcoming-trend">
                <span>→</span> Next 7 days
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Active Services', 'mobooking'); ?></span>
                <div class="kpi-icon services">🧹</div>
            </div>
            <div class="kpi-value" id="kpi-services-count">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend neutral" id="services-trend">
                <span>→</span> Ready to book
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
        <!-- Chart Section -->
        <div class="chart-container">
            <div class="chart-header">
                <h3 class="chart-title"><?php esc_html_e('Booking Analytics', 'mobooking'); ?></h3>
                <div class="chart-tabs">
                    <button class="chart-tab active" data-period="7days">7 Days</button>
                    <button class="chart-tab" data-period="30days">30 Days</button>
                    <button class="chart-tab" data-period="90days">90 Days</button>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="bookingsChart"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="activity-container">
            <div class="activity-header">
                <h3 class="activity-title"><?php esc_html_e('Recent Bookings', 'mobooking'); ?></h3>
                <a href="<?php echo esc_url($dashboard_base_url . 'bookings/'); ?>" class="view-all-btn">
                    <?php esc_html_e('View all', 'mobooking'); ?>
                </a>
            </div>
            <div class="activity-list" id="recent-bookings-list">
                <div class="loading" style="margin: 2rem auto; display: block;"></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions-grid">
        <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_BOOKINGS)) : ?>
        <a href="#" class="quick-action-card" id="add-booking-action">
            <div class="quick-action-icon">➕</div>
            <h4 class="quick-action-title"><?php esc_html_e('Add New Booking', 'mobooking'); ?></h4>
            <p class="quick-action-desc"><?php esc_html_e('Create a booking for a customer', 'mobooking'); ?></p>
        </a>
        <?php endif; ?>

        <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_SERVICES)) : ?>
        <a href="<?php echo esc_url($dashboard_base_url . 'services/'); ?>" class="quick-action-card">
            <div class="quick-action-icon">🧹</div>
            <h4 class="quick-action-title"><?php esc_html_e('Manage Services', 'mobooking'); ?></h4>
            <p class="quick-action-desc"><?php esc_html_e('Add or edit your cleaning services', 'mobooking'); ?></p>
        </a>
        <?php endif; ?>

        <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_DISCOUNTS)) : ?>
        <a href="<?php echo esc_url($dashboard_base_url . 'discounts/'); ?>" class="quick-action-card">
            <div class="quick-action-icon">🎯</div>
            <h4 class="quick-action-title"><?php esc_html_e('Create Discount', 'mobooking'); ?></h4>
            <p class="quick-action-desc"><?php esc_html_e('Set up promotional offers', 'mobooking'); ?></p>
        </a>
        <?php endif; ?>

        <a href="<?php echo esc_url($dashboard_base_url . 'booking-form/'); ?>" class="quick-action-card">
            <div class="quick-action-icon">🔗</div>
            <h4 class="quick-action-title"><?php esc_html_e('Share Booking Form', 'mobooking'); ?></h4>
            <p class="quick-action-desc"><?php esc_html_e('Get your booking form link', 'mobooking'); ?></p>
        </a>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    'use strict';

    // Initialize variables
    let bookingsChart;
    const currencySymbol = '<?php echo esc_js($currency_symbol); ?>';
    const isWorker = <?php echo $is_worker ? 'true' : 'false'; ?>;

    // Initialize the dashboard
    initializeDashboard();

    function initializeDashboard() {
        loadKPIData();
        loadRecentBookings();
        initializeChart();
        bindEvents();
    }

    function loadKPIData() {
        // Check if we have the required AJAX parameters
        if (typeof ajaxurl === 'undefined') {
            console.error('ajaxurl not defined');
            showFallbackKPIs();
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mobooking_get_dashboard_overview_data',
                nonce: '<?php echo wp_create_nonce('mobooking_dashboard_nonce'); ?>'
            },
            success: function(response) {
                console.log('KPI Response:', response);
                if (response.success && response.data) {
                    updateKPIs(response.data.kpis || {});
                    if (response.data.chart_data) {
                        updateChart(response.data.chart_data);
                    }
                } else {
                    console.log('KPI Response not successful, using fallback');
                    showFallbackKPIs();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText, status, error);
                showFallbackKPIs();
            }
        });
    }

    function updateKPIs(kpis) {
        $('#kpi-bookings-month').text(kpis.bookings_month || '0');
        
        if (!isWorker && kpis.revenue_month !== null) {
            $('#kpi-revenue-month').text(currencySymbol + ' ' + (parseFloat(kpis.revenue_month) || 0).toFixed(2));
        }
        
        $('#kpi-upcoming-count').text(kpis.upcoming_count || '0');
        $('#kpi-services-count').text(kpis.services_count || '0');
    }

    function showFallbackKPIs() {
        $('#kpi-bookings-month').text('--');
        $('#kpi-revenue-month').text('--');
        $('#kpi-upcoming-count').text('--');
        $('#kpi-services-count').text('--');
    }

    function loadRecentBookings() {
        const container = $('#recent-bookings-list');
        
        // Check if we have the required AJAX parameters
        if (typeof ajaxurl === 'undefined') {
            console.error('ajaxurl not defined');
            container.html('<p style="text-align: center; color: hsl(0 84.2% 60.2%); padding: 2rem;">Error: AJAX URL not available.</p>');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mobooking_get_tenant_bookings',
                nonce: '<?php echo wp_create_nonce('mobooking_dashboard_nonce'); ?>',
                limit: 5,
                orderby: 'created_at',
                order: 'DESC'
            },
            success: function(response) {
                console.log('Recent bookings response:', response);
                if (response.success && response.data && response.data.bookings && response.data.bookings.length > 0) {
                    renderRecentBookings(response.data.bookings);
                } else {
                    container.html('<p style="text-align: center; color: hsl(215.4 16.3% 46.9%); padding: 2rem;">No recent bookings found.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Recent bookings AJAX Error:', xhr.responseText, status, error);
                container.html('<p style="text-align: center; color: hsl(0 84.2% 60.2%); padding: 2rem;">Error loading recent bookings.</p>');
            }
        });
    }

    function renderRecentBookings(bookings) {
        const container = $('#recent-bookings-list');
        let html = '';

        bookings.forEach(function(booking) {
            const customerInitial = booking.customer_name ? booking.customer_name.charAt(0).toUpperCase() : '?';
            const bookingDate = booking.booking_date ? new Date(booking.booking_date).toLocaleDateString() : 'N/A';
            const totalPrice = booking.total_price ? currencySymbol + ' ' + parseFloat(booking.total_price).toFixed(2) : 'N/A';
            const timeAgo = booking.created_at ? getTimeAgo(booking.created_at) : '';

            html += `
                <div class="activity-item">
                    <div class="activity-avatar">${customerInitial}</div>
                    <div class="activity-content">
                        <div class="activity-name">${escapeHtml(booking.customer_name || 'Unknown Customer')}</div>
                        <div class="activity-details">
                            Booking for ${bookingDate} • 
                            <span class="status-badge ${booking.status || 'pending'}">${(booking.status || 'pending').replace('-', ' ')}</span>
                        </div>
                    </div>
                    <div class="activity-meta">
                        <div class="activity-price">${totalPrice}</div>
                        <div class="activity-time">${timeAgo}</div>
                    </div>
                </div>
            `;
        });

        container.html(html);
    }

    function initializeChart() {
        // Wait for Chart.js to load
        if (typeof Chart === 'undefined') {
            console.log('Chart.js not loaded yet, retrying...');
            setTimeout(initializeChart, 100);
            return;
        }

        const ctx = document.getElementById('bookingsChart');
        if (!ctx) {
            console.log('Chart canvas not found');
            return;
        }

        // Sample data - replace with actual AJAX call
        const chartData = {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Bookings',
                data: [12, 19, 3, 5, 2, 3, 7],
                borderColor: 'hsl(221.2 83.2% 53.3%)',
                backgroundColor: 'hsl(221.2 83.2% 53.3% / 0.1)',
                tension: 0.4,
                fill: true
            }]
        };

        try {
            bookingsChart = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'hsl(214.3 31.8% 91.4%)'
                            },
                            ticks: {
                                color: 'hsl(215.4 16.3% 46.9%)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: 'hsl(215.4 16.3% 46.9%)'
                            }
                        }
                    }
                }
            });
            console.log('Chart initialized successfully');
        } catch (error) {
            console.error('Error initializing chart:', error);
        }
    }

    function updateChart(data) {
        if (bookingsChart && data) {
            bookingsChart.data = data;
            bookingsChart.update();
        }
    }

    function bindEvents() {
        // Chart period tabs
        $('.chart-tab').on('click', function() {
            $('.chart-tab').removeClass('active');
            $(this).addClass('active');
            
            const period = $(this).data('period');
            loadChartData(period);
        });

        // Add booking action
        $('#add-booking-action').on('click', function(e) {
            e.preventDefault();
            // Add your add booking logic here
            alert('Add booking functionality would be implemented here');
        });
    }

    function loadChartData(period) {
        // Implementation for loading chart data based on period
        console.log('Loading chart data for period:', period);
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function getTimeAgo(dateString) {
        const now = new Date();
        const date = new Date(dateString);
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
        if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
        return Math.floor(diffInSeconds / 86400) + 'd ago';
    }
});

// Make ajaxurl available globally for WordPress AJAX
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
</script>

<?php
// Add the AJAX handler registration (this would typically go in your functions.php or appropriate hook file)
?>
<script type="text/javascript">
// Additional dashboard functionality can be added here
document.addEventListener('DOMContentLoaded', function() {
    // Add any additional initialization code here
    
    // Example: Refresh data every 5 minutes
    setInterval(function() {
        if (typeof jQuery !== 'undefined') {
            jQuery('#kpi-bookings-month, #kpi-revenue-month, #kpi-upcoming-count, #kpi-services-count').each(function() {
                if (!jQuery(this).find('.loading').length) {
                    // Only refresh if not currently loading
                    loadKPIData();
                }
            });
        }
    }, 300000); // 5 minutes
});
</script>

<?php
/**
 * AJAX Handlers - Add these to your functions.php or appropriate hook file
 */

// Example AJAX handler for dashboard overview data
add_action('wp_ajax_mobooking_get_dashboard_overview_data', 'mobooking_ajax_get_dashboard_overview_data');
function mobooking_ajax_get_dashboard_overview_data() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'mobooking_overview_nonce')) {
        wp_die('Security check failed');
    }

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('User not authenticated');
    }

    // Get managers
    $services_manager = new \MoBooking\Classes\Services();
    $discounts_manager = new \MoBooking\Classes\Discounts($current_user_id);
    $notifications_manager = new \MoBooking\Classes\Notifications();
    $bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);

    // Handle worker users
    $data_user_id = $current_user_id;
    if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
        $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
        if ($owner_id) {
            $data_user_id = $owner_id;
        }
    }

    try {
        // Get KPI data
        $kpi_data = $bookings_manager->get_kpi_data($data_user_id);
        
        // Get services count
        $services_count = $services_manager->get_services_count($data_user_id);
        $kpi_data['services_count'] = $services_count;

        // Prepare chart data (example structure)
        $chart_data = array(
            'labels' => array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'),
            'datasets' => array(
                array(
                    'label' => 'Bookings',
                    'data' => array(5, 8, 3, 6, 4, 7, 9), // This should come from actual data
                    'borderColor' => 'hsl(221.2 83.2% 53.3%)',
                    'backgroundColor' => 'hsl(221.2 83.2% 53.3% / 0.1)',
                    'tension' => 0.4,
                    'fill' => true
                )
            )
        );

        wp_send_json_success(array(
            'kpis' => $kpi_data,
            'chart_data' => $chart_data
        ));

    } catch (Exception $e) {
        wp_send_json_error('Failed to load dashboard data: ' . $e->getMessage());
    }
}

// AJAX handler for recent bookings
add_action('wp_ajax_mobooking_get_recent_bookings', 'mobooking_ajax_get_recent_bookings');
function mobooking_ajax_get_recent_bookings() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'mobooking_overview_nonce')) {
        wp_die('Security check failed');
    }

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('User not authenticated');
    }

    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;

    // Get managers
    $services_manager = new \MoBooking\Classes\Services();
    $discounts_manager = new \MoBooking\Classes\Discounts($current_user_id);
    $notifications_manager = new \MoBooking\Classes\Notifications();
    $bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);

    try {
        $args = array(
            'limit' => $limit,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );

        $bookings_result = $bookings_manager->get_bookings_by_tenant($current_user_id, $args);
        
        wp_send_json_success($bookings_result['bookings'] ?? array());

    } catch (Exception $e) {
        wp_send_json_error('Failed to load recent bookings: ' . $e->getMessage());
    }
}
?>