/**
 * MoBooking Dashboard Overview - Fixed Version
 * Corrected AJAX action names and improved error handling
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

    // Check if required parameters are available
    if (!mobooking_overview_params.ajax_url || !dashboardNonce) {
      console.error("❌ Missing required parameters:", {
        ajax_url: mobooking_overview_params.ajax_url,
        nonce: dashboardNonce,
      });
      showErrorMessage("Configuration error: Missing required parameters");
      return;
    }

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
    console.log("📊 Loading KPI data...");

    // Show loading indicators
    $(".kpi-value .loading").show();
    $(".kpi-value").not(".loading").hide();

    $.ajax({
      url: mobooking_overview_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_dashboard_kpi_data", // Fixed action name
        nonce: dashboardNonce,
      },
      timeout: 30000, // 30 second timeout
      success: function (response) {
        console.log("📊 KPI Response:", response);

        if (response.success && response.data) {
          updateKPIs(response.data);
        } else {
          console.warn("⚠️ KPI Response not successful:", response);
          showFallbackKPIs();
          if (response.data && response.data.message) {
            showErrorMessage("KPI Error: " + response.data.message);
          }
        }
      },
      error: function (xhr, status, error) {
        console.error("❌ KPI AJAX Error:", {
          status: status,
          error: error,
          responseText: xhr.responseText,
          statusCode: xhr.status,
        });

        showFallbackKPIs();

        // Show user-friendly error message
        if (xhr.status === 500) {
          showErrorMessage(
            "Server error loading dashboard data. Please check server logs."
          );
        } else if (xhr.status === 403) {
          showErrorMessage(
            "Access denied. Please refresh the page and try again."
          );
        } else {
          showErrorMessage("Failed to load dashboard data. Please try again.");
        }
      },
      complete: function () {
        $(".kpi-value .loading").hide();
        $(".kpi-value").not(".loading").show();
      },
    });
  }

  function updateKPIs(data) {
    console.log("📈 Updating KPIs with data:", data);

    // Update each KPI with safe fallbacks
    const kpis = [
      { selector: "#kpi-total-bookings", value: data.total_bookings || 0 },
      { selector: "#kpi-pending-bookings", value: data.pending_bookings || 0 },
      {
        selector: "#kpi-revenue-month",
        value: data.revenue_month || 0,
        isCurrency: true,
      },
      { selector: "#kpi-services-count", value: data.services_count || 0 },
      {
        selector: "#kpi-revenue-today",
        value: data.revenue_today || 0,
        isCurrency: true,
      },
      { selector: "#kpi-bookings-today", value: data.bookings_today || 0 },
      {
        selector: "#kpi-confirmed-bookings",
        value: data.confirmed_bookings || 0,
      },
      {
        selector: "#kpi-completed-bookings",
        value: data.completed_bookings || 0,
      },
    ];

    kpis.forEach(function (kpi) {
      const element = $(kpi.selector);
      if (element.length > 0) {
        let displayValue = kpi.value;
        if (kpi.isCurrency) {
          displayValue = currencySymbol + parseFloat(kpi.value).toFixed(2);
        }
        element.text(displayValue);
        element.removeClass("loading");
      }
    });

    // Hide loading indicators
    $(".kpi-value .loading").hide();
  }

  function showFallbackKPIs() {
    console.log("📊 Showing fallback KPIs");

    const fallbackKPIs = [
      { selector: "#kpi-total-bookings", value: "0" },
      { selector: "#kpi-pending-bookings", value: "0" },
      { selector: "#kpi-revenue-month", value: currencySymbol + "0.00" },
      { selector: "#kpi-services-count", value: "0" },
      { selector: "#kpi-revenue-today", value: currencySymbol + "0.00" },
      { selector: "#kpi-bookings-today", value: "0" },
      { selector: "#kpi-confirmed-bookings", value: "0" },
      { selector: "#kpi-completed-bookings", value: "0" },
    ];

    fallbackKPIs.forEach(function (kpi) {
      const element = $(kpi.selector);
      if (element.length > 0) {
        element.text(kpi.value);
        element.removeClass("loading");
      }
    });

    $(".kpi-value .loading").hide();
  }

  function loadLiveActivityFeed() {
    console.log("📡 Loading recent bookings...");

    $.ajax({
      url: mobooking_overview_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_recent_bookings",
        nonce: dashboardNonce,
        limit: 5,
      },
      timeout: 30000,
      success: function (response) {
        console.log("📡 Recent bookings response:", response);

        if (
          response.success &&
          response.data &&
          response.data.recent_bookings
        ) {
          updateLiveActivityFeed(response.data.recent_bookings);
        } else {
          console.warn("⚠️ Recent bookings response not successful:", response);
          showEmptyActivityFeed();
        }
      },
      error: function (xhr, status, error) {
        console.error("❌ Recent bookings AJAX error:", {
          status: status,
          error: error,
          responseText: xhr.responseText,
        });
        showEmptyActivityFeed();
      },
    });
  }

  function updateLiveActivityFeed(bookings) {
    const container = $("#live-activity-feed");
    if (container.length === 0) return;

    if (!bookings || bookings.length === 0) {
      container.html(
        '<div class="no-activity">No recent bookings found.</div>'
      );
      return;
    }

    let html = "";
    bookings.forEach(function (booking) {
      const bookingDate = new Date(
        booking.booking_date + " " + booking.booking_time
      );
      const formattedDate = bookingDate.toLocaleDateString();
      const formattedTime = bookingDate.toLocaleTimeString();

      html += `
        <div class="activity-item">
          <div class="activity-content">
            <div class="activity-title">${escapeHtml(
              booking.customer_name || "N/A"
            )}</div>
            <div class="activity-details">
              <span class="activity-ref">#${escapeHtml(
                booking.booking_reference || "N/A"
              )}</span>
              <span class="activity-date">${formattedDate} at ${formattedTime}</span>
              <span class="activity-status status-${booking.status}">${
        booking.status || "pending"
      }</span>
            </div>
          </div>
          <div class="activity-price">
            ${currencySymbol}${parseFloat(booking.total_price || 0).toFixed(2)}
          </div>
        </div>
      `;
    });

    container.html(html);
  }

  function showEmptyActivityFeed() {
    const container = $("#live-activity-feed");
    if (container.length > 0) {
      container.html(
        '<div class="no-activity">No recent bookings available.</div>'
      );
    }
  }

  function loadTopServices() {
    console.log("🏆 Loading top services...");

    $.ajax({
      url: mobooking_overview_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_top_services",
        nonce: dashboardNonce,
        limit: 5,
      },
      timeout: 30000,
      success: function (response) {
        console.log("🏆 Top services response:", response);

        if (response.success && response.data && response.data.top_services) {
          updateTopServices(response.data.top_services);
        } else {
          console.warn("⚠️ Top services response not successful:", response);
          showEmptyTopServices();
        }
      },
      error: function (xhr, status, error) {
        console.error("❌ Top services AJAX error:", {
          status: status,
          error: error,
          responseText: xhr.responseText,
        });
        showEmptyTopServices();
      },
    });
  }

  function updateTopServices(services) {
    const container = $("#top-services-list");
    if (container.length === 0) return;

    if (!services || services.length === 0) {
      container.html(
        '<div class="no-services">No services data available.</div>'
      );
      return;
    }

    let html = '<div class="services-grid">';
    services.forEach(function (service) {
      html += `
        <div class="service-item">
          <div class="service-info">
            <div class="service-name">${escapeHtml(service.name || "N/A")}</div>
            <div class="service-stats">
              <span class="service-bookings">${
                service.bookings_count || 0
              } bookings</span>
              ${
                service.revenue
                  ? `<span class="service-revenue">${currencySymbol}${parseFloat(
                      service.revenue
                    ).toFixed(2)}</span>`
                  : ""
              }
            </div>
          </div>
        </div>
      `;
    });
    html += "</div>";

    container.html(html);
  }

  function showEmptyTopServices() {
    const container = $("#top-services-list");
    if (container.length > 0) {
      container.html(
        '<div class="no-services">No services data available.</div>'
      );
    }
  }

  function loadCustomerInsights() {
    console.log("👥 Loading customer insights...");

    $.ajax({
      url: mobooking_overview_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_customer_insights",
        nonce: dashboardNonce,
      },
      timeout: 30000,
      success: function (response) {
        console.log("👥 Customer insights response:", response);

        if (response.success && response.data) {
          updateCustomerInsights(response.data);
        } else {
          console.warn(
            "⚠️ Customer insights response not successful:",
            response
          );
          showFallbackCustomerInsights();
        }
      },
      error: function (xhr, status, error) {
        console.error("❌ Customer insights AJAX error:", {
          status: status,
          error: error,
          responseText: xhr.responseText,
        });
        showFallbackCustomerInsights();
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
    } else {
      $("#insight-new-percentage").text("0%");
      $("#insight-returning-percentage").text("0%");
    }
  }

  function showFallbackCustomerInsights() {
    $("#insight-new-customers").text("0");
    $("#insight-returning-customers").text("0");
    $("#insight-retention-rate").text("0%");
    $("#insight-new-percentage").text("0%");
    $("#insight-returning-percentage").text("0%");
  }

  function initializeBookingStatusChart() {
    const ctx = document.getElementById("booking-status-chart");
    if (!ctx) {
      console.warn("📊 Chart canvas not found");
      return;
    }

    if (typeof Chart === "undefined") {
      console.error("❌ Chart.js is not loaded");
      return;
    }

    // Load chart data
    loadChartData("week");
  }

  function loadChartData(period) {
    console.log("📊 Loading chart data for period:", period);

    $.ajax({
      url: mobooking_overview_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_chart_data",
        nonce: dashboardNonce,
        period: period,
      },
      timeout: 30000,
      success: function (response) {
        console.log("📊 Chart data response:", response);

        if (response.success && response.data) {
          updateChart(response.data);
        } else {
          console.warn("⚠️ Chart data response not successful:", response);
          showEmptyChart();
        }
      },
      error: function (xhr, status, error) {
        console.error("❌ Chart data AJAX error:", {
          status: status,
          error: error,
          responseText: xhr.responseText,
        });
        showEmptyChart();
      },
    });
  }

  function updateChart(data) {
    const ctx = document.getElementById("booking-status-chart");
    if (!ctx) return;

    // Destroy existing chart
    if (bookingStatusChart) {
      bookingStatusChart.destroy();
    }

    // Create new chart
    bookingStatusChart = new Chart(ctx, {
      type: "line",
      data: {
        labels: data.labels || [],
        datasets: [
          {
            label: "Bookings",
            data: data.values || [],
            borderColor: "#3b82f6",
            backgroundColor: "rgba(59, 130, 246, 0.1)",
            tension: 0.4,
            fill: true,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1,
            },
          },
        },
      },
    });
  }

  function showEmptyChart() {
    const ctx = document.getElementById("booking-status-chart");
    if (!ctx) return;

    // Destroy existing chart
    if (bookingStatusChart) {
      bookingStatusChart.destroy();
    }

    // Show empty state
    const container = ctx.parentElement;
    if (container) {
      container.innerHTML =
        '<div class="chart-empty">No chart data available</div>';
    }
  }

  function updateSubscriptionUsage() {
    // Placeholder for subscription usage updates
    console.log("📊 Updating subscription usage...");
  }

  function bindEvents() {
    // Refresh button
    $(document).on("click", "#refresh-dashboard", function (e) {
      e.preventDefault();
      console.log("🔄 Manual refresh triggered");
      initializeDashboard();
    });

    // Chart period selector
    $(document).on("change", "#chart-period-selector", function () {
      const period = $(this).val();
      loadChartData(period);
    });

    // Quick action buttons
    $(document).on("click", "#add-booking-action", function (e) {
      e.preventDefault();
      // Redirect to add booking page or show modal
      window.location.href =
        mobooking_overview_params.dashboard_url + "bookings/";
    });
  }

  function setupDataRefresh() {
    // Auto-refresh every 5 minutes
    refreshInterval = setInterval(function () {
      console.log("🔄 Auto-refreshing dashboard data...");
      loadKPIData();
      loadLiveActivityFeed();
    }, 300000); // 5 minutes

    // Clear interval on page unload
    $(window).on("beforeunload", function () {
      if (refreshInterval) {
        clearInterval(refreshInterval);
      }
    });
  }

  function showErrorMessage(message) {
    console.error("❌ Error:", message);

    // Show error in a user-friendly way
    let errorContainer = $("#dashboard-error-message");
    if (errorContainer.length === 0) {
      errorContainer = $(
        '<div id="dashboard-error-message" class="notice notice-error" style="margin: 10px 0;"><p></p></div>'
      );
      $(".mobooking-overview").prepend(errorContainer);
    }

    errorContainer.find("p").text(message);
    errorContainer.show();

    // Auto-hide after 10 seconds
    setTimeout(function () {
      errorContainer.fadeOut();
    }, 10000);
  }

  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  // Debug function to help troubleshoot issues
  function debugDashboard() {
    console.log("🔍 Dashboard Debug Info:");
    console.log("- AJAX URL:", mobooking_overview_params.ajax_url);
    console.log("- Nonce:", dashboardNonce);
    console.log("- Currency Symbol:", currencySymbol);
    console.log("- Is Worker:", isWorker);
    console.log("- jQuery version:", $.fn.jquery);
    console.log("- Chart.js available:", typeof Chart !== "undefined");

    // Test AJAX connectivity
    $.ajax({
      url: mobooking_overview_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_debug_ajax",
        nonce: dashboardNonce,
      },
      success: function (response) {
        console.log("🔍 AJAX Debug Response:", response);
      },
      error: function (xhr, status, error) {
        console.error("🔍 AJAX Debug Error:", {
          status: status,
          error: error,
          responseText: xhr.responseText,
        });
      },
    });
  }

  // Expose debug function globally for testing
  window.debugMoBookingDashboard = debugDashboard;

  // Initial debug on load if in debug mode
  if (mobooking_overview_params.debug) {
    debugDashboard();
  }
});
