/**
 * NORDBOOKING Enhanced Dashboard JavaScript
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
      // Check if parameters are available
      if (typeof nordbooking_overview_params === 'undefined') {
        console.error('NORDBOOKING: Dashboard parameters not loaded');
        return;
      }

      this.bindEvents();
      this.initializeCharts();
      this.setupStaticActivityFeed();
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

      // Keyboard shortcuts removed as requested
    },

    initServicePerformanceChart() {
      const ctx = document.getElementById("service-performance-chart");
      if (!ctx) {
        console.log("Service performance chart canvas not found");
        return;
      }

      // Ensure chart is destroyed and canvas is clean
      if (this.charts.servicePerformance) {
        this.charts.servicePerformance.destroy();
        this.charts.servicePerformance = null;
      }

      console.log("Initializing service performance chart with real data...");

      // Get real data from canvas data attributes
      const services = JSON.parse(ctx.dataset.services || '[]');
      const counts = JSON.parse(ctx.dataset.counts || '[]');
      
      // Fallback data if no real data available
      const labels = services.length > 0 ? services : ['No Services'];
      const data = counts.length > 0 ? counts.map(Number) : [0];
      
      // Generate colors for the bars
      const colors = [
        'hsl(221.2 83.2% 53.3%)',
        'hsl(142.1 76.2% 36.3%)',
        'hsl(45.4 93.4% 47.5%)',
        'hsl(262.1 83.3% 57.8%)',
        'hsl(0 84.2% 60.2%)',
        'hsl(280 100% 70%)',
        'hsl(200 100% 50%)',
        'hsl(30 100% 50%)'
      ];

      this.charts.servicePerformance = new Chart(ctx, {
        type: "bar",
        data: {
          labels: labels,
          datasets: [
            {
              label: "Bookings",
              data: data,
              backgroundColor: colors.slice(0, labels.length),
              borderWidth: 0,
              borderRadius: 4
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                stepSize: 1,
                color: 'hsl(215.4 16.3% 46.9%)',
                font: { size: 11 }
              },
              grid: {
                color: 'hsl(214.3 31.8% 91.4% / 0.3)',
                drawBorder: false
              }
            },
            x: {
              ticks: {
                color: 'hsl(215.4 16.3% 46.9%)',
                font: { size: 11 }
              },
              grid: {
                display: false
              }
            }
          },
          plugins: {
            legend: {
              display: false,
            },
            tooltip: {
              backgroundColor: 'hsl(222.2 84% 4.9% / 0.9)',
              titleColor: 'hsl(210 40% 98%)',
              bodyColor: 'hsl(210 40% 98%)',
              cornerRadius: 8
            }
          },
        },
      });
    },

    // Initialize Chart.js charts
    initializeCharts() {
      // Destroy all existing charts first
      this.destroyAllCharts();
      
      this.initPerformanceChart();
      this.initServicePerformanceChart();
      this.initCustomerInsightsAnimations();
      this.initRevenueBreakdownAnimations();
    },

    // Destroy all existing charts
    destroyAllCharts() {
      // Destroy Chart.js instances
      if (typeof Chart !== 'undefined') {
        Chart.helpers.each(Chart.instances, function(instance) {
          instance.destroy();
        });
      }
      
      // Clear our chart references
      Object.keys(this.charts).forEach(key => {
        if (this.charts[key] && typeof this.charts[key].destroy === 'function') {
          this.charts[key].destroy();
        }
        this.charts[key] = null;
      });
      
      // Clear the charts object
      this.charts = {};
    },

    // Initialize revenue chart
    initRevenueChart() {
      const ctx = document.getElementById("revenue-chart");
      if (!ctx) return;

      // Ensure chart is destroyed and canvas is clean
      if (this.charts.revenue) {
        this.charts.revenue.destroy();
        this.charts.revenue = null;
      }

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
              borderWidth: 2,
              fill: true,
              tension: 0.3,
              pointBackgroundColor: "hsl(221.2 83.2% 53.3%)",
              pointBorderColor: "hsl(var(--background))",
              pointBorderWidth: 2,
              pointRadius: 4,
              pointHoverRadius: 6,
              pointHoverBackgroundColor: "hsl(221.2 83.2% 53.3%)",
              pointHoverBorderColor: "hsl(var(--background))",
              pointHoverBorderWidth: 2,
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
                    nordbooking_overview_params.currency_symbol +
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
                    nordbooking_overview_params.currency_symbol +
                    value.toFixed(0)
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

      // Chart initialized without auto-loading data
      console.log("Revenue chart initialized");
    },

    // Initialize performance doughnut chart
    initPerformanceChart() {
      const ctx = document.getElementById("performance-chart");
      if (!ctx) return;

      // Ensure chart is destroyed and canvas is clean
      if (this.charts.performance) {
        this.charts.performance.destroy();
        this.charts.performance = null;
      }

      const stats = nordbooking_overview_params.stats || {};
      const totalBookings = stats.total_bookings || 1;
      const completionRate = stats.completion_rate || 0;
      const completedBookings = Math.round(
        totalBookings * (completionRate / 100)
      );
      const pendingBookings = Math.round(totalBookings * 0.3);
      const cancelledBookings = Math.max(0,
        totalBookings - completedBookings - pendingBookings
      );

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
      console.log("Loading chart data for period:", period);
      
      const $chartWidget = $('[data-widget="total-revenue"]');
      $chartWidget.addClass("loading");

      $.ajax({
        url: nordbooking_overview_params.ajax_url,
        type: "POST",
        data: {
          action: "nordbooking_get_chart_data",
          nonce: nordbooking_overview_params.nonce,
          period: period,
        },
        success: (response) => {
          console.log("Chart data response:", response);
          if (response.success && this.charts.revenue) {
            this.updateRevenueChart(response.data);
            this.showNotification("success", "Chart Updated", "Revenue data refreshed");
          } else {
            console.error("Chart data error:", response);
            // Create sample data if no real data
            this.updateRevenueChart({
              labels: this.getSampleLabels(period),
              revenue: this.getSampleRevenue(period)
            });
          }
        },
        error: (xhr, status, error) => {
          console.error("Chart data AJAX error:", {xhr, status, error});
          // Create sample data on error
          this.updateRevenueChart({
            labels: this.getSampleLabels(period),
            revenue: this.getSampleRevenue(period)
          });
          this.showNotification(
            "error",
            "Network Error",
            "Using sample data - check connection"
          );
        },
        complete: () => {
          $chartWidget.removeClass("loading");
        },
      });
    },

    // Get sample labels for different periods
    getSampleLabels(period) {
      const now = new Date();
      const labels = [];
      
      switch(period) {
        case 'week':
          for(let i = 6; i >= 0; i--) {
            const date = new Date(now);
            date.setDate(date.getDate() - i);
            labels.push(date.toLocaleDateString('en-US', { weekday: 'short' }));
          }
          break;
        case 'month':
          for(let i = 29; i >= 0; i--) {
            const date = new Date(now);
            date.setDate(date.getDate() - i);
            labels.push(date.getDate().toString());
          }
          break;
        case 'quarter':
          for(let i = 11; i >= 0; i--) {
            const date = new Date(now);
            date.setMonth(date.getMonth() - i);
            labels.push(date.toLocaleDateString('en-US', { month: 'short' }));
          }
          break;
        default:
          labels.push('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
      }
      
      return labels;
    },

    // Get sample revenue data
    getSampleRevenue(period) {
      const baseRevenue = 1000;
      const dataPoints = period === 'week' ? 7 : period === 'month' ? 30 : 12;
      const revenue = [];
      
      for(let i = 0; i < dataPoints; i++) {
        revenue.push(baseRevenue + Math.random() * 500);
      }
      
      return revenue;
    },

    // Update revenue chart with new data
    updateRevenueChart(data) {
      if (!this.charts.revenue) {
        console.error("Revenue chart not initialized");
        return;
      }

      console.log("Updating revenue chart with data:", data);
      
      this.charts.revenue.data.labels = data.labels || [];
      this.charts.revenue.data.datasets[0].data = data.revenue || [];
      this.charts.revenue.update("active");
      
      console.log("Revenue chart updated successfully");
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
        url: nordbooking_overview_params.ajax_url,
        type: "POST",
        data: {
          action: "nordbooking_get_recent_bookings",
          nonce: nordbooking_overview_params.nonce,
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
                        <p>${nordbooking_overview_params.i18n.no_data}</p>
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
                              nordbooking_overview_params.currency_symbol
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
        url: nordbooking_overview_params.ajax_url,
        type: "POST",
        data: {
          action: "nordbooking_get_recent_activity",
          nonce: nordbooking_overview_params.nonce,
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
        $container.html(`
          <div class="empty-state">
            <div class="empty-state-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 20h9"></path>
                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
              </svg>
            </div>
            <div>${nordbooking_overview_params.i18n.no_data || 'No recent activity'}</div>
          </div>
        `);
        return;
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

    // Setup static activity feed
    setupStaticActivityFeed() {
      console.log('Setting up static activity feed...');
      const $container = $("#activity-feed");
      console.log('Activity feed container found:', $container.length > 0);
      
      if ($container.length === 0) {
        console.error('Activity feed container not found');
        return;
      }
      
      const activities = this.generateSampleActivity();
      console.log('Generated activities:', activities);
      this.updateActivityFeed(activities);
    },

    // Generate sample activity data
    generateSampleActivity() {
      const now = new Date();
      return [
        {
          icon: "calendar-plus",
          title: "New booking received",
          description: "John Doe booked Hair Cut service",
          timestamp: new Date(now - 15 * 60 * 1000), // 15 minutes ago
        },
        {
          icon: "check-circle",
          title: "Booking completed",
          description: "Booking #1234 marked as completed",
          timestamp: new Date(now - 2 * 60 * 60 * 1000), // 2 hours ago
        },
        {
          icon: "plus-circle",
          title: "Service created",
          description: "Massage Therapy service added",
          timestamp: new Date(now - 24 * 60 * 60 * 1000), // 1 day ago
        },
        {
          icon: "user-plus",
          title: "Worker added",
          description: "New staff member Sarah joined",
          timestamp: new Date(now - 3 * 24 * 60 * 60 * 1000), // 3 days ago
        },
        {
          icon: "settings",
          title: "Settings updated",
          description: "Business hours updated",
          timestamp: new Date(now - 5 * 24 * 60 * 60 * 1000), // 5 days ago
        },
      ];
    },

    // Setup KPI animations
    setupKPIAnimations() {
      const stats = nordbooking_overview_params.stats || {};

      // Animate values on load with staggered timing
      setTimeout(
        () =>
          this.animateValue(
            "total-revenue-value",
            0,
            stats.total_revenue || 0,
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
            stats.total_bookings || 0,
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
            stats.today_revenue || 0,
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
            stats.completion_rate || 0,
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
            nordbooking_overview_params.currency_symbol +
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
        url: nordbooking_overview_params.ajax_url,
        type: "POST",
        data: {
          action: "nordbooking_get_dashboard_stats",
          nonce: nordbooking_overview_params.nonce,
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
                nordbooking_overview_params.currency_symbol +
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
        nordbooking_overview_params.currency_symbol +
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
        url: nordbooking_overview_params.ajax_url,
        type: "POST",
        data: {
          action: "nordbooking_get_today_revenue",
          nonce: nordbooking_overview_params.nonce,
        },
        success: (response) => {
          if (response.success) {
            const $todayRevenueEl = $("#today-revenue-value");
            const newValue =
              nordbooking_overview_params.currency_symbol +
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
        url: nordbooking_overview_params.ajax_url,
        type: "POST",
        data: {
          action: "nordbooking_get_live_updates",
          nonce: nordbooking_overview_params.nonce,
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
        nordbooking_overview_params.dashboard_base_url + "bookings/";
    },

    // Export dashboard data
    exportDashboardData() {
      $.ajax({
        url: nordbooking_overview_params.ajax_url,
        type: "POST",
        data: {
          action: "nordbooking_export_dashboard_data",
          nonce: nordbooking_overview_params.nonce,
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

    // Initialize customer insights animations
    initCustomerInsightsAnimations() {
      $('.insight-metric').each(function(index) {
        $(this).css({
          opacity: '0',
          transform: 'translateY(20px)'
        });
        
        setTimeout(() => {
          $(this).css({
            opacity: '1',
            transform: 'translateY(0)',
            transition: 'all 0.6s cubic-bezier(0.16, 1, 0.3, 1)'
          });
        }, index * 150);
      });
    },

    // Initialize revenue breakdown animations
    initRevenueBreakdownAnimations() {
      $('.revenue-item').each(function(index) {
        const $item = $(this);
        const $fill = $item.find('.revenue-fill');
        const targetWidth = $fill.css('width');
        
        $fill.css('width', '0%');
        $item.css({
          opacity: '0',
          transform: 'translateX(-20px)'
        });
        
        setTimeout(() => {
          $item.css({
            opacity: '1',
            transform: 'translateX(0)',
            transition: 'all 0.6s cubic-bezier(0.16, 1, 0.3, 1)'
          });
          
          setTimeout(() => {
            $fill.css({
              width: targetWidth,
              transition: 'width 1s ease-out'
            });
          }, 300);
        }, index * 200);
      });
    },

    // Enhanced shadcn/ui style notification system
    showNotification(type, title, message, duration = 4000) {
      const notificationId = 'notification-' + Date.now();
      
      const notification = $(`
        <div id="${notificationId}" class="toast-notification" style="transform: translateX(100%); opacity: 0;">
          <div class="toast-content">
            <div class="toast-icon toast-icon-${type}">
              ${this.getNotificationIcon(type)}
            </div>
            <div class="toast-text">
              <div class="toast-title">${title}</div>
              <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="$('#${notificationId}').remove()">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
        </div>
      `);
      
      $('body').append(notification);
      
      // Animate in
      setTimeout(() => {
        notification.css({
          transform: 'translateX(0)',
          opacity: '1',
          transition: 'all 0.3s cubic-bezier(0.16, 1, 0.3, 1)'
        });
      }, 100);
      
      // Auto remove
      setTimeout(() => {
        notification.css({
          transform: 'translateX(100%)',
          opacity: '0',
          transition: 'all 0.3s ease-in'
        });
        setTimeout(() => notification.remove(), 300);
      }, duration);
    },

    // Get shadcn/ui style notification icon
    getNotificationIcon(type) {
      const icons = {
        success: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22,4 12,14.01 9,11.01"></polyline></svg>',
        error: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
        warning: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        info: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
      };
      return icons[type] || icons.info;
    },

    // Enhanced time ago function
    timeAgo(date) {
      const now = new Date();
      const diffInSeconds = Math.floor((now - new Date(date)) / 1000);
      
      if (diffInSeconds < 60) return 'Just now';
      if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
      if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
      if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
      
      return new Date(date).toLocaleDateString();
    },

    // Escape HTML helper
    escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
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
          ? nordbooking_overview_params.i18n.time_ago_just_now
          : diffInSeconds +
              nordbooking_overview_params.i18n.time_ago_seconds_suffix;
      }

      const diffInMinutes = Math.floor(diffInSeconds / 60);
      if (diffInMinutes < 60) {
        return (
          diffInMinutes +
          nordbooking_overview_params.i18n.time_ago_minutes_suffix
        );
      }

      const diffInHours = Math.floor(diffInMinutes / 60);
      if (diffInHours < 24) {
        return (
          diffInHours + nordbooking_overview_params.i18n.time_ago_hours_suffix
        );
      }

      const diffInDays = Math.floor(diffInHours / 24);
      return diffInDays + nordbooking_overview_params.i18n.time_ago_days_suffix;
    },

    // HTML escape helper
    escapeHtml(text) {
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    },

    // Show notification (integrates with existing NORDBOOKING notification system)
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
    if (typeof feather !== "undefined") {
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

    // Keyboard shortcuts removed as requested
  });
})(jQuery);
    // Utility function to escape HTML
    escapeHtml(text) {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    },

    // Utility function to format time ago
    timeAgo(timestamp) {
      const now = new Date();
      const diff = now - new Date(timestamp);
      const minutes = Math.floor(diff / 60000);
      const hours = Math.floor(diff / 3600000);
      const days = Math.floor(diff / 86400000);

      if (minutes < 1) return 'just now';
      if (minutes < 60) return `${minutes}m ago`;
      if (hours < 24) return `${hours}h ago`;
      if (days < 7) return `${days}d ago`;
      return new Date(timestamp).toLocaleDateString();
    },    
// Update user profile function
    updateUserProfile(firstName, lastName) {
      if (!firstName || !lastName) {
        this.showNotification('error', 'Error', 'First name and last name are required.');
        return;
      }

      $.ajax({
        url: nordbooking_overview_params.ajax_url,
        type: 'POST',
        data: {
          action: 'nordbooking_update_user_profile',
          nonce: nordbooking_overview_params.nonce,
          first_name: firstName,
          last_name: lastName
        },
        success: (response) => {
          if (response.success) {
            this.showNotification('success', 'Success', response.data.message);
            
            // Update display name in user menu if it exists
            $('.user-display-name').text(response.data.display_name);
            $('.user-first-name').text(response.data.first_name);
            $('.user-last-name').text(response.data.last_name);
            
            // Update dashboard header if it exists
            $('.dashboard-subtitle').each(function() {
              const text = $(this).text();
              if (text.includes('Welcome back,')) {
                $(this).text(text.replace(/Welcome back, [^!]+!/, `Welcome back, ${response.data.first_name}!`));
              }
            });
            
          } else {
            this.showNotification('error', 'Error', response.data.message);
          }
        },
        error: (xhr, status, error) => {
          console.error('Profile update error:', error);
          this.showNotification('error', 'Error', 'Failed to update profile. Please try again.');
        }
      });
    },

    // Show notification function
    showNotification(type, title, message) {
      // Simple notification - you can enhance this with a proper notification system
      const notification = $(`
        <div class="notification notification-${type}" style="
          position: fixed;
          top: 20px;
          right: 20px;
          background: ${type === 'success' ? '#10b981' : '#ef4444'};
          color: white;
          padding: 1rem;
          border-radius: 0.5rem;
          box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
          z-index: 9999;
          max-width: 300px;
        ">
          <strong>${title}</strong><br>
          ${message}
        </div>
      `);
      
      $('body').append(notification);
      
      setTimeout(() => {
        notification.fadeOut(300, function() {
          $(this).remove();
        });
      }, 3000);
    },  // Ma
ke Dashboard available globally for external access
  window.MoBookingDashboard = Dashboard;
  window.NordbookingDashboard = Dashboard;

})(jQuery);