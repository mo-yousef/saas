/**
 * MoBooking Dashboard Overview - Refactored for Shadcn UI look
 */

jQuery(document).ready(function ($) {
    "use strict";

    // Global variables
    const currencySymbol = mobooking_overview_params.currency_symbol || "$";
    const dashboardNonce = mobooking_overview_params.nonce;

    // Initialize the dashboard
    initializeDashboard();

    function initializeDashboard() {
        if (!mobooking_overview_params.ajax_url || !dashboardNonce) {
            console.error("Missing required parameters for dashboard initialization.");
            return;
        }

        // Load dynamic data
        loadStatisticWidgets();
        loadRecentBookings();

        // Load static data
        populateSetupProgress();
        populateTipsAndResources();
    }

    function loadStatisticWidgets() {
        $.ajax({
            url: mobooking_overview_params.ajax_url,
            type: "POST",
            data: {
                action: "mobooking_get_dashboard_kpi_data",
                nonce: dashboardNonce,
            },
            success: function (response) {
                if (response.success && response.data) {
                    updateStatistics(response.data);
                } else {
                    showErrorInWidgets();
                }
            },
            error: function () {
                showErrorInWidgets();
            }
        });
    }

    function updateStatistics(data) {
        // Total Bookings
        const totalBookings = data.total_bookings || 0;
        $('#total-bookings-value').text(totalBookings);

        // Total Revenue
        const totalRevenue = data.revenue_month || 0;
        $('#total-revenue-value').text(currencySymbol + parseFloat(totalRevenue).toFixed(2));

        // Revenue Breakdown - using today's revenue as main value
        const revenueToday = data.revenue_today || 0;
        $('#revenue-breakdown-value').text(currencySymbol + parseFloat(revenueToday).toFixed(2));

        // Completion Rate
        const completedBookings = data.completed_bookings || 0;
        const completionRate = totalBookings > 0 ? ((completedBookings / totalBookings) * 100).toFixed(0) + '%' : '0%';
        $('#completion-rate-value').text(completionRate);
    }

    function loadRecentBookings() {
        $.ajax({
            url: mobooking_overview_params.ajax_url,
            type: "POST",
            data: {
                action: "mobooking_get_recent_bookings",
                nonce: dashboardNonce,
                limit: 5,
            },
            success: function (response) {
                if (response.success && response.data.recent_bookings) {
                    updateRecentBookings(response.data.recent_bookings);
                } else {
                    $('#recent-bookings-list').html('<p>No recent bookings.</p>');
                }
            },
            error: function () {
                $('#recent-bookings-list').html('<p>Error loading bookings.</p>');
            }
        });
    }

    function updateRecentBookings(bookings) {
        const container = $('#recent-bookings-list');
        if (!bookings || bookings.length === 0) {
            container.html('<p>No recent bookings found.</p>');
            return;
        }

        let html = '<div class="recent-bookings-list">';
        bookings.forEach(function (booking) {
            html += `
                <div class="booking-item">
                    <div class="booking-item-details">
                        <div class="booking-item-customer">${escapeHtml(booking.customer_name || 'N/A')}</div>
                        <div class="booking-item-service">${escapeHtml(booking.service_name || 'N/A')}</div>
                    </div>
                    <div class="booking-item-price">
                        ${currencySymbol}${parseFloat(booking.total_price || 0).toFixed(2)}
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.html(html);
    }

    function populateSetupProgress() {
        const container = $('#setup-progress-list');
        const steps = [
            { name: 'Add Services', completed: true }, // Assuming user has added at least one service
            { name: 'Set Service Areas', completed: false },
            { name: 'Business Info', completed: true }, // Assuming basic info is filled
            { name: 'Customize Branding', completed: false }
        ];

        let html = '<div class="setup-progress-list">';
        steps.forEach(function(step) {
            html += `
                <div class="setup-progress-item ${step.completed ? 'completed' : ''}">
                    <div class="icon">${step.completed ? '✔' : ''}</div>
                    <span>${step.name}</span>
                </div>
            `;
        });
        html += '</div>';
        container.html(html);
    }

    function populateTipsAndResources() {
        const container = $('#tips-resources-list');
        const tips = [
            { name: 'Share Your Booking Link', url: '#' },
            { name: 'Create Promotional Codes', url: '#' }
        ];

        let html = '<div class="tips-resources-list">';
        tips.forEach(function(tip) {
            html += `
                <a href="${tip.url}" class="tip-item">
                    <span>${tip.name}</span>
                </a>
            `;
        });
        html += '</div>';
        container.html(html);
    }

    function showErrorInWidgets() {
        $('#total-bookings-value').text('Error');
        $('#total-revenue-value').text('Error');
        $('#revenue-breakdown-value').text('Error');
        $('#completion-rate-value').text('Error');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
