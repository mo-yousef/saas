/**
 * Enhanced Services Page JavaScript
 * Improved filtering, search, and modern UI interactions
 * @package MoBooking
 */

(function ($) {
  "use strict";

  /**
   * Services Manager Class
   */
  class MoBookingServicesManager {
    constructor() {
      this.currentPage = 1;
      this.currentRequest = null;
      this.isLoading = false;
      this.filters = {
        search: "",
        status: "",
        orderby: "name",
        order: "asc",
      };

      this.init();
    }

    init() {
      this.cacheElements();
      this.bindEvents();
      this.setupAccessibility();
      this.initializeEnhancements();
    }

    cacheElements() {
      this.$searchInput = $("#services-search");
      this.$statusFilter = $("#status-filter");
      this.$sortFilter = $("#sort-filter");
      this.$servicesGrid = $("#services-grid");
      this.$paginationContainer = $("#services-pagination-container");
      this.$feedbackContainer = $("#services-feedback-container");
      this.$loadingState = $("#loading-state");
      this.$servicesListContainer = $("#services-list-container");
      this.$servicesContent = $(".services-content");
    }

    bindEvents() {
      // Search and filter events
      this.$searchInput.on(
        "input",
        this.debounce(() => this.handleSearch(), 300)
      );
      this.$searchInput.on("keypress", (e) => this.handleSearchKeypress(e));
      this.$statusFilter.on("change", () => this.handleStatusFilter());
      this.$sortFilter.on("change", () => this.handleSortFilter());

      // Pagination events
      $(document).on("click", ".pagination-link:not(.disabled)", (e) =>
        this.handlePagination(e)
      );

      // Service action events
      $(document).on("click", ".service-delete-btn", (e) =>
        this.handleDeleteService(e)
      );
      $(document).on("click", ".service-duplicate-btn", (e) =>
        this.handleDuplicateService(e)
      );

      // Modal events
      this.bindModalEvents();

      // Card interaction events
      this.bindCardEvents();

      // Keyboard navigation
      this.bindKeyboardEvents();
    }

    bindModalEvents() {
      $("#modal-close-btn, #cancel-delete-btn").on("click", () =>
        this.hideModal()
      );
      $("#confirm-delete-btn").on("click", () => this.confirmDeleteService());

      // Close modal on outside click
      $(document).on("click", ".modal-overlay", (e) => {
        if (e.target === e.currentTarget) {
          this.hideModal();
        }
      });
    }

    bindCardEvents() {
      // Enhanced hover effects
      $(document)
        .on("mouseenter", ".service-card", function () {
          $(this).addClass("card-hover");
        })
        .on("mouseleave", ".service-card", function () {
          $(this).removeClass("card-hover");
        });

      // Card focus for accessibility
      $(document)
        .on("focus", ".service-card", function () {
          $(this).addClass("card-focused");
        })
        .on("blur", ".service-card", function () {
          $(this).removeClass("card-focused");
        });
    }

    bindKeyboardEvents() {
      $(document).on("keydown", (e) => {
        // Escape key to close modal
        if (
          e.key === "Escape" &&
          $("#delete-confirmation-modal").is(":visible")
        ) {
          this.hideModal();
        }

        // Ctrl/Cmd + F to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === "f") {
          e.preventDefault();
          this.$searchInput.focus();
        }
      });
    }

    setupAccessibility() {
      // Add ARIA labels
      this.$searchInput.attr({
        "aria-label":
          mobooking_services_params.i18n?.search_services || "Search services",
        role: "searchbox",
      });

      this.$statusFilter.attr({
        "aria-label":
          mobooking_services_params.i18n?.filter_by_status ||
          "Filter by status",
        role: "combobox",
      });

      this.$sortFilter.attr({
        "aria-label":
          mobooking_services_params.i18n?.sort_services || "Sort services",
        role: "combobox",
      });

      // Add roles to elements
      $(".service-card").attr("role", "article");
      $(".btn").attr("role", "button");
      $(".pagination-link").attr("role", "button");
    }

    initializeEnhancements() {
      // Add smooth transitions
      this.$servicesContent.addClass("enhanced-transitions");

      // Initialize tooltips if available
      if (typeof $.fn.tooltip === "function") {
        $(document).on("mouseenter", "[data-tooltip]", function () {
          $(this).tooltip({
            title: $(this).data("tooltip"),
            placement: "top",
            trigger: "hover",
          });
        });
      }

      // Add loading indicators
      this.addLoadingIndicators();
    }

    addLoadingIndicators() {
      const loadingSpinner = `
                <div class="loading-spinner">
                    <div class="spinner-ring"></div>
                </div>
            `;

      if (!$(".loading-spinner").length) {
        $("head").append(`
                    <style>
                        .loading-spinner {
                            display: inline-block;
                            position: relative;
                            width: 20px;
                            height: 20px;
                        }
                        .spinner-ring {
                            box-sizing: border-box;
                            display: block;
                            position: absolute;
                            width: 16px;
                            height: 16px;
                            margin: 2px;
                            border: 2px solid currentColor;
                            border-radius: 50%;
                            animation: spinner-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
                            border-color: currentColor transparent transparent transparent;
                        }
                        @keyframes spinner-ring {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                        .btn-loading {
                            opacity: 0.7;
                            pointer-events: none;
                        }
                        .card-hover {
                            transform: translateY(-2px);
                            box-shadow: 0 8px 25px hsl(var(--primary) / 0.15);
                        }
                        .card-focused {
                            outline: 2px solid hsl(var(--ring));
                            outline-offset: 2px;
                        }
                        .enhanced-transitions * {
                            transition: all 0.2s ease;
                        }
                    </style>
                `);
      }
    }

    // Event Handlers
    handleSearch() {
      this.filters.search = this.$searchInput.val().trim();
      this.fetchServices(1);
    }

    handleSearchKeypress(e) {
      if (e.which === 13) {
        // Enter key
        e.preventDefault();
        this.$searchInput.blur();
        this.handleSearch();
      }
    }

    handleStatusFilter() {
      this.filters.status = this.$statusFilter.val();
      this.fetchServices(1);
    }

    handleSortFilter() {
      const sortValue = this.$sortFilter.val().split("-");
      this.filters.orderby = sortValue[0];
      this.filters.order = sortValue[1];
      this.fetchServices(1);
    }

    handlePagination(e) {
      e.preventDefault();
      const page = parseInt($(e.currentTarget).data("page"));
      if (page && page !== this.currentPage) {
        this.fetchServices(page);
        this.smoothScrollToTop();
      }
    }

    handleDeleteService(e) {
      e.preventDefault();
      const $btn = $(e.currentTarget);
      const serviceId = $btn.data("service-id");
      const serviceName = $btn.data("service-name");

      this.showDeleteConfirmation(serviceId, serviceName);
    }

    handleDuplicateService(e) {
      e.preventDefault();
      const $btn = $(e.currentTarget);
      const serviceId = $btn.data("service-id");

      this.duplicateService(serviceId, $btn);
    }

    // API Methods
    fetchServices(page = 1) {
      if (this.isLoading) return;

      this.isLoading = true;
      this.currentPage = page;

      // Show loading state
      this.showLoadingState();

      // Abort previous request
      if (this.currentRequest) {
        this.currentRequest.abort();
      }

      const requestData = {
        action: "mobooking_get_services",
        nonce: mobooking_services_params.services_nonce,
        search_query: this.filters.search,
        status_filter: this.filters.status,
        orderby: this.filters.orderby,
        order: this.filters.order.toUpperCase(),
        paged: this.currentPage,
        per_page: 20,
      };

      this.currentRequest = $.ajax({
        url: mobooking_services_params.ajax_url,
        type: "POST",
        data: requestData,
        dataType: "json",
        success: (response) => this.handleFetchSuccess(response),
        error: (jqXHR, textStatus, errorThrown) =>
          this.handleFetchError(jqXHR, textStatus, errorThrown),
      });
    }

    handleFetchSuccess(response) {
      this.isLoading = false;
      this.hideLoadingState();

      if (response.success && response.data) {
        const { services, total_count, per_page, current_page } = response.data;
        const totalPages = Math.ceil(total_count / per_page);

        if (services && services.length > 0) {
          this.renderServices(services);
          this.renderPagination(totalPages, current_page);
        } else {
          const isFiltered = this.filters.search || this.filters.status;
          this.renderEmptyState(isFiltered);
        }

        // Update URL without page refresh (if history API is available)
        this.updateURL();
      } else {
        this.showFeedback(
          response.data?.message ||
            "Failed to load services. Please try again.",
          "error"
        );
        this.renderEmptyState();
      }
    }

    handleFetchError(jqXHR, textStatus, errorThrown) {
      this.isLoading = false;
      this.hideLoadingState();

      if (textStatus !== "abort") {
        this.showFeedback(
          "Network error. Please check your connection and try again.",
          "error"
        );
        this.renderEmptyState();
      }
    }

    duplicateService(serviceId, $btn) {
      const originalHtml = $btn.html();
      $btn
        .html(
          '<div class="loading-spinner"><div class="spinner-ring"></div></div> Duplicating...'
        )
        .addClass("btn-loading");

      $.ajax({
        url: mobooking_services_params.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_duplicate_service",
          nonce: mobooking_services_params.services_nonce,
          service_id: serviceId,
        },
        dataType: "json",
        success: (response) => {
          $btn.html(originalHtml).removeClass("btn-loading");

          if (response.success) {
            this.showFeedback(
              response.data.message || "Service duplicated successfully."
            );
            this.fetchServices(this.currentPage);
          } else {
            this.showFeedback(
              response.data?.message || "Failed to duplicate service.",
              "error"
            );
          }
        },
        error: () => {
          $btn.html(originalHtml).removeClass("btn-loading");
          this.showFeedback("Network error. Please try again.", "error");
        },
      });
    }

    deleteService(serviceId) {
      const $btn = $("#confirm-delete-btn");
      const originalHtml = $btn.html();

      $btn
        .html(
          '<div class="loading-spinner"><div class="spinner-ring"></div></div> Deleting...'
        )
        .addClass("btn-loading");

      $.ajax({
        url: mobooking_services_params.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_delete_service",
          nonce: mobooking_services_params.services_nonce,
          service_id: serviceId,
        },
        dataType: "json",
        success: (response) => {
          $btn.html(originalHtml).removeClass("btn-loading");
          this.hideModal();

          if (response.success) {
            this.showFeedback(
              response.data.message || "Service deleted successfully."
            );
            this.fetchServices(this.currentPage);
          } else {
            this.showFeedback(
              response.data?.message || "Failed to delete service.",
              "error"
            );
          }
        },
        error: () => {
          $btn.html(originalHtml).removeClass("btn-loading");
          this.hideModal();
          this.showFeedback("Network error. Please try again.", "error");
        },
      });
    }

    // UI Methods
    showLoadingState() {
      this.$loadingState.show();
      this.$servicesListContainer.hide();
    }

    hideLoadingState() {
      this.$loadingState.hide();
      this.$servicesListContainer.show();
    }

    showFeedback(message, type = "success") {
      const feedbackHtml = `
                <div class="feedback-message feedback-${type}" role="alert">
                    <div class="feedback-content">
                        ${this.getFeedbackIcon(type)}
                        <span>${message}</span>
                    </div>
                    <button type="button" class="feedback-close" aria-label="Close">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18"/><path d="M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            `;

      this.$feedbackContainer.html(feedbackHtml);

      // Auto-hide after 5 seconds
      setTimeout(() => {
        this.$feedbackContainer
          .find(".feedback-message")
          .fadeOut(500, function () {
            $(this).remove();
          });
      }, 5000);

      // Manual close button
      this.$feedbackContainer.on("click", ".feedback-close", function () {
        $(this)
          .closest(".feedback-message")
          .fadeOut(500, function () {
            $(this).remove();
          });
      });
    }

    getFeedbackIcon(type) {
      const icons = {
        success:
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>',
        error:
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6"/><path d="M9 9l6 6"/></svg>',
        warning:
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
      };
      return icons[type] || icons.success;
    }

    showDeleteConfirmation(serviceId, serviceName) {
      $("#delete-confirmation-text").text(
        `Are you sure you want to delete the service "${serviceName}"? This action cannot be undone.`
      );
      $("#delete-confirmation-modal").show();
      $("#confirm-delete-btn").data("service-id", serviceId);
    }

    confirmDeleteService() {
      const serviceId = $("#confirm-delete-btn").data("service-id");
      this.deleteService(serviceId);
    }

    hideModal() {
      $("#delete-confirmation-modal").hide();
    }

    smoothScrollToTop() {
      $("html, body").animate(
        {
          scrollTop: this.$servicesContent.offset().top - 100,
        },
        300
      );
    }

    updateURL() {
      if (history.pushState) {
        const url = new URL(window.location);
        url.searchParams.set("page", this.currentPage);
        if (this.filters.search)
          url.searchParams.set("search", this.filters.search);
        else url.searchParams.delete("search");
        if (this.filters.status)
          url.searchParams.set("status", this.filters.status);
        else url.searchParams.delete("status");
        if (this.filters.orderby !== "name" || this.filters.order !== "asc") {
          url.searchParams.set(
            "sort",
            `${this.filters.orderby}-${this.filters.order}`
          );
        } else {
          url.searchParams.delete("sort");
        }

        history.pushState(null, "", url.toString());
      }
    }

    // Render Methods
    renderServices(services) {
      const servicesHTML = services
        .map((service) => this.renderServiceCard(service))
        .join("");
      this.$servicesListContainer.html(
        `<div class="services-grid" id="services-grid">${servicesHTML}</div>`
      );
    }

    renderServiceCard(service) {
        const priceFormatted = this.formatCurrency(service.price);
        const serviceIcon = service.icon_html || '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>';
        const optionsCount = service.options ? service.options.length : 0;

        const imageHtml = service.image_url
            ? `<img src="${service.image_url}" alt="${service.name}" class="w-full h-48 object-cover">`
            : `<div class="w-full h-48 bg-muted flex items-center justify-center">
                   <svg class="w-12 h-12 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
               </div>`;

        return `
            <div class="card" data-service-id="${service.service_id}">
                <div class="card-header p-0 relative">
                    ${imageHtml}
                    <div class="badge badge-${service.status} absolute top-2 right-2">${service.status.charAt(0).toUpperCase() + service.status.slice(1)}</div>
                </div>
                <div class="card-content p-4">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="text-primary">${serviceIcon}</div>
                        <div>
                            <h3 class="font-semibold">${service.name}</h3>
                            <p class="text-primary font-bold">${priceFormatted}</p>
                        </div>
                    </div>
                    ${service.description ? `<p class="text-sm text-muted-foreground mb-4 line-clamp-3">${service.description}</p>` : ''}
                    <div class="text-xs text-muted-foreground space-y-2">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span>${service.duration} min</span>
                        </div>
                        ${optionsCount > 0 ? `
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M9 12l2 2 4-4"/><path d="M21 12c.552 0 1-.448 1-1V5c0-.552-.448-1-1-1H3c-.552 0-1 .448-1 1v6c0 .552.448 1 1 1h18z"/></svg>
                            <span>${optionsCount} Options</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
                <div class="card-footer p-4 flex gap-2">
                    <a href="/dashboard/service-edit/?service_id=${service.service_id}" class="btn btn-primary w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                        View
                    </a>
                    <button type="button" class="btn btn-destructive service-delete-btn" data-service-id="${service.service_id}" data-service-name="${service.name}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    </button>
                </div>
            </div>
        `;
    }

    renderPagination(totalPages, currentPage) {
      if (totalPages <= 1) {
        this.$paginationContainer.hide();
        return;
      }

      const maxPagesToShow = 5;
      let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
      let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

      if (endPage - startPage + 1 < maxPagesToShow) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
      }

      let paginationHTML = `
                <a href="#" class="pagination-link prev ${
                  currentPage === 1 ? "disabled" : ""
                }" data-page="${currentPage - 1}" aria-label="Previous page">
                    &laquo; Prev
                </a>
            `;

      if (startPage > 1) {
        paginationHTML += `<a href="#" class="pagination-link" data-page="1" aria-label="Page 1">1</a>`;
        if (startPage > 2) {
          paginationHTML += `<span class="pagination-ellipsis" aria-hidden="true">&hellip;</span>`;
        }
      }

      for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `<a href="#" class="pagination-link ${
          i === currentPage ? "active" : ""
        }" data-page="${i}" aria-label="Page ${i}" ${
          i === currentPage ? 'aria-current="page"' : ""
        }>${i}</a>`;
      }

      if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
          paginationHTML += `<span class="pagination-ellipsis" aria-hidden="true">&hellip;</span>`;
        }
        paginationHTML += `<a href="#" class="pagination-link" data-page="${totalPages}" aria-label="Page ${totalPages}">${totalPages}</a>`;
      }

      paginationHTML += `
                <a href="#" class="pagination-link next ${
                  currentPage === totalPages ? "disabled" : ""
                }" data-page="${currentPage + 1}" aria-label="Next page">
                    Next &raquo;
                </a>
            `;

      this.$paginationContainer.html(paginationHTML).show();
    }

    renderEmptyState(isFiltered = false) {
      const emptyStateHTML = isFiltered
        ? `
                <div class="empty-state" role="status" aria-live="polite">
                    <div class="empty-state-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            <line x1="13" y1="9" x2="9" y2="13"></line>
                            <line x1="9" y1="9" x2="13" y2="13"></line>
                        </svg>
                    </div>
                    <h3 class="empty-state-title">No matching services found</h3>
                    <p class="empty-state-description">
                        Try adjusting your search or filter criteria to find what you're looking for.
                    </p>
                    <button type="button" class="btn btn-secondary" id="clear-filters-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18"/>
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                        </svg>
                        Clear Filters
                    </button>
                </div>
            `
        : `
                <div class="empty-state" role="status" aria-live="polite">
                    <div class="empty-state-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="16" y1="13" x2="8" y2="13" />
                            <line x1="16" y1="17" x2="8" y2="17" />
                            <line x1="10" y1="9" x2="8" y2="9" />
                        </svg>
                    </div>
                    <h3 class="empty-state-title">No services yet</h3>
                    <p class="empty-state-description">
                        Create your first service to start accepting bookings from customers.
                    </p>
                    <a href="${mobooking_services_params.add_service_url}" class="add-service-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14" />
                            <path d="M12 5v14" />
                        </svg>
                        Create First Service
                    </a>
                </div>
            `;

      this.$servicesListContainer.html(emptyStateHTML);
      this.$paginationContainer.hide();

      // Bind clear filters event
      $(document).on("click", "#clear-filters-btn", () => this.clearFilters());
    }

    // Utility Methods
    clearFilters() {
      this.filters = {
        search: "",
        status: "",
        orderby: "name",
        order: "asc",
      };

      this.$searchInput.val("");
      this.$statusFilter.val("");
      this.$sortFilter.val("name-asc");

      this.fetchServices(1);
    }

    formatCurrency(amount) {
      const symbol = mobooking_services_params.currency_symbol || "";
      const position = mobooking_services_params.currency_position || "before";
      const formattedAmount = parseFloat(amount).toFixed(2);

      return position === "before"
        ? symbol + formattedAmount
        : formattedAmount + symbol;
    }

    getDefaultServiceIcon() {
      return `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>`;
    }

    debounce(func, delay) {
      let timeout;
      return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
      };
    }

    // Public API
    refresh() {
      this.fetchServices(this.currentPage);
    }

    getCurrentPage() {
      return this.currentPage;
    }

    getFilters() {
      return { ...this.filters };
    }

    setFilters(newFilters) {
      this.filters = { ...this.filters, ...newFilters };
      this.updateUIFromFilters();
      this.fetchServices(1);
    }

    updateUIFromFilters() {
      this.$searchInput.val(this.filters.search);
      this.$statusFilter.val(this.filters.status);
      this.$sortFilter.val(`${this.filters.orderby}-${this.filters.order}`);
    }

    // Analytics and Performance
    trackEvent(action, label = "") {
      if (typeof gtag === "function") {
        gtag("event", action, {
          event_category: "Services",
          event_label: label,
        });
      }
    }

    // Performance monitoring
    measurePerformance(name, fn) {
      const start = performance.now();
      const result = fn();
      const end = performance.now();

      console.log(`Performance: ${name} took ${end - start} milliseconds`);

      return result;
    }
  }

  // Advanced Features
  class ServicesEnhancer {
    constructor(manager) {
      this.manager = manager;
      this.init();
    }

    init() {
      this.addKeyboardShortcuts();
      this.addBulkActions();
      // this.addQuickFilters();
      this.addAdvancedSearch();
      // this.addExportFeatures();
    }

    addKeyboardShortcuts() {
      $(document).on("keydown", (e) => {
        // Alt + N: New service
        if (e.altKey && e.key === "n") {
          e.preventDefault();
          window.location.href = mobooking_services_params.add_service_url;
        }

        // Alt + R: Refresh
        if (e.altKey && e.key === "r") {
          e.preventDefault();
          this.manager.refresh();
        }

        // Alt + C: Clear filters
        if (e.altKey && e.key === "c") {
          e.preventDefault();
          this.manager.clearFilters();
        }
      });
    }

    addBulkActions() {
      // Add bulk selection functionality
      const bulkActionsHTML = `
                <div class="bulk-actions" style="display: none;">
                    <div class="bulk-actions-bar">
                        <label class="bulk-select-all">
                            <input type="checkbox" id="select-all-services"> Select All
                        </label>
                        <div class="bulk-actions-buttons">
                            <button type="button" class="btn btn-secondary" id="bulk-activate">Activate Selected</button>
                            <button type="button" class="btn btn-secondary" id="bulk-deactivate">Deactivate Selected</button>
                            <button type="button" class="btn btn-destructive" id="bulk-delete">Delete Selected</button>
                        </div>
                        <span class="selected-count">0 selected</span>
                    </div>
                </div>
            `;

      $(".services-controls").after(bulkActionsHTML);

      // Add checkboxes to service cards
      $(document).on("DOMNodeInserted", ".service-card", function () {
        const $card = $(this);
        if (!$card.find(".service-checkbox").length) {
          $card.prepend(`
                        <div class="service-checkbox">
                            <input type="checkbox" class="service-select" value="${$card.data(
                              "service-id"
                            )}">
                        </div>
                    `);
        }
      });

      this.bindBulkEvents();
    }

    bindBulkEvents() {
      // Select all functionality
      $(document).on(
        "change",
        "#select-all-services",
        function () {
          const isChecked = $(this).is(":checked");
          $(".service-select").prop("checked", isChecked);
          this.updateBulkActionsVisibility();
        }.bind(this)
      );

      // Individual checkbox changes
      $(document).on("change", ".service-select", () => {
        this.updateBulkActionsVisibility();
      });

      // Bulk action buttons
      $(document).on("click", "#bulk-activate", () =>
        this.performBulkAction("activate")
      );
      $(document).on("click", "#bulk-deactivate", () =>
        this.performBulkAction("deactivate")
      );
      $(document).on("click", "#bulk-delete", () =>
        this.performBulkAction("delete")
      );
    }

    updateBulkActionsVisibility() {
      const selectedCount = $(".service-select:checked").length;
      const $bulkActions = $(".bulk-actions");
      const $selectedCount = $(".selected-count");

      if (selectedCount > 0) {
        $bulkActions.show();
        $selectedCount.text(`${selectedCount} selected`);
      } else {
        $bulkActions.hide();
      }

      // Update select all checkbox state
      const totalCount = $(".service-select").length;
      const $selectAll = $("#select-all-services");

      if (selectedCount === 0) {
        $selectAll.prop("indeterminate", false).prop("checked", false);
      } else if (selectedCount === totalCount) {
        $selectAll.prop("indeterminate", false).prop("checked", true);
      } else {
        $selectAll.prop("indeterminate", true);
      }
    }

    performBulkAction(action) {
      const selectedIds = $(".service-select:checked")
        .map(function () {
          return $(this).val();
        })
        .get();

      if (selectedIds.length === 0) return;

      if (action === "delete") {
        if (
          !confirm(
            `Are you sure you want to delete ${selectedIds.length} services? This action cannot be undone.`
          )
        ) {
          return;
        }
      }

      // Perform bulk action via AJAX
      $.ajax({
        url: mobooking_services_params.ajax_url,
        type: "POST",
        data: {
          action: `mobooking_bulk_${action}_services`,
          nonce: mobooking_services_params.services_nonce,
          service_ids: selectedIds,
        },
        success: (response) => {
          if (response.success) {
            this.manager.showFeedback(
              `${selectedIds.length} services ${action}d successfully.`
            );
            this.manager.refresh();
            $(".bulk-actions").hide();
          } else {
            this.manager.showFeedback(
              response.data?.message || `Failed to ${action} services.`,
              "error"
            );
          }
        },
        error: () => {
          this.manager.showFeedback(
            "Network error. Please try again.",
            "error"
          );
        },
      });
    }

    addQuickFilters() {
      const quickFiltersHTML = `
                <div class="quick-filters">
                    <button type="button" class="quick-filter-btn" data-filter="status" data-value="active">Active Only</button>
                    <button type="button" class="quick-filter-btn" data-filter="status" data-value="inactive">Inactive Only</button>
                    <button type="button" class="quick-filter-btn" data-filter="has_image" data-value="true">With Images</button>
                    <button type="button" class="quick-filter-btn" data-filter="has_options" data-value="true">With Options</button>
                    <button type="button" class="quick-filter-btn" data-filter="price_range" data-value="high">High Value ($100+)</button>
                </div>
            `;

      $(".services-controls").after(quickFiltersHTML);

      $(document).on("click", ".quick-filter-btn", (e) => {
        const $btn = $(e.currentTarget);
        const filter = $btn.data("filter");
        const value = $btn.data("value");

        $btn.toggleClass("active");

        // Apply quick filter
        this.applyQuickFilter(filter, value, $btn.hasClass("active"));
      });
    }

    applyQuickFilter(filter, value, isActive) {
      // Implement quick filter logic based on filter type
      switch (filter) {
        case "status":
          this.manager.$statusFilter.val(isActive ? value : "");
          this.manager.handleStatusFilter();
          break;
        case "has_image":
        case "has_options":
        case "price_range":
          // These would require backend support for additional filters
          console.log(
            `Quick filter ${filter}:${value} ${
              isActive ? "applied" : "removed"
            }`
          );
          break;
      }
    }

    addAdvancedSearch() {
      // Add advanced search modal or expandable section
      const advancedSearchHTML = `
                <div class="advanced-search" style="display: none;">
                    <div class="advanced-search-fields">
                        <div class="field-group">
                            <label>Price Range</label>
                            <input type="number" placeholder="Min" id="price-min">
                            <input type="number" placeholder="Max" id="price-max">
                        </div>
                        <div class="field-group">
                            <label>Duration Range</label>
                            <select id="duration-filter">
                                <option value="">Any Duration</option>
                                <option value="0-30">0-30 minutes</option>
                                <option value="30-60">30-60 minutes</option>
                                <option value="60-120">1-2 hours</option>
                                <option value="120+">2+ hours</option>
                            </select>
                        </div>
                        <div class="field-group">
                            <label>Date Created</label>
                            <input type="date" id="date-from">
                            <input type="date" id="date-to">
                        </div>
                    </div>
                    <div class="advanced-search-actions">
                        <button type="button" class="btn btn-primary" id="apply-advanced-search">Apply</button>
                        <button type="button" class="btn btn-secondary" id="reset-advanced-search">Reset</button>
                    </div>
                </div>
            `;

      // Add toggle button
      $(".search-container").append(
        '<button type="button" class="btn btn-secondary advanced-search-toggle">Advanced</button>'
      );
      $(".services-controls").after(advancedSearchHTML);

      $(document).on("click", ".advanced-search-toggle", () => {
        $(".advanced-search").slideToggle();
      });
    }

    addExportFeatures() {
      const exportHTML = `
                <div class="export-options">
                    <button type="button" class="btn btn-secondary export-btn" data-format="csv">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <line x1="10" y1="9" x2="8" y2="9"/>
                        </svg>
                        Export CSV
                    </button>
                    <button type="button" class="btn btn-secondary export-btn" data-format="json">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        Export JSON
                    </button>
                </div>
            `;

      $(".services-header .header-content").after(exportHTML);

      $(document).on("click", ".export-btn", (e) => {
        const format = $(e.currentTarget).data("format");
        this.exportServices(format);
      });
    }

    exportServices(format) {
      const filters = this.manager.getFilters();

      $.ajax({
        url: mobooking_services_params.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_export_services",
          nonce: mobooking_services_params.services_nonce,
          format: format,
          filters: filters,
        },
        success: (response) => {
          if (response.success) {
            // Create download link
            const blob = new Blob([response.data.content], {
              type: format === "csv" ? "text/csv" : "application/json",
            });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            link.download = `services_export.${format}`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);

            this.manager.showFeedback("Services exported successfully!");
          } else {
            this.manager.showFeedback("Failed to export services.", "error");
          }
        },
        error: () => {
          this.manager.showFeedback(
            "Export failed. Please try again.",
            "error"
          );
        },
      });
    }
  }

  // Initialize when DOM is ready
  $(document).ready(function () {
    // Check if we're on the services page
    if (typeof mobooking_services_params === "undefined") {
      console.warn("MoBooking Services: Required parameters not found");
      return;
    }

    // Initialize the main services manager
    const servicesManager = new MoBookingServicesManager();

    // Initialize enhanced features
    const servicesEnhancer = new ServicesEnhancer(servicesManager);

    // Make manager globally accessible
    window.MoBookingServices = servicesManager;

    // Initialize URL parameters if present
    const urlParams = new URLSearchParams(window.location.search);
    const initialFilters = {};

    if (urlParams.get("search"))
      initialFilters.search = urlParams.get("search");
    if (urlParams.get("status"))
      initialFilters.status = urlParams.get("status");
    if (urlParams.get("sort")) {
      const [orderby, order] = urlParams.get("sort").split("-");
      initialFilters.orderby = orderby;
      initialFilters.order = order;
    }

    if (Object.keys(initialFilters).length > 0) {
      servicesManager.setFilters(initialFilters);
    }

    console.log("MoBooking Services: Enhanced page initialized successfully");
  });
})(jQuery);
