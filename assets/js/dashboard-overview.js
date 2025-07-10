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
    let bookingsChart;
    const currencySymbol = mobooking_overview_params.currency_symbol || '$';
    const isWorker = mobooking_overview_params.is_worker || false;
    const dashboardNonce = mobooking_overview_params.dashboard_nonce; // Assuming this will be localized

    // Initialize the dashboard
    initializeDashboard();

    function initializeDashboard() {
        console.log('Initializing MoBooking Dashboard...');
        loadKPIData();
        loadRecentBookings(); // This was a separate call in the inline script
        initializeChart();
        bindEvents();
        setupDataRefresh(); // For the 5-minute interval refresh
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
                    if (response.data.chart_data) { // Chart data comes with this response
                        updateChart(response.data.chart_data);
                    }
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
                limit: 5,
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

    function initializeChart() {
        if (typeof Chart === 'undefined') {
            console.warn('MoBooking Warning: Chart.js not loaded yet, retrying in 100ms...');
            setTimeout(initializeChart, 100);
            return;
        }

        const ctx = document.getElementById('bookingsChart');
        if (!ctx) {
            console.warn('MoBooking Warning: Chart canvas element #bookingsChart not found.');
            return;
        }

        // Default chart data (will be updated by AJAX)
        const initialChartData = {
            labels: [], // Populate with actual data
            datasets: [{
                label: 'Bookings',
                data: [], // Populate with actual data
                borderColor: 'hsl(221.2 83.2% 53.3%)',
                backgroundColor: 'hsl(221.2 83.2% 53.3% / 0.1)',
                tension: 0.4,
                fill: true
            }]
        };

        try {
            bookingsChart = new Chart(ctx, {
                type: 'line',
                data: initialChartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y;
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'hsl(214.3 31.8% 91.4%)' },
                            ticks: { color: 'hsl(215.4 16.3% 46.9%)' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: 'hsl(215.4 16.3% 46.9%)' }
                        }
                    }
                }
            });
            console.log('MoBooking: Chart initialized successfully.');
        } catch (error) {
            console.error('MoBooking Error initializing chart:', error);
        }
    }

    function updateChart(chartData) {
        if (bookingsChart && chartData && chartData.labels && chartData.datasets) {
            bookingsChart.data.labels = chartData.labels;
            bookingsChart.data.datasets = chartData.datasets.map(dataset => ({
                ...dataset, // Spread existing dataset properties
                borderColor: dataset.borderColor || 'hsl(221.2 83.2% 53.3%)',
                backgroundColor: dataset.backgroundColor || 'hsl(221.2 83.2% 53.3% / 0.1)',
                tension: dataset.tension || 0.4,
                fill: typeof dataset.fill !== 'undefined' ? dataset.fill : true,
            }));
            bookingsChart.update();
            console.log('MoBooking: Chart updated.');
        } else {
            console.warn('MoBooking Warning: Attempted to update chart with invalid data or chart not initialized.');
        }
    }

    function loadChartDataForPeriod(period) {
        console.log('MoBooking: Loading chart data for period:', period);
        // This function should make an AJAX call to fetch data for the selected period
        // For now, it's a placeholder. The main `loadKPIData` fetches initial chart data.
        // This implies `mobooking_get_dashboard_overview_data` should accept a period parameter.
         $('#bookingsChart').parent().showLoading(); // Example of showing a loader

        $.ajax({
            url: mobooking_overview_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_dashboard_chart_data', // A new dedicated action or modify existing
                nonce: dashboardNonce,
                period: period
            },
            success: function(response) {
                if (response.success && response.data) {
                    updateChart(response.data); // Assuming response.data is the chart data object
                } else {
                    console.warn('MoBooking Warning: Failed to load chart data for period:', period, response.data.message);
                    // Optionally, show an error on the chart or revert to default
                }
            },
            error: function(xhr, status, error) {
                console.error('MoBooking AJAX Error (Chart Data for Period):', error, xhr.responseText);
            },
            complete: function() {
                 $('#bookingsChart').parent().hideLoading(); // Hide loader
            }
        });
    }


    function bindEvents() {
        // Chart period tabs
        $('.chart-tabs').on('click', '.chart-tab', function() { // Delegated event
            const $this = $(this);
            if ($this.hasClass('active')) return;

            $('.chart-tab').removeClass('active');
            $this.addClass('active');

            const period = $this.data('period');
            loadChartDataForPeriod(period); // Call function to load data for the new period
        });

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
