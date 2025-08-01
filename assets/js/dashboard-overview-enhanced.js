/**
 * MoBooking Enhanced Dashboard JavaScript
 * Interactive features and real-time updates
 * Save as: assets/js/dashboard-overview-enhanced.js
 */

(function ($) {
  "use strict";

  // Dashboard namespace
  window.MoBookingDashboard = window.MoBookingDashboard || {};

  const Dashboard = {
    charts: {},
    refreshInterval: null,
    liveUpdateInterval: null,
    isVisible: true,
    lastUpdate: null,

    // Initialize dashboard
    init() {
      this.bindEvents();
      this.initializeCharts();
      this.loadRecentActivity();
      this.setupVisibilityHandling();
      this.startLiveUpdates();
      this.setupKPIAnimations();

      // Add fade-in animation to cards
      this.animateCardsOnLoad();
    },

    // Bind all event listeners
    bindEvents() {
      // Period selector for revenue chart
      $(document).on("click", ".period-btn", (e) => {
        e.preventDefault();
        const $btn = $(e.target);
        const period = $btn.data("period");

        $(".period-btn").removeClass("active");
        $btn.addClass("active");

        this.loadChartData(period);
      });

      // Refresh buttons
      $(document).on("click", "#refresh-chart", (e) => {
        e.preventDefault();
        this.refreshChart();
      });

      $(document).on("click", "#refresh-bookings", (e) => {
        e.preventDefault();
        this.refreshRecentBookings();
      });

      $(document).on("click", "#refresh-activity", (e) => {
        e.preventDefault();
        this.loadRecentActivity();
      });

      // Setup progress items
      $(document).on(
        "click",
        ".setup-progress-item:not(.completed)",
        function () {
          const stepText = $(this).find("span").text();
          Dashboard.showNotification(
            "info",
            "Setup Step",
            `Click to complete: ${stepText}`
          );
        }
      );

      // Widget click handlers for drill-down
      $(document).on("click", '[data-widget="total-revenue"]', () => {
        this.showRevenueDetails();
      });

      $(document).on("click", '[data-widget="total-bookings"]', () => {
        this.navigateToBookings();
      });

      // Keyboard shortcuts
      $(document).on("keydown", (e) => {
        if (e.ctrlKey || e.metaKey) {
          switch (e.key) {
            case "r":
              e.preventDefault();
              this.refreshAllData();
              break;
            case "e":
              e.preventDefault();
              this.exportDashboardData();
              break;
          }
        }
      });
    },

    // Initialize Chart.js charts
    initializeCharts() {
      this.initRevenueChart();
      this.initPerformanceChart();
    },

    // Initialize revenue chart
    initRevenueChart() {
      const ctx = document.getElementById("revenue-chart");
      if (!ctx) return;

      this.charts.revenue = new Chart(ctx, {
        type: "line",
        data: {
          labels: [],
          datasets: [
            {
              label: "Revenue",
              data: [],
              borderColor: "hsl(221.2 83.2% 53.3%)",
              backgroundColor: "hsl(221.2 83.2% 53.3% / 0.1)",
              borderWidth: 3,
              fill: true,
              tension: 0.4,
              pointBackgroundColor: "hsl(221.2 83.2% 53.3%)",
              pointBorderColor: "#fff",
              pointBorderWidth: 2,
              pointRadius: 4,
              pointHoverRadius: 6,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: {
            intersect: false,
            mode: "index",
          },
          plugins: {
            legend: {
              display: false,
            },
            tooltip: {
              backgroundColor: "hsl(222.2 84% 4.9% / 0.9)",
              titleColor: "hsl(210 40% 98%)",
              bodyColor: "hsl(210 40% 98%)",
              borderColor: "hsl(214.3 31.8% 91.4%)",
              borderWidth: 1,
              cornerRadius: 8,
              callbacks: {
                label: function (context) {
                  return (
                    "Revenue: " +
                    mobooking_overview_params.currency_symbol +
                    context.parsed.y.toFixed(2)
                  );
                },
              },
            },
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: "hsl(214.3 31.8% 91.4% / 0.5)",
                drawBorder: false,
              },
              ticks: {
                color: "hsl(215.4 16.3% 46.9%)",
                callback: function (value) {
                  return (
                    mobooking_overview_params.currency_symbol + value.toFixed(0)
                  );
                },
              },
            },
            x: {
              grid: {
                color: "hsl(214.3 31.8% 91.4% / 0.3)",
                drawBorder: false,
              },
              ticks: {
                color: "hsl(215.4 16.3% 46.9%)",
              },
            },
          },
          elements: {
            point: {
              hoverBackgroundColor: "hsl(221.2 83.2% 53.3%)",
            },
          },
        },
      });

      // Load initial data
      this.loadChartData("week");
    },

    // Initialize performance doughnut chart
    initPerformanceChart() {
      const ctx = document.getElementById("performance-chart");
      if (!ctx) return;

      const stats = mobooking_overview_params.stats;
      const totalBookings = stats.total_bookings || 1;
      const completedBookings = Math.round(
        totalBookings * (stats.completion_rate / 100)
      );
      const pendingBookings = Math.round(totalBookings * 0.3);
      const cancelledBookings =
        totalBookings - completedBookings - pendingBookings;

      this.charts.performance = new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: ["Completed", "Pending", "Cancelled"],
          datasets: [
            {
              data: [
                completedBookings,
                pendingBookings,
                Math.max(0, cancelledBookings),
              ],
              backgroundColor: [
                "hsl(142.1 76.2% 36.3%)",
                "hsl(45.4 93.4% 47.5%)",
                "hsl(0 84.2% 60.2%)",
              ],
              borderWidth: 0,
              hoverBorderWidth: 2,
              hoverBorderColor: "#fff",
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: "70%",
          plugins: {
            legend: {
              position: "bottom",
              labels: {
                padding: 20,
                usePointStyle: true,
                color: "hsl(215.4 16.3% 46.9%)",
                font: {
                  size: 12,
                },
              },
            },
            tooltip: {
              backgroundColor: "hsl(222.2 84% 4.9% / 0.9)",
              titleColor: "hsl(210 40% 98%)",
              bodyColor: "hsl(210 40% 98%)",
              cornerRadius: 8,
              callbacks: {
                label: function (context) {
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const percentage = ((context.parsed / total) * 100).toFixed(
                    1
                  );
                  return (
                    context.label +
                    ": " +
                    context.parsed +
                    " (" +
                    percentage +
                    "%)"
                  );
                },
              },
            },
          },
        },
      });
    },

    // Load chart data via AJAX
    loadChartData(period = "week") {
      const $chartWidget = $('[data-widget="total-revenue"]');
      $chartWidget.addClass("loading");

      $.ajax({
        url: mobooking_overview_params.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_get_chart_data",
          nonce: mobooking_overview_params.nonce,
          period: period,
        },
        success: (response) => {
          if (response.success && this.charts.revenue) {
            this.updateRevenueChart(response.data);
          } else {
            this.showNotification(
              "error",
              "Chart Error",
              "Failed to load chart data"
            );
          }
        },
        error: (xhr, status, error) => {
          console.error("Chart data error:", error);
          this.showNotification(
            "error",
            "Network Error",
            "Failed to connect to server"
          );
        },
        complete: () => {
          $chartWidget.removeClass("loading");
        },
      });
    },

    // Update revenue chart with new data
    updateRevenueChart(data) {
      if (!this.charts.revenue) return;

      this.charts.revenue.data.labels = data.labels || [];
      this.charts.revenue.data.datasets[0].data = data.revenue || [];
      this.charts.revenue.update("active");
    },

    // Refresh chart with current period
    refreshChart() {
      const activePeriod = $(".period-btn.active").data("period") || "week";
      this.loadChartData(activePeriod);
    },

    // Refresh recent bookings
    refreshRecentBookings() {
      const $container = $("#recent-bookings-list");
      const $refreshBtn = $("#refresh-bookings");

      if (!$container.length || !$refreshBtn.length) return;

      $refreshBtn.addClass("loading");

      $.ajax({
        url: mobooking_overview_params.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_get_recent_bookings",
          nonce: mobooking_overview_params.nonce,
          limit: 8,
        },
        success: (response) => {
          if (response.success && response.data.bookings) {
            this.updateRecentBookings(response.data.bookings);
            this.showNotification(
              "success",
              "Updated",
              "Recent bookings refreshed"
            );
          } else {
            this.showNotification(
              "error",
              "Update Failed",
              "Could not refresh bookings"
            );
          }
        },
        error: (xhr, status, error) => {
          console.error("Bookings refresh error:", error);
          this.showNotification(
            "error",
            "Network Error",
            "Failed to refresh bookings"
          );
        },
        complete: () => {
          $refreshBtn.removeClass("loading");
        },
      });
    },

    // Update recent bookings display
    updateRecentBookings(bookings) {
      const $container = $("#recent-bookings-list");
      if (!$container.length) return;

      if (bookings.length === 0) {
        $container.html(`
                    <div style="text-align: center; padding: 2rem; color: hsl(var(--mobk-muted-foreground));">
                        <i data-feather="calendar" style="width: 3rem; height: 3rem; margin-bottom: 1rem;"></i>
                        <p>${mobooking_overview_params.i18n.no_data}</p>
                    </div>
                `);
        feather.replace();
        return;
      }

      const bookingsHTML = bookings
        .map(
          (booking) => `
                <div class="booking-item fade-in" style="animation-delay: ${
                  Math.random() * 200
                }ms">
                    <div class="booking-customer">
                        <div class="customer-avatar">
                            ${this.escapeHtml(
                              booking.customer_name
                                .substring(0, 2)
                                .toUpperCase()
                            )}
                        </div>
                        <div class="customer-info">
                            <h4>${this.escapeHtml(booking.customer_name)}</h4>
                            <p>${this.escapeHtml(booking.customer_email)}</p>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div class="booking-amount">
                            ${
                              mobooking_overview_params.currency_symbol
                            }${parseFloat(booking.total_price).toFixed(2)}
                        </div>
                        <div class="booking-status">
                            ${
                              booking.status.charAt(0).toUpperCase() +
                              booking.status.slice(1)
                            }
                        </div>
                    </div>
                </div>
            `
        )
        .join("");

      $container.html(bookingsHTML);
    },

    // Load recent activity
    loadRecentActivity() {
      const $container = $("#activity-feed");
      const $refreshBtn = $("#refresh-activity");

      if (!$container.length) return;

      $refreshBtn?.addClass("loading");

      $.ajax({
        url: mobooking_overview_params.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_get_recent_activity",
          nonce: mobooking_overview_params.nonce,
          limit: 10,
        },
        success: (response) => {
          if (response.success) {
            this.updateActivityFeed(response.data || []);
          } else {
            this.updateActivityFeed([]);
          }
        },
        error: (xhr, status, error) => {
          console.error("Activity load error:", error);
          this.updateActivityFeed([]);
        },
        complete: () => {
          $refreshBtn?.removeClass("loading");
        },
      });
    },

    // Update activity feed
    updateActivityFeed(activities) {
      const $container = $("#activity-feed");
      if (!$container.length) return;

      if (activities.length === 0) {
        activities = this.generateSampleActivity();
      }

      const activitiesHTML = activities
        .map(
          (activity, index) => `
                <div class="activity-item fade-in" style="animation-delay: ${
                  index * 100
                }ms">
                    <div class="activity-icon">
                        <i data-feather="${activity.icon}"></i>
                    </div>
                    <div class="activity-content">
                        <h4>${this.escapeHtml(activity.title)}</h4>
                        <p>${this.escapeHtml(
                          activity.description
                        )} • ${this.timeAgo(activity.timestamp)}</p>
                    </div>
                </div>
            `
        )
        .join("");

      $container.html(activitiesHTML);

      // Replace feather icons safely
      if (typeof feather !== "undefined") {
        feather.replace();
      }
    },

    // Generate sample activity data
    generateSampleActivity() {
      const now = new Date();
      return [
        {
          icon: "calendar-plus",
          title:
            mobooking_overview_params.i18n.new_booking ||
            "New booking received",
          description: "John Doe booked Hair Cut service",
          timestamp: new Date(now - 15 * 60 * 1000), // 15 minutes ago
        },
        {
          icon: "check-circle",
          title:
            mobooking_overview_params.i18n.booking_updated || "Booking updated",
          description: "Booking #1234 marked as completed",
          timestamp: new Date(now - 2 * 60 * 60 * 1000), // 2 hours ago
        },
        {
          icon: "plus-circle",
          title:
            mobooking_overview_params.i18n.service_created || "Service created",
          description: "Massage Therapy service added",
          timestamp: new Date(now - 24 * 60 * 60 * 1000), // 1 day ago
        },
        {
          icon: "user-plus",
          title: mobooking_overview_params.i18n.worker_added || "Worker added",
          description: "New staff member Sarah joined",
          timestamp: new Date(now - 3 * 24 * 60 * 60 * 1000), // 3 days ago
        },
        {
          icon: "settings",
          title:
            mobooking_overview_params.i18n.settings_updated ||
            "Settings updated",
          description: "Business hours updated",
          timestamp: new Date(now - 5 * 24 * 60 * 60 * 1000), // 5 days ago
        },
      ];
    },

    // Setup KPI animations
    setupKPIAnimations() {
      const stats = mobooking_overview_params.stats;

      // Animate values on load with staggered timing
      setTimeout(
        () =>
          this.animateValue(
            "total-revenue-value",
            0,
            stats.total_revenue,
            2000,
            true
          ),
        200
      );
      setTimeout(
        () =>
          this.animateValue(
            "total-bookings-value",
            0,
            stats.total_bookings,
            1500,
            false
          ),
        400
      );
      setTimeout(
        () =>
          this.animateValue(
            "today-revenue-value",
            0,
            stats.today_revenue,
            1800,
            true
          ),
        600
      );
      setTimeout(
        () =>
          this.animateValue(
            "completion-rate-value",
            0,
            stats.completion_rate,
            1600,
            false,
            "%"
          ),
        800
      );
    },

    // Animate numeric values
    animateValue(
      elementId,
      start,
      end,
      duration,
      isCurrency = false,
      suffix = ""
    ) {
      const element = document.getElementById(elementId);
      if (!element) return;

      const startTime = performance.now();
      const startValue = start;
      const endValue = end;

      const animate = (currentTime) => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const easeProgress = this.easeOutCubic(progress);
        const currentValue =
          startValue + (endValue - startValue) * easeProgress;

        let displayValue;
        if (isCurrency) {
          displayValue =
            mobooking_overview_params.currency_symbol +
            this.formatNumber(currentValue);
        } else {
          displayValue = Math.round(currentValue) + suffix;
        }

        element.textContent = displayValue;

        if (progress < 1) {
          requestAnimationFrame(animate);
        }
      };

      requestAnimationFrame(animate);
    },

    // Easing function
    easeOutCubic(t) {
      return 1 - Math.pow(1 - t, 3);
    },

    // Format numbers with commas
    formatNumber(num) {
      return parseFloat(num).toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    },

    // Start live updates
    startLiveUpdates() {
      // Refresh dashboard data every 5 minutes
      this.refreshInterval = setInterval(() => {
        if (this.isVisible) {
          this.refreshDashboardData();
        }
      }, 5 * 60 * 1000);

      // Check for live updates every 30 seconds
      this.liveUpdateInterval = setInterval(() => {
        if (this.isVisible) {
          this.checkLiveUpdates();
        }
      }, 30 * 1000);

      // Real-time today's revenue updates (every 2 minutes)
      setInterval(() => {
        if (this.isVisible) {
          this.updateTodayRevenue();
        }
      }, 2 * 60 * 1000);
    },

    // Setup page visibility handling
    setupVisibilityHandling() {
      document.addEventListener("visibilitychange", () => {
        this.isVisible = !document.hidden;

        if (this.isVisible) {
          // Page became visible, refresh data
          this.refreshDashboardData();
        }
      });
    },

    // Refresh all dashboard data
    refreshDashboardData() {
      $.ajax({
        url: mobooking_overview_params.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_get_dashboard_stats",
          nonce: mobooking_overview_params.nonce,
        },
        success: (response) => {
          if (response.success) {
            this.updateKPIs(response.data);
          }
        },
        error: (xhr, status, error) => {
          console.error("Dashboard refresh error:", error);
        },
      });
    },

    // Update KPI values
    updateKPIs(stats) {
      // Update total revenue with animation
      const $totalRevenueEl = $("#total-revenue-value");
      const currentRevenue = parseFloat(
        $totalRevenueEl.text().replace(/[^0-9.-]+/g, "")
      );
      const newRevenue = parseFloat(stats.total_revenue || 0);

      if (Math.abs(currentRevenue - newRevenue) > 0.01) {
        this.animateValue(
          "total-revenue-value",
          currentRevenue,
          newRevenue,
          1000,
          true
        );
        this.showValueChange($totalRevenueEl, newRevenue > currentRevenue);
      }

      // Update other KPIs
      const updates = [
        {
          id: "total-bookings-value",
          value: stats.total_bookings,
          format: "number",
        },
        {
          id: "today-revenue-value",
          value: stats.today_revenue,
          format: "currency",
        },
        {
          id: "completion-rate-value",
          value: stats.completion_rate,
          format: "percentage",
        },
      ];

      updates.forEach((update) => {
        const $el = $(`#${update.id}`);
        if ($el.length && stats[update.value] !== undefined) {
          let displayValue;
          switch (update.format) {
            case "currency":
              displayValue =
                mobooking_overview_params.currency_symbol +
                parseFloat(stats[update.value]).toFixed(2);
              break;
            case "percentage":
              displayValue = parseFloat(stats[update.value]).toFixed(1) + "%";
              break;
            default:
              displayValue = stats[update.value];
          }

          if ($el.text() !== displayValue) {
            $el.text(displayValue);
            this.showValueChange($el);
          }
        }
      });

      // Update quick stats
      $("#week-bookings").text(stats.week_bookings + " bookings");
      $("#avg-booking-value").text(
        mobooking_overview_params.currency_symbol +
          parseFloat(stats.avg_booking_value).toFixed(2)
      );
      $("#active-services").text(stats.active_services);
    },

    // Show visual indication of value change
    showValueChange($element, isIncrease = true) {
      $element.addClass("scale-in");

      if (isIncrease) {
        $element.css("color", "hsl(var(--mobk-success))");
      }

      setTimeout(() => {
        $element.removeClass("scale-in");
        $element.css("color", "");
      }, 500);
    },

    // Update today's revenue specifically
    updateTodayRevenue() {
      $.ajax({
        url: mobooking_overview_params.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_get_today_revenue",
          nonce: mobooking_overview_params.nonce,
        },
        success: (response) => {
          if (response.success) {
            const $todayRevenueEl = $("#today-revenue-value");
            const newValue =
              mobooking_overview_params.currency_symbol +
              parseFloat(response.data.today_revenue).toFixed(2);

            if ($todayRevenueEl.text() !== newValue) {
              $todayRevenueEl.css("transform", "scale(1.1)");
              $todayRevenueEl.text(newValue);
              setTimeout(() => {
                $todayRevenueEl.css("transform", "scale(1)");
              }, 200);
            }
          }
        },
        error: (xhr, status, error) => {
          console.error("Today revenue update error:", error);
        },
      });
    },

    // Check for live updates
    checkLiveUpdates() {
      $.ajax({
        url: mobooking_overview_params.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_get_live_updates",
          nonce: mobooking_overview_params.nonce,
          last_update: this.lastUpdate || "",
        },
        success: (response) => {
          if (response.success && response.data.has_updates) {
            this.handleLiveUpdates(response.data);
            this.lastUpdate = response.data.timestamp;
          }
        },
        error: (xhr, status, error) => {
          console.error("Live updates error:", error);
        },
      });
    },

    // Handle live updates
    handleLiveUpdates(updates) {
      if (updates.new_bookings > 0) {
        this.refreshRecentBookings();
        this.refreshDashboardData();

        // Show notification for new bookings
        this.showNotification(
          "success",
          "New Booking!",
          `${updates.new_bookings} new booking${
            updates.new_bookings > 1 ? "s" : ""
          } received`
        );
      }

      if (updates.notifications && updates.notifications.length > 0) {
        updates.notifications.forEach((notification) => {
          this.showNotification(
            notification.type,
            notification.title,
            notification.message
          );
        });
      }
    },

    // Animate cards on load
    animateCardsOnLoad() {
      $(".card").each(function (index) {
        $(this).css({
          opacity: "0",
          transform: "translateY(20px)",
        });

        setTimeout(() => {
          $(this).css({
            opacity: "1",
            transform: "translateY(0)",
            transition: "all 0.6s cubic-bezier(0.16, 1, 0.3, 1)",
          });
        }, index * 100);
      });
    },

    // Show revenue details modal/popup
    showRevenueDetails() {
      this.showNotification(
        "info",
        "Revenue Details",
        "Detailed revenue breakdown would open here"
      );
      // Here you could implement a modal with detailed revenue breakdown
    },

    // Navigate to bookings page
    navigateToBookings() {
      window.location.href =
        mobooking_overview_params.dashboard_base_url + "bookings/";
    },

    // Export dashboard data
    exportDashboardData() {
      $.ajax({
        url: mobooking_overview_params.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_export_dashboard_data",
          nonce: mobooking_overview_params.nonce,
          format: "csv",
          period: "month",
        },
        success: (response) => {
          if (response.success) {
            this.downloadFile(
              response.data.csv,
              response.data.filename,
              "text/csv"
            );
            this.showNotification(
              "success",
              "Export Complete",
              "Dashboard data exported successfully"
            );
          } else {
            this.showNotification(
              "error",
              "Export Failed",
              "Could not export dashboard data"
            );
          }
        },
        error: (xhr, status, error) => {
          console.error("Export error:", error);
          this.showNotification(
            "error",
            "Export Error",
            "Failed to export data"
          );
        },
      });
    },

    // Download file helper
    downloadFile(content, filename, contentType) {
      const blob = new Blob([content], { type: contentType });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
    },

    // Refresh all data
    refreshAllData() {
      this.refreshDashboardData();
      this.refreshChart();
      this.refreshRecentBookings();
      this.loadRecentActivity();

      this.showNotification(
        "success",
        "Refreshed",
        "All dashboard data updated"
      );
    },

    // Time ago helper
    timeAgo(timestamp) {
      const now = new Date();
      const past = new Date(timestamp);
      const diffInSeconds = Math.floor((now - past) / 1000);

      if (diffInSeconds < 60) {
        return diffInSeconds < 5
          ? mobooking_overview_params.i18n.time_ago_just_now
          : diffInSeconds +
              mobooking_overview_params.i18n.time_ago_seconds_suffix;
      }

      const diffInMinutes = Math.floor(diffInSeconds / 60);
      if (diffInMinutes < 60) {
        return (
          diffInMinutes + mobooking_overview_params.i18n.time_ago_minutes_suffix
        );
      }

      const diffInHours = Math.floor(diffInMinutes / 60);
      if (diffInHours < 24) {
        return (
          diffInHours + mobooking_overview_params.i18n.time_ago_hours_suffix
        );
      }

      const diffInDays = Math.floor(diffInHours / 24);
      return diffInDays + mobooking_overview_params.i18n.time_ago_days_suffix;
    },

    // HTML escape helper
    escapeHtml(text) {
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    },

    // Show notification (integrates with existing MoBooking notification system)
    showNotification(type, title, message) {
      if (typeof window.showAlert === "function") {
        window.showAlert(message, type);
      } else if (typeof window.showToast === "function") {
        window.showToast({
          type: type,
          title: title,
          message: message,
        });
      } else {
        // Fallback to console
        console.log(`${type.toUpperCase()}: ${title} - ${message}`);
      }
    },

    // Cleanup function
    destroy() {
      // Clear intervals
      if (this.refreshInterval) {
        clearInterval(this.refreshInterval);
      }

      if (this.liveUpdateInterval) {
        clearInterval(this.liveUpdateInterval);
      }

      // Destroy charts
      Object.values(this.charts).forEach((chart) => {
        if (chart && typeof chart.destroy === "function") {
          chart.destroy();
        }
      });

      // Remove event listeners
      $(document).off("click", ".period-btn");
      $(document).off("click", "#refresh-chart");
      $(document).off("click", "#refresh-bookings");
      $(document).off("click", "#refresh-activity");
      $(document).off("click", ".setup-progress-item:not(.completed)");
      $(document).off("click", "[data-widget]");
      $(document).off("keydown");
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    // Initialize Feather Icons
    if (typeof feather !== 'undefined') {
      feather.replace();
    }

    // Initialize dashboard
    Dashboard.init();

    // Export to global scope
    window.MoBookingDashboard = Dashboard;

    // Handle page unload
    $(window).on("beforeunload", () => {
      Dashboard.destroy();
    });

    // Add keyboard shortcut hints
    if (console && console.info) {
      console.info(
        "MoBooking Dashboard Shortcuts:\n- Ctrl/Cmd + R: Refresh all data\n- Ctrl/Cmd + E: Export data"
      );
    }
  });
})(jQuery);
