jQuery(document).ready(function ($) {
  "use strict";

  // Enhanced parameter validation
  if (typeof mobooking_services_params === "undefined") {
    console.error(
      "MoBooking: mobooking_services_params is not defined. Scripts may not be properly localized."
    );
    // Provide fallback to prevent crashes
    window.mobooking_services_params = {
      ajax_url: "/wp-admin/admin-ajax.php",
      nonce: "",
      i18n: {},
      currency_symbol: "$",
    };
  }

  // Validate required parameters
  if (!mobooking_services_params.ajax_url) {
    console.error("MoBooking: AJAX URL is missing from localized parameters");
    return;
  }

  if (!mobooking_services_params.nonce) {
    console.error("MoBooking: Nonce is missing from localized parameters");
    return;
  }

  const servicesListContainer = $("#mobooking-services-list-container");
  const serviceFormContainer = $("#mobooking-service-form-container");
  const serviceForm = $("#mobooking-service-form");
  const serviceFormTitle = $("#mobooking-service-form-title");
  const serviceIdField = $("#mobooking-service-id");
  const feedbackDiv = $("#mobooking-service-form-feedback").hide();
  const optionsListContainer = $("#mobooking-service-options-list");
  const addServiceOptionBtn = $("#mobooking-add-service-option-btn");
  const optionTemplateHtml = $("#mobooking-service-option-template").html();

  if (optionTemplateHtml) {
    $("#mobooking-service-option-template").remove();
  }

  const paginationContainer = $("#mobooking-services-pagination-container");
  const mainServiceItemTemplate = $("#mobooking-service-item-template").html();
  $("#mobooking-service-item-template").remove();

  let currentServiceFilters = {
    paged: 1,
    per_page: 20,
    status_filter: "",
    category_filter: "",
    search_query: "",
    orderby: "name",
    order: "ASC",
  };
  let servicesDataCache = {};
  let optionIndex = 0;

  // Basic XSS protection for display
  function sanitizeHTML(str) {
    if (typeof str !== "string") return "";
    var temp = document.createElement("div");
    temp.textContent = str;
    return temp.innerHTML;
  }

  // Updated populateForm function to support full CRUD
  function populateForm(service) {
    serviceForm[0].reset();
    serviceIdField.val(service.service_id);
    $("#mobooking-service-name").val(service.name);
    $("#mobooking-service-description").val(service.description);
    $("#mobooking-service-price").val(parseFloat(service.price).toFixed(2));
    $("#mobooking-service-duration").val(service.duration);
    $("#mobooking-service-category").val(service.category);
    $("#mobooking-service-icon").val(service.icon);
    $("#mobooking-service-image-url").val(service.image_url);
    $("#mobooking-service-status").val(service.status);

    // Only clear options when switching to a completely different service
    const currentlyLoadedServiceId = optionsListContainer.attr(
      "data-current-service-id"
    );
    const isNewService = !service.service_id;
    const isDifferentService =
      service.service_id && service.service_id !== currentlyLoadedServiceId;

    // Only clear and reload options if we're switching services or loading for the first time
    if (isNewService || isDifferentService || !currentlyLoadedServiceId) {
      optionsListContainer.empty();
      optionIndex = 0;

      // Set the current service ID for tracking
      optionsListContainer.attr(
        "data-current-service-id",
        service.service_id || "new"
      );

      // Load saved options for existing service
      if (
        service.service_id &&
        service.options &&
        Array.isArray(service.options) &&
        service.options.length
      ) {
        service.options.forEach(function (opt) {
          optionIndex++;
          const newOptionRow = $(optionTemplateHtml);

          // Mark this as a saved option
          newOptionRow.attr("data-option-source", "saved");
          newOptionRow.attr("data-option-id", opt.option_id);

          newOptionRow
            .find('input[name="options[][option_id]"]')
            .val(opt.option_id || "");
          newOptionRow
            .find('input[name="options[][name]"]')
            .val(opt.name || "");
          newOptionRow
            .find('textarea[name="options[][description]"]')
            .val(opt.description || "");
          newOptionRow
            .find('select[name="options[][type]"]')
            .val(opt.type || "checkbox");

          const isRequiredCheckbox = newOptionRow.find(
            'input[name="options[][is_required_cb]"]'
          );
          const isRequiredHidden = newOptionRow.find(
            'input[name="options[][is_required]"]'
          );
          if (
            opt.is_required &&
            (opt.is_required === "1" ||
              opt.is_required === 1 ||
              opt.is_required === true)
          ) {
            isRequiredCheckbox.prop("checked", true);
            isRequiredHidden.val("1");
          } else {
            isRequiredCheckbox.prop("checked", false);
            isRequiredHidden.val("0");
          }

          newOptionRow
            .find('select[name="options[][price_impact_type]"]')
            .val(opt.price_impact_type || "");
          newOptionRow
            .find('input[name="options[][price_impact_value]"]')
            .val(opt.price_impact_value || "");

          let optionValuesText = "";
          if (opt.option_values) {
            if (typeof opt.option_values === "string") {
              try {
                const parsedValues = JSON.parse(opt.option_values);
                optionValuesText = JSON.stringify(parsedValues, null, 2);
              } catch (e) {
                optionValuesText = opt.option_values;
              }
            } else if (typeof opt.option_values === "object") {
              optionValuesText = JSON.stringify(opt.option_values, null, 2);
            }
          }
          newOptionRow
            .find('textarea[name="options[][option_values]"]')
            .val(optionValuesText);
          newOptionRow
            .find('input[name="options[][sort_order]"]')
            .val(opt.sort_order || optionIndex);

          optionsListContainer.append(newOptionRow);
          toggleOptionDetailFields(newOptionRow);
        });
      }

      // Show appropriate message if no options
      if (
        optionsListContainer.children(".mobooking-service-option-row")
          .length === 0
      ) {
        if (!service.service_id) {
          optionsListContainer.html(
            "<p><em>" +
              (mobooking_services_params.i18n.add_options_for_new_service ||
                'Click "Add Option" below to create service options. You can save everything together when done.') +
              "</em></p>"
          );
        } else {
          optionsListContainer.html(
            "<p><em>" +
              (mobooking_services_params.i18n.no_options_for_service ||
                'No options configured for this service yet. Click "Add Option" to create one.') +
              "</em></p>"
          );
        }
      }
    }

    // Always enable the Add Option button for CRUD operations
    addServiceOptionBtn.prop("disabled", false);
  }

  // Show/hide option_values and price_impact_value fields based on selections
  function toggleOptionDetailFields($row) {
    const type = $row.find(".mobooking-option-type").val();
    if (type === "select" || type === "radio") {
      $row.find(".mobooking-option-values-field").slideDown();
    } else {
      $row.find(".mobooking-option-values-field").slideUp();
    }
    const priceType = $row.find(".mobooking-option-price-type").val();
    if (priceType && priceType !== "") {
      $row.find(".mobooking-option-price-value-field").slideDown();
    } else {
      $row.find(".mobooking-option-price-value-field").slideUp();
      $row.find('input[name="options[][price_impact_value]"]').val("");
    }
  }

  // Helper function to update sort orders
  function updateOptionSortOrders() {
    optionsListContainer
      .find(".mobooking-service-option-row")
      .each(function (idx, el) {
        $(el)
          .find('input[name="options[][sort_order]"]')
          .val(idx + 1);
      });
  }

  optionsListContainer.on(
    "change",
    ".mobooking-option-type, .mobooking-option-price-type",
    function () {
      toggleOptionDetailFields(
        $(this).closest(".mobooking-service-option-row")
      );
    }
  );

  // Updated "Add Option" button click handler
  $("#mobooking-add-service-option-btn").on("click", function () {
    if ($(this).is(":disabled")) return;

    // Clear placeholder message if it exists
    if (
      optionsListContainer.find("p").length &&
      optionsListContainer.children().length === 1 &&
      optionsListContainer.find("p em").length > 0
    ) {
      optionsListContainer.empty();
    }

    optionIndex++;
    const newOptionRow = $(optionTemplateHtml);

    // Mark this as a newly added option
    newOptionRow.attr("data-option-source", "new");
    newOptionRow.attr("data-option-id", "new-" + optionIndex);

    // Set default values for new option
    newOptionRow.find('input[name="options[][option_id]"]').val("");
    newOptionRow.find('select[name="options[][type]"]').val("checkbox");
    newOptionRow.find('input[name="options[][is_required]"]').val("0");

    optionsListContainer.append(newOptionRow);
    toggleOptionDetailFields(newOptionRow);

    // Update sort order for all options
    updateOptionSortOrders();

    newOptionRow.find('input[name="options[][name]"]').focus();
  });

  // Updated remove button handler to support both saved and new options
  optionsListContainer.on(
    "click",
    ".mobooking-remove-service-option-btn",
    function () {
      const $row = $(this).closest(".mobooking-service-option-row");
      const optionSource = $row.attr("data-option-source");

      // For saved options, confirm deletion
      if (optionSource === "saved") {
        if (
          !confirm(
            mobooking_services_params.i18n.confirm_delete_option ||
              "Are you sure you want to delete this option? This action cannot be undone."
          )
        ) {
          return;
        }
      }

      $row.remove();
      updateOptionSortOrders();

      // Show placeholder message if no options left
      if (
        optionsListContainer.children(".mobooking-service-option-row")
          .length === 0
      ) {
        const currentServiceId = optionsListContainer.attr(
          "data-current-service-id"
        );
        if (!currentServiceId || currentServiceId === "new") {
          optionsListContainer.html(
            "<p><em>" +
              (mobooking_services_params.i18n.add_options_for_new_service ||
                'Click "Add Option" below to create service options.') +
              "</em></p>"
          );
        } else {
          optionsListContainer.html(
            "<p><em>" +
              (mobooking_services_params.i18n.no_options_for_service ||
                'No options configured for this service yet. Click "Add Option" to create one.') +
              "</em></p>"
          );
        }
      }
    }
  );

  // Handle 'is_required' checkbox to hidden field sync
  optionsListContainer.on(
    "change",
    'input[name="options[][is_required_cb]"]',
    function () {
      const $checkbox = $(this);
      const $hiddenInput = $checkbox
        .closest(".mobooking-service-option-row")
        .find('input[name="options[][is_required]"]');
      $hiddenInput.val($checkbox.is(":checked") ? "1" : "0");
    }
  );

  // Function to fetch and render services
  function fetchAndRenderServices(page = 1, filters = {}) {
    currentServiceFilters.paged = page;
    currentServiceFilters = { ...currentServiceFilters, ...filters };

    servicesListContainer.html(
      "<p>" +
        (mobooking_services_params.i18n.loading_services ||
          "Loading services...") +
        "</p>"
    );
    paginationContainer.empty();
    servicesDataCache = {};

    let ajaxData = {
      action: "mobooking_get_services",
      nonce: mobooking_services_params.nonce,
      ...currentServiceFilters,
    };

    $.ajax({
      url: mobooking_services_params.ajax_url,
      type: "POST",
      data: ajaxData,
      dataType: "json",
      success: function (response) {
        servicesListContainer.empty();
        if (
          response.success &&
          response.data.services &&
          response.data.services.length
        ) {
          response.data.services.forEach(function (service) {
            servicesDataCache[service.service_id] = service;
            let serviceDataForTemplate = { ...service };
            serviceDataForTemplate.formatted_price =
              mobooking_services_params.currency_symbol +
              parseFloat(service.price).toFixed(2);
            serviceDataForTemplate.display_status =
              service.status.charAt(0).toUpperCase() + service.status.slice(1);

            let itemHtml = mainServiceItemTemplate;
            for (const key in serviceDataForTemplate) {
              itemHtml = itemHtml.replace(
                new RegExp("<%=\\s*" + key + "\\s*%>", "g"),
                sanitizeHTML(String(serviceDataForTemplate[key]))
              );
            }
            servicesListContainer.append(itemHtml);
          });
          renderPagination(
            response.data.total_count,
            response.data.per_page,
            response.data.current_page
          );
        } else if (response.success) {
          servicesListContainer.html(
            "<p>" +
              (mobooking_services_params.i18n.no_services_found ||
                "No services found.") +
              "</p>"
          );
        } else {
          servicesListContainer.html(
            "<p>" +
              (response.data.message ||
                mobooking_services_params.i18n.error_loading_services ||
                "Error loading services.") +
              "</p>"
          );
        }
      },
      error: function () {
        servicesListContainer.html(
          "<p>" +
            (mobooking_services_params.i18n.error_loading_services_ajax ||
              "AJAX error loading services.") +
            "</p>"
        );
      },
    });
  }

  function renderPagination(totalCount, perPage, currentPage) {
    const totalPages = Math.ceil(totalCount / perPage);
    if (totalPages <= 1) {
      paginationContainer.empty();
      return;
    }

    let paginationHtml = "<ul class='page-numbers'>";

    if (currentPage > 1) {
      paginationHtml += `<li><a href="#" class="page-numbers" data-page="${
        currentPage - 1
      }">&laquo; Prev</a></li>`;
    }

    for (let i = 1; i <= totalPages; i++) {
      if (i === currentPage) {
        paginationHtml += `<li><span class="page-numbers current">${i}</span></li>`;
      } else {
        paginationHtml += `<li><a href="#" class="page-numbers" data-page="${i}">${i}</a></li>`;
      }
    }

    if (currentPage < totalPages) {
      paginationHtml += `<li><a href="#" class="page-numbers" data-page="${
        currentPage + 1
      }">Next &raquo;</a></li>`;
    }

    paginationHtml += "</ul>";
    paginationContainer.html(paginationHtml);
  }

  // Updated edit service click handler
  servicesListContainer.on("click", ".mobooking-edit-service-btn", function () {
    const serviceId = $(this).data("id");
    const $editButton = $(this);
    const originalButtonText = $editButton.text();

    console.log("Attempting to fetch details for service ID:", serviceId);

    if (!serviceId) {
      console.error("Service ID for edit is missing or undefined.");
      alert(
        mobooking_services_params.i18n.error_missing_service_id ||
          "Error: Could not identify the service ID for editing."
      );
      return;
    }

    // Get service details from cache or fetch via AJAX
    let service = servicesDataCache[serviceId];
    if (service) {
      populateForm(service);
      serviceFormTitle.text(
        mobooking_services_params.i18n.edit_service || "Edit Service"
      );
      feedbackDiv.empty().hide();
      $("#mobooking-service-form-modal-backdrop").show();
      serviceFormContainer.show();
      $("body").addClass("mobooking-modal-open");
      $("#mobooking-service-name").focus();
    } else {
      // Fetch service details via AJAX if not in cache
      $editButton.prop("disabled", true).text("Loading...");

      $.ajax({
        url: mobooking_services_params.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_get_service_details",
          nonce: mobooking_services_params.nonce,
          service_id: serviceId,
        },
        dataType: "json",
        success: function (response) {
          if (response.success && response.data.service) {
            service = response.data.service;
            servicesDataCache[serviceId] = service;
            populateForm(service);
            serviceFormTitle.text(
              mobooking_services_params.i18n.edit_service || "Edit Service"
            );
            feedbackDiv.empty().hide();
            $("#mobooking-service-form-modal-backdrop").show();
            serviceFormContainer.show();
            $("body").addClass("mobooking-modal-open");
            $("#mobooking-service-name").focus();
          } else {
            showGlobalFeedback(
              response.data.message || "Error loading service details.",
              "error"
            );
          }
        },
        error: function (xhr, status, error) {
          let errorMessage = "AJAX error loading service details.";

          if (xhr.status === 400) {
            errorMessage =
              "Bad request. Please check if you have the proper permissions.";
          } else if (xhr.status === 403) {
            errorMessage = "Access denied. Please log in again.";
          } else if (xhr.status === 404) {
            errorMessage = "Service not found or may have been deleted.";
          } else if (xhr.status === 500) {
            errorMessage = "Server error occurred. Please try again later.";
          } else if (status === "timeout") {
            errorMessage = "Request timed out. Please try again.";
          }

          alert(errorMessage);
        },
        complete: function () {
          $editButton.prop("disabled", false).text(originalButtonText);
        },
      });
    }
  });

  // Delegated Delete Service
  servicesListContainer.on(
    "click",
    ".mobooking-delete-service-btn",
    function () {
      const serviceId = $(this).data("id");
      const serviceName = $(this)
        .closest(".mobooking-service-item")
        .find("h3")
        .text();
      if (
        confirm(
          (
            mobooking_services_params.i18n.confirm_delete_service ||
            'Are you sure you want to delete "%s"?'
          ).replace("%s", serviceName)
        )
      ) {
        $.ajax({
          url: mobooking_services_params.ajax_url,
          type: "POST",
          data: {
            action: "mobooking_delete_service",
            nonce: mobooking_services_params.nonce,
            service_id: serviceId,
          },
          dataType: "json",
          success: function (response) {
            if (response.success) {
              showGlobalFeedback(
                response.data.message ||
                  mobooking_services_params.i18n.service_deleted ||
                  "Service deleted.",
                "success"
              );
              fetchAndRenderServices(
                currentServiceFilters.paged,
                currentServiceFilters
              );
            } else {
              showGlobalFeedback(
                response.data.message ||
                  mobooking_services_params.i18n.error_deleting_service,
                "error"
              );
            }
          },
          error: function () {
            showGlobalFeedback(
              mobooking_services_params.i18n.error_deleting_service_ajax ||
                "AJAX error deleting service.",
              "error"
            );
          },
        });
      }
    }
  );

  // Updated "Add New Service" button click handler
  $("#mobooking-add-new-service-btn").on("click", function () {
    populateForm({
      service_id: "",
      name: "",
      description: "",
      price: "0.00",
      duration: "30",
      category: "",
      icon: "",
      image_url: "",
      status: "active",
      options: [],
    });
    serviceFormTitle.text(
      mobooking_services_params.i18n.add_new_service || "Add New Service"
    );
    feedbackDiv.empty().hide();

    $("#mobooking-service-form-modal-backdrop").show();
    serviceFormContainer.show();
    $("body").addClass("mobooking-modal-open");
    $("#mobooking-service-name").focus();
  });

  // Updated cancel handler to clear tracking
  $("#mobooking-cancel-service-form").on("click", function () {
    serviceFormContainer.hide();
    $("#mobooking-service-form-modal-backdrop").hide();
    $("body").removeClass("mobooking-modal-open");
    feedbackDiv.empty().hide();

    // Clear current service tracking
    optionsListContainer.removeAttr("data-current-service-id");
  });

  // Show global feedback message
  function showGlobalFeedback(message, type = "info") {
    $(".mobooking-global-feedback").remove();
    const feedbackHtml = `<div class="mobooking-global-feedback ${type}" style="padding:10px; margin:10px 0; border-radius:4px; background-color:${
      type === "success" ? "#d4edda" : "#f8d7da"
    }; border-color:${type === "success" ? "#c3e6cb" : "#f5c6cb"}; color:${
      type === "success" ? "#155724" : "#721c24"
    };">${sanitizeHTML(message)}</div>`;
    $("#mobooking-add-new-service-btn").after(feedbackHtml);
    setTimeout(function () {
      $(".mobooking-global-feedback").fadeOut(500, function () {
        $(this).remove();
      });
    }, 5000);
  }

  // Pagination click
  paginationContainer.on("click", "a.page-numbers", function (e) {
    e.preventDefault();
    const page =
      $(this).data("page") ||
      $(this).attr("href").split("paged=")[1]?.split("&")[0];
    if (page) {
      fetchAndRenderServices(parseInt(page), currentServiceFilters);
    }
  });

  // Form Submission (Add/Update)
  serviceForm.on("submit", function (e) {
    e.preventDefault();
    feedbackDiv.empty().removeClass("success error").hide();

    let dataToSend = {};
    $(this)
      .serializeArray()
      .forEach(function (item) {
        dataToSend[item.name] = item.value;
      });
    dataToSend.action = "mobooking_save_service";
    dataToSend.nonce = mobooking_services_params.nonce;

    // Collect Service Options
    let service_options = [];
    optionsListContainer
      .find(".mobooking-service-option-row")
      .each(function (idx) {
        const $row = $(this);
        const option = {
          option_id:
            $row.find('input[name="options[][option_id]"]').val() || "",
          name: $row.find('input[name="options[][name]"]').val(),
          description: $row
            .find('textarea[name="options[][description]"]')
            .val(),
          type: $row.find('select[name="options[][type]"]').val(),
          is_required: $row.find('input[name="options[][is_required]"]').val(),
          price_impact_type: $row
            .find('select[name="options[][price_impact_type]"]')
            .val(),
          price_impact_value:
            $row.find('input[name="options[][price_impact_value]"]').val() ||
            null,
          option_values: $row
            .find('textarea[name="options[][option_values]"]')
            .val(),
          sort_order: idx + 1,
        };

        if (option.name && option.name.trim() !== "") {
          if (
            (option.type === "select" || option.type === "radio") &&
            option.option_values.trim() !== ""
          ) {
            try {
              JSON.parse(option.option_values.trim());
            } catch (e) {
              feedbackDiv
                .text(
                  (mobooking_services_params.i18n.invalid_json_for_option ||
                    "Invalid JSON in Option Values for: ") +
                    sanitizeHTML(option.name)
                )
                .removeClass("success")
                .addClass("error")
                .show();
              throw new Error("Invalid JSON for option: " + option.name);
            }
          }
          service_options.push(option);
        }
      });

    if (service_options.length > 0) {
      dataToSend.service_options = JSON.stringify(service_options);
    }

    const submitButton = $(this).find('[type="submit"]');
    const originalButtonText = submitButton.text();
    submitButton.prop("disabled", true).text("Saving...");

    $.ajax({
      url: mobooking_services_params.ajax_url,
      type: "POST",
      data: dataToSend,
      dataType: "json",
      success: function (response) {
        if (response.success) {
          feedbackDiv
            .text(
              response.data.message ||
                mobooking_services_params.i18n.service_saved ||
                "Service saved successfully."
            )
            .removeClass("error")
            .addClass("success")
            .show();

          // Update the service ID if it was a new service
          if (response.data.service_id && !serviceIdField.val()) {
            serviceIdField.val(response.data.service_id);
            // Update the tracking ID
            optionsListContainer.attr(
              "data-current-service-id",
              response.data.service_id
            );
          }

          // Update cache with new data
          if (response.data.service) {
            servicesDataCache[response.data.service.service_id] =
              response.data.service;
          }

          // Refresh the services list
          setTimeout(function () {
            serviceFormContainer.hide();
            $("#mobooking-service-form-modal-backdrop").hide();
            $("body").removeClass("mobooking-modal-open");
            fetchAndRenderServices(
              currentServiceFilters.paged,
              currentServiceFilters
            );
          }, 1500);
        } else {
          feedbackDiv
            .text(
              response.data.message ||
                mobooking_services_params.i18n.error_saving_service ||
                "Error saving service."
            )
            .removeClass("success")
            .addClass("error")
            .show();
        }
      },
      error: function () {
        feedbackDiv
          .text(
            mobooking_services_params.i18n.error_saving_service_ajax ||
              "AJAX error saving service. Check console."
          )
          .removeClass("success")
          .addClass("error")
          .show();
      },
      complete: function () {
        submitButton.prop("disabled", false).text(originalButtonText);
      },
    });
  });

  // Initialize from cached data if available
  if (typeof mobooking_initial_services_list_for_cache !== "undefined") {
    mobooking_initial_services_list_for_cache.forEach(function (service) {
      servicesDataCache[service.service_id] = service;
    });
  }

  // Close modal if clicking on backdrop
  $("#mobooking-service-form-modal-backdrop").on("click", function () {
    serviceFormContainer.hide();
    $(this).hide();
    $("body").removeClass("mobooking-modal-open");
    feedbackDiv.empty().hide();
    optionsListContainer.removeAttr("data-current-service-id");
  });
});

// Additional debugging function to test AJAX connectivity
function testMoBookingAjax() {
  if (typeof mobooking_services_params === "undefined") {
    console.error("Cannot test AJAX - parameters not loaded");
    return;
  }

  jQuery.ajax({
    url: mobooking_services_params.ajax_url,
    type: "POST",
    data: {
      action: "mobooking_get_services",
      nonce: mobooking_services_params.nonce,
      per_page: 1,
    },
    success: function (response) {
      console.log("AJAX Test Success:", response);
    },
    error: function (xhr, status, error) {
      console.error("AJAX Test Failed:", {
        status: xhr.status,
        statusText: xhr.statusText,
        error: error,
        response: xhr.responseText,
      });
    },
  });
}

// Expose test function globally for debugging
window.testMoBookingAjax = testMoBookingAjax;
