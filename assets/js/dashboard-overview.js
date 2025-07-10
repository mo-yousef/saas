jQuery(document).ready(function($) {
    'use strict';

    // Check if essential parameters are defined
    if (typeof mobooking_overview_params === 'undefined') {
        console.error('MoBooking Error: mobooking_overview_params is not defined. Ensure it is localized correctly.');
        // Display error to user on the page elements
        $('#kpi-bookings-month, #kpi-revenue-month, #kpi-upcoming-count, #kpi-services-count').text('Error: Config missing');
        $('#recent-bookings-list').html('<p style="text-align: center; color: hsl(0 84.2% 60.2%); padding: 2rem;">Error: Configuration parameters missing.</p>');
        return; // Stop execution if params are missing
    }

    // Elements from the previous version of this script (assets/js/dashboard-overview.js)
    // It seems the inline script in page-overview.php was more up-to-date with element IDs.
    // I will use IDs found in page-overview.php's inline script.
    // const kpiBookingsMonthEl = $('#kpi-bookings-month'); // from page-overview.php
    // const kpiRevenueMonthEl = $('#kpi-revenue-month'); // from page-overview.php
    // const kpiUpcomingCountEl = $('#kpi-upcoming-count'); // from page-overview.php
    // const recentBookingsContainer = $('#mobooking-overview-recent-bookings'); // old ID
    // const bookingItemTemplate = $('#mobooking-overview-booking-item-template').html(); // old template

    // Variables from inline script
    // let bookingsChart; // Removed chart variable
    const currencySymbol = mobooking_overview_params.currency_symbol || '$';
    const isWorker = mobooking_overview_params.is_worker || false;
    // Correctly access the nonce provided by PHP: mobooking_overview_params.nonce
    const dashboardNonce = mobooking_overview_params.nonce;

    // Initialize the dashboard
    initializeDashboard();

    function initializeDashboard() {
        console.log('Initializing MoBooking Dashboard...');
        loadKPIData();
        loadRecentBookings();
        initializeBookingsCalendar(); // Added calendar initialization
        bindEvents();
        setupDataRefresh(); // For the 5-minute interval refresh
    }

    function initializeBookingsCalendar() {
        const calendarEl = document.getElementById('mobooking-bookings-calendar');
        if (!calendarEl) {
            console.warn('MoBooking Warning: Calendar container #mobooking-bookings-calendar not found.');
            return;
        }

        if (typeof FullCalendar === 'undefined') {
            console.error('MoBooking Error: FullCalendar is not loaded. Ensure it is enqueued correctly.');
            $(calendarEl).html('<p style="color: red; text-align: center;">Error: Calendar library not loaded.</p>');
            return;
        }

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: function(fetchInfo, successCallback, failureCallback) {
                $.ajax({
                    url: mobooking_overview_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mobooking_get_all_bookings_for_calendar',
                        nonce: dashboardNonce,
                        start: fetchInfo.startStr,
                        end: fetchInfo.endStr
                    },
                    success: function(response) {
                        if (response.success) {
                            successCallback(response.data);
                        } else {
                            console.error('MoBooking AJAX Error (Calendar Events):', response.data.message || 'Unknown error');
                            failureCallback(new Error(response.data.message || 'Error fetching calendar events'));
                            $(calendarEl).append('<p style="color: red; text-align: center;">Could not load booking data.</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('MoBooking AJAX HTTP Error (Calendar Events):', error, xhr.responseText);
                        failureCallback(new Error('HTTP error fetching calendar events'));
                         $(calendarEl).append('<p style="color: red; text-align: center;">Could not load booking data due to a network error.</p>');
                    }
                });
            },
            eventClick: function(info) {
                info.jsEvent.preventDefault(); // don't let the browser navigate
                if (info.event.url) {
                    window.open(info.event.url, "_blank");
                }
            },
            loading: function(isLoading) {
                if (isLoading) {
                    // Optionally add a loading indicator to the calendar container
                    $(calendarEl).showLoading();
                } else {
                    $(calendarEl).hideLoading();
                }
            }
        });
        calendar.render();
        console.log('MoBooking: Bookings calendar initialized.');
    }

    function loadKPIData() {
        if (typeof mobooking_overview_params.ajax_url === 'undefined' || !dashboardNonce) {
            console.error('MoBooking Error: AJAX URL or nonce not defined in mobooking_overview_params.');
            showFallbackKPIs();
            return;
        }

        // Show loading indicators on KPIs
        $('#kpi-bookings-month, #kpi-revenue-month, #kpi-upcoming-count, #kpi-services-count').html('<span class="loading"></span>');


        $.ajax({
            url: mobooking_overview_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_dashboard_overview_data', // Action from inline script
                nonce: dashboardNonce
            },
            success: function(response) {
                console.log('KPI Response:', response);
                if (response.success && response.data) {
                    updateKPIs(response.data.kpis || {});
                    // Removed chart update:
                    // if (response.data.chart_data) {
                    //     updateChart(response.data.chart_data);
                    // }
                } else {
                    console.warn('MoBooking Warning: KPI Response not successful or data missing, using fallback. Message:', response.data.message);
                    showFallbackKPIs();
                }
            },
            error: function(xhr, status, error) {
                console.error('MoBooking AJAX Error (KPIs):', error, xhr.responseText);
                showFallbackKPIs();
            }
        });
    }

    function updateKPIs(kpis) {
        $('#kpi-bookings-month').text(kpis.bookings_month || '0');

        if (!isWorker && typeof kpis.revenue_month !== 'undefined' && kpis.revenue_month !== null) {
             // Ensure revenue KPI box is visible if it was hidden by old logic
            $('#kpi-revenue-month').closest('.dashboard-kpi-card').show();
            $('#kpi-revenue-month').text(currencySymbol + (parseFloat(kpis.revenue_month) || 0).toFixed(2));
        } else if (!isWorker) {
            // If revenue is null/undefined for non-worker, show fallback or hide
             $('#kpi-revenue-month').text(currencySymbol + '0.00'); // Or hide: $('#kpi-revenue-month').closest('.dashboard-kpi-card').hide();
        }
        // For workers, the revenue KPI is not rendered in PHP, so no need to hide/show via JS specifically.

        $('#kpi-upcoming-count').text(kpis.upcoming_count || '0');
        $('#kpi-services-count').text(kpis.services_count || '0'); // Assuming services_count is part of kpis
    }

    function showFallbackKPIs() {
        $('#kpi-bookings-month').text('--');
        if (!isWorker) {
            $('#kpi-revenue-month').text('--');
        }
        $('#kpi-upcoming-count').text('--');
        $('#kpi-services-count').text('--');
    }

    function loadRecentBookings() {
        const container = $('#recent-bookings-list'); // ID from inline script
        if (!container.length) {
            console.warn("MoBooking Warning: Recent bookings container '#recent-bookings-list' not found.");
            return;
        }
        container.html('<div class="loading" style="margin: 2rem auto; display: block;"></div>');


        if (typeof mobooking_overview_params.ajax_url === 'undefined' || !dashboardNonce) {
            console.error('MoBooking Error: AJAX URL or nonce not defined for recent bookings.');
            container.html('<p style="text-align: center; color: hsl(0 84.2% 60.2%); padding: 2rem;">Error: AJAX URL/nonce not available.</p>');
            return;
        }

        $.ajax({
            url: mobooking_overview_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_recent_bookings', // Corrected to match PHP action name
                nonce: dashboardNonce, // Assuming same nonce can be used
                limit: 4, // Changed limit from 5 to 4
                orderby: 'created_at',
                order: 'DESC'
            },
            success: function(response) {
                console.log('Recent bookings response:', response);
                // The inline script expected response.data.bookings
                // The PHP AJAX handler `mobooking_ajax_get_recent_bookings` sends `wp_send_json_success($bookings_result['bookings'] ?? array());`
                // So response.data should be the array of bookings directly.
                if (response.success && response.data && Array.isArray(response.data) && response.data.length > 0) {
                    renderRecentBookings(response.data);
                } else if (response.success && response.data && Array.isArray(response.data) && response.data.length === 0) {
                    container.html('<p style="text-align: center; color: hsl(215.4 16.3% 46.9%); padding: 2rem;">No recent bookings found.</p>');
                }
                 else {
                    console.warn('MoBooking Warning: No recent bookings data or unsuccessful response. Message:', response.data ? response.data.message : 'No message');
                    container.html('<p style="text-align: center; color: hsl(215.4 16.3% 46.9%); padding: 2rem;">No recent bookings found or error loading.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('MoBooking AJAX Error (Recent Bookings):', error, xhr.responseText);
                container.html('<p style="text-align: center; color: hsl(0 84.2% 60.2%); padding: 2rem;">Error loading recent bookings.</p>');
            }
        });
    }

    function renderRecentBookings(bookings) {
        const container = $('#recent-bookings-list');
        let html = '';

        bookings.forEach(function(booking) {
            const customerInitial = booking.customer_name ? escapeHtml(booking.customer_name.charAt(0).toUpperCase()) : '?';
            const bookingDate = booking.booking_date ? new Date(booking.booking_date).toLocaleDateString() : 'N/A';
            const totalPrice = (typeof booking.total_price !== 'undefined' && booking.total_price !== null)
                               ? currencySymbol + parseFloat(booking.total_price).toFixed(2)
                               : 'N/A';
            const timeAgo = booking.created_at ? getTimeAgo(booking.created_at) : '';
            const statusText = (booking.status || 'pending').replace('-', ' ');
            const statusClass = escapeHtml(booking.status || 'pending');

            html += `
                <div class="activity-item">
                    <div class="activity-avatar">${customerInitial}</div>
                    <div class="activity-content">
                        <div class="activity-name">${escapeHtml(booking.customer_name || 'Unknown Customer')}</div>
                        <div class="activity-details">
                            Booking for ${escapeHtml(bookingDate)} •
                            <span class="status-badge ${statusClass}">${escapeHtml(statusText)}</span>
                        </div>
                    </div>
                    <div class="activity-meta">
                        <div class="activity-price">${escapeHtml(totalPrice)}</div>
                        <div class="activity-time">${escapeHtml(timeAgo)}</div>
                    </div>
                </div>
            `;
        });
        container.html(html);
    }

    // initializeChart(), updateChart(), loadChartDataForPeriod() removed.

    function bindEvents() {
        // Chart period tabs event binding removed.
        // $('.chart-tabs').on('click', '.chart-tab', function() { ... });

        // Add booking action
        $('#add-booking-action').on('click', function(e) {
            e.preventDefault();
            // Replace with actual modal or navigation
            alert('MoBooking: "Add new booking" functionality to be implemented.');
        });

        // Other event bindings can be added here
    }

    function escapeHtml(text) {
        if (typeof text !== 'string') return '';
        const map = {
            '&': '&amp;', '<': '&lt;', '>': '&gt;',
            '"': '&quot;', "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function getTimeAgo(dateString) {
        try {
            const now = new Date();
            const date = new Date(dateString);
            if (isNaN(date.getTime())) { // Check if dateString is valid
                return 'Invalid date';
            }
            const diffInSeconds = Math.floor((now - date) / 1000);

            if (diffInSeconds < 5) return mobooking_overview_params.i18n.time_ago_just_now || 'Just now';
            if (diffInSeconds < 60) return diffInSeconds + (mobooking_overview_params.i18n.time_ago_seconds_suffix || 's ago');
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + (mobooking_overview_params.i18n.time_ago_minutes_suffix || 'm ago');
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + (mobooking_overview_params.i18n.time_ago_hours_suffix || 'h ago');
            return Math.floor(diffInSeconds / 86400) + (mobooking_overview_params.i18n.time_ago_days_suffix || 'd ago');
        } catch (e) {
            console.error("MoBooking Error in getTimeAgo:", e);
            return dateString; // Fallback to original string
        }
    }

    function setupDataRefresh() {
        // The setInterval for refreshing data, taken from the inline script.
        // Ensure loadKPIData is accessible.
        setInterval(function() {
            console.log("MoBooking: Refreshing KPI data...");
            // Check if any KPI is currently showing a loading indicator to prevent multiple rapid calls
            let isLoading = false;
            $('#kpi-bookings-month, #kpi-revenue-month, #kpi-upcoming-count, #kpi-services-count').each(function() {
                if ($(this).find('.loading').length || $(this).text() === '...') {
                    isLoading = true;
                    return false; // break loop
                }
            });

            if (!isLoading) {
                loadKPIData(); // This will also refresh chart data if it comes from the same endpoint
            } else {
                console.log("MoBooking: KPI data refresh skipped, already loading.");
            }
        }, 300000); // 5 minutes
    }

    // Simple jQuery plugin for loading overlay (optional, can be replaced with specific classes)
    $.fn.showLoading = function() {
        return this.each(function() {
            const $this = $(this);
            if ($this.find('.loading-overlay').length === 0) {
                $this.css('position', 'relative'); // Ensure parent is positioned
                $('<div class="loading-overlay" style="position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.7); display:flex; align-items:center; justify-content:center; z-index:10;"><span class="loading-spinner" style="width:2rem;height:2rem;border:3px solid #f3f3f3;border-top:3px solid #3498db;border-radius:50%;animation:spin 1s linear infinite;"></span></div>').appendTo($this);
            }
        });
    };
    $.fn.hideLoading = function() {
        return this.each(function() {
            $(this).find('.loading-overlay').remove();
        });
    };
    // Keyframes for spinner (if not already in CSS)
    if ($('#mobooking-dynamic-styles').length === 0) {
        $('<style id="mobooking-dynamic-styles">@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>').appendTo('head');
    }


    // Initial load check - the old script had: if ($('#mobooking-overview-content').length)
    // This is good practice if the script is loaded on multiple pages.
    // Assuming this script is specifically enqueued for the overview page, this check might be redundant,
    // but it's safer to keep some form of it or ensure specific enqueueing.
    if ($('.mobooking-overview').length) { // Check for the main overview container
        console.log("MoBooking: Overview container found, proceeding with initialization.");
        // initializeDashboard() is already called above.
    } else {
        console.warn("MoBooking Warning: Main overview container '.mobooking-overview' not found. Dashboard script may not function correctly.");
    }

});
