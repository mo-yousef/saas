jQuery(document).ready(function($) {
    'use strict';

    const kpiBookingsMonthEl = $('#kpi-bookings-month');
    const kpiRevenueMonthEl = $('#kpi-revenue-month');
    const kpiUpcomingCountEl = $('#kpi-upcoming-count');
    const recentBookingsContainer = $('#mobooking-overview-recent-bookings');
    const bookingItemTemplate = $('#mobooking-overview-booking-item-template').html();

    function sanitizeHTML(str) {
        if (typeof str !== 'string') return '';
        var temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    }

    function renderTemplate(templateHtml, data) {
        let template = templateHtml;
        for (const key in data) {
            const value = (data[key] === null || typeof data[key] === 'undefined') ? '' : data[key];
            template = template.replace(new RegExp('<%=\\s*' + key + '\\s*%>', 'g'), sanitizeHTML(String(value)));
        }
        return template;
    }

    function loadOverviewData() {
        // Show loading indicators
        kpiBookingsMonthEl.text('...');
        kpiRevenueMonthEl.text('...');
        kpiUpcomingCountEl.text('...');
        recentBookingsContainer.html('<p>' + (mobooking_overview_params.i18n.loading_data || 'Loading data...') + '</p>');

        $.ajax({
            url: mobooking_overview_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_dashboard_overview_data',
                nonce: mobooking_overview_params.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Populate KPIs
                    if (response.data.kpis) {
                        kpiBookingsMonthEl.text(response.data.kpis.bookings_month || '0');
                        const revenue = parseFloat(response.data.kpis.revenue_month) || 0;
                        const revenueFormatted = mobooking_overview_params.currency_position === 'after' ?
                            revenue.toFixed(2) + mobooking_overview_params.currency_symbol :
                            mobooking_overview_params.currency_symbol + revenue.toFixed(2);
                        kpiRevenueMonthEl.text(revenueFormatted);
                        kpiUpcomingCountEl.text(response.data.kpis.upcoming_count || '0');
                    }

                    // Populate Recent Bookings
                    recentBookingsContainer.empty();
                    if (response.data.recent_bookings && response.data.recent_bookings.length) {
                        response.data.recent_bookings.forEach(function(booking) {
                            let bookingData = {...booking};
                            const price = parseFloat(booking.total_price) || 0;
                            bookingData.total_price_formatted = mobooking_overview_params.currency_position === 'after' ?
                                price.toFixed(2) + mobooking_overview_params.currency_symbol :
                                mobooking_overview_params.currency_symbol + price.toFixed(2);
                            bookingData.status_display = mobooking_overview_params.statuses[booking.status] || booking.status.charAt(0).toUpperCase() + booking.status.slice(1);
                            recentBookingsContainer.append(renderTemplate(bookingItemTemplate, bookingData));
                        });
                    } else {
                        recentBookingsContainer.html('<p>' + (mobooking_overview_params.i18n.no_recent_bookings || 'No recent bookings to display.') + '</p>');
                    }
                } else {
                    recentBookingsContainer.html('<p>' + (response.data.message || mobooking_overview_params.i18n.error_loading_data || 'Error loading data.') + '</p>');
                     kpiBookingsMonthEl.text('N/A');
                     kpiRevenueMonthEl.text('N/A');
                     kpiUpcomingCountEl.text('N/A');
                }
            },
            error: function() {
                recentBookingsContainer.html('<p>' + (mobooking_overview_params.i18n.error_ajax || 'AJAX error.') + '</p>');
                 kpiBookingsMonthEl.text('Error');
                 kpiRevenueMonthEl.text('Error');
                 kpiUpcomingCountEl.text('Error');
            }
        });
    }

    // Initial load
    if ($('#mobooking-overview-content').length) { // Only load if on the overview page
        loadOverviewData();
    }
});
