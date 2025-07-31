/**
 * MoBooking Dashboard Overview - Refactored for shadcn UI
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

        // Add event listeners
        addEventListeners();
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
                    $('#recent-bookings-list').html('<p class="text-center text-gray-500">No recent bookings.</p>');
                }
            },
            error: function () {
                $('#recent-bookings-list').html('<p class="text-center text-red-500">Error loading bookings.</p>');
            }
        });
    }

    function updateRecentBookings(bookings) {
        const container = $('#recent-bookings-list');
        if (!bookings || bookings.length === 0) {
            container.html('<p class="text-center text-gray-500">No recent bookings found.</p>');
            return;
        }

        let html = '<div class="recent-bookings-list">';
        bookings.forEach(function (booking) {
            const avatarLetter = (booking.customer_name || 'N/A').charAt(0).toUpperCase();
            html += `
                <div class="booking-item">
                    <div class="booking-item-details">
                        <div class="booking-item-avatar">${escapeHtml(avatarLetter)}</div>
                        <div>
                            <div class="booking-item-customer">${escapeHtml(booking.customer_name || 'N/A')}</div>
                            <div class="booking-item-service">${escapeHtml(booking.service_name || 'N/A')}</div>
                        </div>
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
            { name: 'Add Services', completed: true, url: mobooking_overview_params.dashboard_base_url + 'services/' },
            { name: 'Set Service Areas', completed: false, url: mobooking_overview_params.dashboard_base_url + 'areas/' },
            { name: 'Business Info', completed: true, url: mobooking_overview_params.dashboard_base_url + 'settings/' },
            { name: 'Customize Branding', completed: false, url: mobooking_overview_params.dashboard_base_url + 'settings/' }
        ];

        let html = '<div class="setup-progress-list">';
        steps.forEach(function(step) {
            html += `
                <a href="${step.url}" class="setup-progress-item ${step.completed ? 'completed' : ''}">
                    <div class="icon">${step.completed ? '✔' : ''}</div>
                    <span>${step.name}</span>
                </a>
            `;
        });
        html += '</div>';
        container.html(html);
    }

    function addEventListeners() {
        const copyButton = $('#copy-share-link-button');
        if (copyButton.length) {
            copyButton.on('click', function() {
                const input = $('.share-link-input');
                input.select();
                document.execCommand('copy');

                const originalText = copyButton.html();
                copyButton.html(mobooking_overview_params.i18n.copied || 'Copied!');
                setTimeout(function() {
                    copyButton.html(originalText);
                    feather.replace(); // Re-render the icon
                }, 2000);
            });
        }
    }

    function showErrorInWidgets() {
        const errorText = mobooking_overview_params.i18n.error || 'Error';
        $('#total-bookings-value').text(errorText);
        $('#total-revenue-value').text(errorText);
        $('#revenue-breakdown-value').text(errorText);
        $('#completion-rate-value').text(errorText);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
