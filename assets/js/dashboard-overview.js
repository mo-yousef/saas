/**
 * MoBooking Dashboard Overview - Refactored Version
 * Focused on KPIs, live activity, and business insights
 */

jQuery(document).ready(function ($) {
  "use strict";

  // Global variables
  const currencySymbol = mobooking_overview_params.currency_symbol || "$";
  const isWorker = mobooking_overview_params.is_worker || false;
  const dashboardNonce = mobooking_overview_params.nonce;
  let bookingStatusChart = null;
  let refreshInterval = null;

  // Initialize the dashboard
  initializeDashboard();

  function initializeDashboard() {
    console.log("🚀 Initializing MoBooking Dashboard Overview...");

    // Load all data
    loadKPIData();
    loadLiveActivityFeed();
    loadTopServices();
    loadCustomerInsights();
    initializeBookingStatusChart();
    updateSubscriptionUsage();

    // Bind events
    bindEvents();

    // Setup auto-refresh
    setupDataRefresh();
  }

  function loadKPIData() {
    if (!mobooking_overview_params.ajax_url || !dashboardNonce) {
      console.error("❌ AJAX URL or nonce not defined");
      showFallbackKPIs();
      return;
    }

    // Show loading indicators
    $(".kpi-value .loading").show();

    $.ajax({
      url: mobooking_overview_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_dashboard_kpi_data",
        nonce: dashboardNonce,
      },
      success: function (response) {
        console.log("📊 KPI Response:", response);
        if (response.success && response.data) {
          updateKPIs(response.data);
        } else {
          console.warn("⚠️ KPI Response not successful, using fallback");
          showFallbackKPIs();
        }
      },
      error: function (xhr, status, error) {
        console.error("❌ KPI AJAX Error:", error);
        showFallbackKPIs();
      },
    });
  }

  function updateKPIs(data) {
    // Update each KPI with animation
    animateValue("kpi-bookings-today", data.bookings_today || 0);
    animateValue("kpi-bookings-month", data.bookings_month || 0);
    animateValue("kpi-upcoming-count", data.upcoming_count || 0);
    animateValue("kpi-completed-count", data.completed_count || 0);
    animateValue("kpi-cancelled-count", data.cancelled_count || 0);
    animateValue("kpi-new-customers", data.new_customers || 0);

    // Revenue and average booking value (only for non-workers)
    if (!isWorker) {
      const revenueValue = parseFloat(data.revenue_month || 0);
      const avgBookingValue = parseFloat(data.avg_booking_value || 0);

      $("#kpi-revenue-month").text(currencySymbol + revenueValue.toFixed(2));
      $("#kpi-avg-booking-value").text(
        currencySymbol + avgBookingValue.toFixed(2)
      );
    }

    // Update trends
    updateTrends(data.trends || {});
  }

  function animateValue(elementId, endValue, duration = 1000) {
    const element = $("#" + elementId);
    const startValue = 0;
    const increment = endValue / (duration / 16);
    let currentValue = startValue;

    const timer = setInterval(function () {
      currentValue += increment;
      if (currentValue >= endValue) {
        currentValue = endValue;
        clearInterval(timer);
      }
      element.text(Math.floor(currentValue));
    }, 16);
  }

  function updateTrends(trends) {
    // Update trend indicators based on data
    Object.keys(trends).forEach((key) => {
      const trendElement = $("#" + key + "-trend");
      const trend = trends[key];

      if (trend.direction === "up") {
        trendElement.removeClass("negative neutral").addClass("positive");
        trendElement.find("span:first-child").text("↗");
      } else if (trend.direction === "down") {
        trendElement.removeClass("positive neutral").addClass("negative");
        trendElement.find("span:first-child").text("↘");
      } else {
        trendElement.removeClass("positive negative").addClass("neutral");
        trendElement.find("span:first-child").text("→");
      }

      // Update trend text
      const trendText = trendElement.text().replace(/^[→↗↘]\s*/, "");
      trendElement.html(
        `<span>${trendElement.find("span:first-child").text()}</span> ${
          trend.text || trendText
        }`
      );
    });
  }

  function showFallbackKPIs() {
    $(".kpi-value").each(function () {
      $(this).text("--");
    });
  }

  function loadLiveActivityFeed() {
    const container = $("#live-activity-feed");

    $.ajax({
      url: mobooking_overview_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_live_activity",
        nonce: dashboardNonce,
        limit: 10,
      },
      success: function (response) {
        if (response.success && response.data) {
          renderActivityFeed(response.data, container);
        } else {
          container.html('<p class="no-data">No recent activity found.</p>');
        }
      },
      error: function () {
        container.html(
          '<p class="error-message">Error loading activity feed.</p>'
        );
      },
    });
  }

  function renderActivityFeed(activities, container) {
    if (!activities || activities.length === 0) {
      container.html('<p class="no-data">No recent activity found.</p>');
      return;
    }

    let html = '<div class="activity-list">';
    activities.forEach((activity) => {
      html += `
                <div class="activity-item">
                    <div class="activity-icon ${activity.type}">
                        ${getActivityIcon(activity.type)}
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">${escapeHtml(
                          activity.text
                        )}</div>
                        <div class="activity-time">${getTimeAgo(
                          activity.timestamp
                        )}</div>
                    </div>
                    <div class="activity-status">
                        <span class="status-badge status-${activity.status}">${
        activity.status
      }</span>
                    </div>
                </div>
            `;
    });
    html += "</div>";

    container.html(html);
  }

  function getActivityIcon(type) {
    const icons = {
      booking_created: "📅",
      booking_confirmed: "✅",
      booking_completed: "🏁",
      booking_cancelled: "❌",
      payment_received: "💰",
      customer_registered: "👥",
      service_updated: "🧹",
    };
    return icons[type] || "📋";
  }

  function loadTopServices() {
    const container = $("#top-services-list");

    $.ajax({
      url: mobooking_overview_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_top_services",
        nonce: dashboardNonce,
        limit: 5,
      },
      success: function (response) {
        if (response.success && response.data) {
          renderTopServices(response.data, container);
        } else {
          container.html('<p class="no-data">No services data available.</p>');
        }
      },
      error: function () {
        container.html(
          '<p class="error-message">Error loading top services.</p>'
        );
      },
    });
  }

  function renderTopServices(services, container) {
    if (!services || services.length === 0) {
      container.html('<p class="no-data">No services found.</p>');
      return;
    }

    let html = '<div class="services-list">';
    services.forEach((service, index) => {
      const rankIcon =
        index === 0
          ? "🥇"
          : index === 1
          ? "🥈"
          : index === 2
          ? "🥉"
          : `${index + 1}.`;
      html += `
                <div class="service-item">
                    <div class="service-rank">${rankIcon}</div>
                    <div class="service-info">
                        <div class="service-name">${escapeHtml(
                          service.name
                        )}</div>
                        <div class="service-bookings">${
                          service.booking_count
                        } bookings</div>
                    </div>
                    <div class="service-revenue">
                        ${
                          !isWorker
                            ? currencySymbol +
                              parseFloat(service.revenue || 0).toFixed(2)
                            : ""
                        }
                    </div>
                </div>
            `;
    });
    html += "</div>";

    container.html(html);
  }

  function loadCustomerInsights() {
    $.ajax({
      url: mobooking_overview_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_customer_insights",
        nonce: dashboardNonce,
      },
      success: function (response) {
        if (response.success && response.data) {
          updateCustomerInsights(response.data);
        }
      },
      error: function () {
        console.error("Error loading customer insights");
      },
    });
  }

  function updateCustomerInsights(data) {
    $("#insight-new-customers").text(data.new_customers || 0);
    $("#insight-returning-customers").text(data.returning_customers || 0);
    $("#insight-retention-rate").text((data.retention_rate || 0) + "%");

    // Update percentages
    const total = (data.new_customers || 0) + (data.returning_customers || 0);
    if (total > 0) {
      $("#insight-new-percentage").text(
        Math.round((data.new_customers / total) * 100) + "%"
      );
      $("#insight-returning-percentage").text(
        Math.round((data.returning_customers / total) * 100) + "%"
      );
    }
    $("#insight-retention-percentage").text((data.retention_rate || 0) + "%");
  }

  function initializeBookingStatusChart() {
    const ctx = document.getElementById("booking-status-chart");
    if (!ctx) {
      console.warn("Chart canvas not found");
      return;
    }

    if (typeof Chart === "undefined") {
      console.error("Chart.js is not loaded");
      return;
    }

    // Load chart data
    loadChartData("week");
  }

  function loadChartData(period) {
    $.ajax({
      url: mobooking_overview_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_chart_data",
        nonce: dashboardNonce,
        period: period,
      },
      success: function (response) {
        if (response.success && response.data) {
          renderChart(response.data);
        }
      },
      error: function () {
        console.error("Error loading chart data");
      },
    });
  }

  function renderChart(data) {
    const ctx = document
      .getElementById("booking-status-chart")
      .getContext("2d");

    // Destroy existing chart
    if (bookingStatusChart) {
      bookingStatusChart.destroy();
    }

    bookingStatusChart = new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: data.labels || [
          "Confirmed",
          "Pending",
          "Completed",
          "Cancelled",
        ],
        datasets: [
          {
            data: data.values || [0, 0, 0, 0],
            backgroundColor: [
              "hsl(142.1 76.2% 36.3%)",
              "hsl(47.9 95.8% 53.1%)",
              "hsl(221.2 83.2% 53.3%)",
              "hsl(0 84.2% 60.2%)",
            ],
            borderWidth: 0,
            cutout: "60%",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              padding: 20,
              usePointStyle: true,
            },
          },
        },
      },
    });
  }

  function updateSubscriptionUsage() {
    // Get current usage data
    $.ajax({
      url: mobooking_overview_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_subscription_usage",
        nonce: dashboardNonce,
      },
      success: function (response) {
        if (response.success && response.data) {
          const usage = response.data;

          // Update usage displays
          $("#usage-bookings").text(
            `${usage.bookings_used} / ${usage.bookings_limit}`
          );
          $("#usage-services").text(
            `${usage.services_used} / ${usage.services_limit}`
          );

          // Calculate and update progress
          const bookingsPercentage = Math.round(
            (usage.bookings_used / usage.bookings_limit) * 100
          );
          $("#usage-progress-bar").css("width", bookingsPercentage + "%");
          $("#usage-progress-text").text(bookingsPercentage + "% used");

          // Update progress bar color based on usage
          const progressBar = $("#usage-progress-bar");
          progressBar.removeClass("progress-warning progress-danger");
          if (bookingsPercentage >= 90) {
            progressBar.addClass("progress-danger");
          } else if (bookingsPercentage >= 75) {
            progressBar.addClass("progress-warning");
          }
        }
      },
      error: function () {
        console.error("Error loading subscription usage");
      },
    });
  }

  function bindEvents() {
    // Chart period selector
    $(".period-btn").on("click", function () {
      const period = $(this).data("period");
      $(".period-btn").removeClass("active");
      $(this).addClass("active");
      loadChartData(period);
    });

    // Quick action buttons
    $("#add-booking-action").on("click", function (e) {
      e.preventDefault();
      openAddBookingModal();
    });

    // Subscription action buttons
    $(".btn-upgrade").on("click", function (e) {
      e.preventDefault();
      window.open("/upgrade", "_blank");
    });

    $(".btn-manage").on("click", function (e) {
      e.preventDefault();
      window.location.href =
        mobooking_overview_params.dashboard_base_url + "settings/#subscription";
    });

    // Refresh button (if added to UI)
    $(document).on("click", ".refresh-data", function (e) {
      e.preventDefault();
      refreshAllData();
    });
  }

  function openAddBookingModal() {
    // This would open a modal or redirect to add booking page
    // For now, we'll redirect to the bookings page
    window.location.href =
      mobooking_overview_params.dashboard_base_url + "bookings/?action=add";
  }

  function refreshAllData() {
    console.log("🔄 Refreshing all dashboard data...");

    // Show loading states
    $(".kpi-value").html('<span class="loading"></span>');
    $("#live-activity-feed").html(
      '<div class="loading-activity"><span class="loading"></span><p>Loading activities...</p></div>'
    );
    $("#top-services-list").html(
      '<div class="loading-services"><span class="loading"></span><p>Loading services...</p></div>'
    );

    // Reload all data
    loadKPIData();
    loadLiveActivityFeed();
    loadTopServices();
    loadCustomerInsights();
    updateSubscriptionUsage();

    // Reload chart with current period
    const activePeriod = $(".period-btn.active").data("period") || "week";
    loadChartData(activePeriod);
  }

  function setupDataRefresh() {
    // Auto-refresh data every 5 minutes
    refreshInterval = setInterval(function () {
      console.log("⏰ Auto-refreshing dashboard data...");

      // Only refresh if no loading indicators are visible
      if ($(".loading:visible").length === 0) {
        loadKPIData();
        loadLiveActivityFeed();
        updateSubscriptionUsage();
      }
    }, 300000); // 5 minutes
  }

  // Utility functions
  function escapeHtml(text) {
    if (typeof text !== "string") return "";
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text.replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  }

  function getTimeAgo(dateString) {
    try {
      const now = new Date();
      const date = new Date(dateString);
      if (isNaN(date.getTime())) {
        return "Invalid date";
      }
      const diffInSeconds = Math.floor((now - date) / 1000);

      if (diffInSeconds < 5)
        return mobooking_overview_params.i18n.time_ago_just_now || "Just now";
      if (diffInSeconds < 60)
        return (
          diffInSeconds +
          (mobooking_overview_params.i18n.time_ago_seconds_suffix || "s ago")
        );
      if (diffInSeconds < 3600)
        return (
          Math.floor(diffInSeconds / 60) +
          (mobooking_overview_params.i18n.time_ago_minutes_suffix || "m ago")
        );
      if (diffInSeconds < 86400)
        return (
          Math.floor(diffInSeconds / 3600) +
          (mobooking_overview_params.i18n.time_ago_hours_suffix || "h ago")
        );
      return (
        Math.floor(diffInSeconds / 86400) +
        (mobooking_overview_params.i18n.time_ago_days_suffix || "d ago")
      );
    } catch (e) {
      console.error("Error in getTimeAgo:", e);
      return dateString;
    }
  }

  function formatCurrency(amount) {
    return currencySymbol + parseFloat(amount || 0).toFixed(2);
  }

  function showNotification(message, type = "info") {
    // Simple notification system
    const notification = $(`
            <div class="mobooking-notification notification-${type}">
                ${escapeHtml(message)}
                <button class="notification-close">&times;</button>
            </div>
        `);

    $("body").append(notification);

    setTimeout(() => {
      notification.fadeOut(() => notification.remove());
    }, 5000);

    notification.find(".notification-close").on("click", () => {
      notification.fadeOut(() => notification.remove());
    });
  }

  // Error handling
  $(document).ajaxError(function (event, xhr, settings, thrownError) {
    if (settings.url && settings.url.includes("mobooking_")) {
      console.error("MoBooking AJAX Error:", {
        url: settings.url,
        error: thrownError,
        status: xhr.status,
        responseText: xhr.responseText,
      });

      // Show user-friendly error message
      if (xhr.status === 403) {
        showNotification("Session expired. Please refresh the page.", "error");
      } else if (xhr.status === 500) {
        showNotification("Server error. Please try again later.", "error");
      }
    }
  });

  // Cleanup function
  function cleanup() {
    if (refreshInterval) {
      clearInterval(refreshInterval);
    }
    if (bookingStatusChart) {
      bookingStatusChart.destroy();
    }
  }

  // Cleanup on page unload
  $(window).on("beforeunload", cleanup);

  // Expose some functions globally for debugging
  window.MoBookingDashboard = {
    refreshData: refreshAllData,
    loadKPIData: loadKPIData,
    loadChart: loadChartData,
    updateUsage: updateSubscriptionUsage,
  };

  console.log("✅ MoBooking Dashboard Overview initialized successfully");
});

// Additional CSS for new elements (can be moved to separate CSS file)
const additionalCSS = `
    <style>
    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .activity-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        background: var(--mobk-dashboard-muted);
        border-radius: var(--mobk-dashboard-radius-sm);
        transition: background-color 0.2s ease;
    }
    
    .activity-item:hover {
        background: var(--mobk-dashboard-accent);
    }
    
    .activity-icon {
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        background: var(--mobk-dashboard-card);
        flex-shrink: 0;
    }
    
    .activity-content {
        flex: 1;
        min-width: 0;
    }
    
    .activity-text {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--mobk-dashboard-foreground);
        margin-bottom: 0.25rem;
    }
    
    .activity-time {
        font-size: 0.75rem;
        color: var(--mobk-dashboard-muted-foreground);
    }
    
    .activity-status {
        flex-shrink: 0;
    }
    
    .services-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .service-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        background: var(--mobk-dashboard-muted);
        border-radius: var(--mobk-dashboard-radius-sm);
        transition: background-color 0.2s ease;
    }
    
    .service-item:hover {
        background: var(--mobk-dashboard-accent);
    }
    
    .service-rank {
        font-size: 1rem;
        font-weight: 600;
        width: 1.5rem;
        text-align: center;
        flex-shrink: 0;
    }
    
    .service-info {
        flex: 1;
        min-width: 0;
    }
    
    .service-name {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--mobk-dashboard-foreground);
        margin-bottom: 0.125rem;
    }
    
    .service-bookings {
        font-size: 0.75rem;
        color: var(--mobk-dashboard-muted-foreground);
    }
    
    .service-revenue {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--mobk-dashboard-primary);
        flex-shrink: 0;
    }
    
    .no-data, .error-message {
        text-align: center;
        padding: 2rem;
        color: var(--mobk-dashboard-muted-foreground);
        font-style: italic;
    }
    
    .error-message {
        color: var(--mobk-dashboard-destructive);
    }
    
    .mobooking-notification {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 9999;
        padding: 0.75rem 1rem;
        border-radius: var(--mobk-dashboard-radius);
        color: white;
        font-size: 0.875rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .notification-info {
        background: var(--mobk-dashboard-primary);
    }
    
    .notification-success {
        background: hsl(142.1 76.2% 36.3%);
    }
    
    .notification-error {
        background: var(--mobk-dashboard-destructive);
    }
    
    .notification-close {
        background: none;
        border: none;
        color: inherit;
        font-size: 1.25rem;
        cursor: pointer;
        padding: 0;
        margin-left: auto;
    }
    
    .progress-warning .progress-fill {
        background: linear-gradient(90deg, #f59e0b, #fbbf24) !important;
    }
    
    .progress-danger .progress-fill {
        background: linear-gradient(90deg, #ef4444, #f87171) !important;
    }
    </style>
`;

// Inject additional CSS
document.head.insertAdjacentHTML("beforeend", additionalCSS);
