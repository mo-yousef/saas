jQuery(document).ready(function ($) {
  ("use strict");

  if (typeof mobooking_services_params === "undefined") {
    console.error("MoBooking: mobooking_services_params is not defined.");
    // Provide fallback to prevent crashes, though functionality will be impaired
    window.mobooking_services_params = {
      ajax_url: "/wp-admin/admin-ajax.php", // This is a common default
      nonce: "",
      i18n: {},
      currency_code: "USD", // Fallback currency code
      site_url: "", // Should be localized
      dashboard_slug: "dashboard", // Default slug
    };
  }

  const currencyCode = mobooking_services_params.currency_code || "USD";
  const siteUrl = mobooking_services_params.site_url || "";
  const dashboardSlug = mobooking_services_params.dashboard_slug || "dashboard";
  const servicesListPageUrl =
    siteUrl + (siteUrl.endsWith("/") ? "" : "/") + dashboardSlug + "/services/";

  // Common helper
  function sanitizeHTML(str) {
    if (typeof str !== "string") return "";
    var temp = document.createElement("div");
    temp.textContent = str;
    return temp.innerHTML;
  }

  // --- Logic for Service Add/Edit Page (page-service-edit.php) ---
  if ($("#mobooking-service-form").length) {
    const serviceForm = $("#mobooking-service-form");
    const feedbackDiv = $("#mobooking-service-form-feedback").hide();

    // FIXED: Update container selectors to match actual page template
    const optionsListContainer = $("#options-container"); // Changed from "#mobooking-service-options-list"
    const addServiceOptionBtn = $("#add-option-btn"); // Changed from "#mobooking-add-service-option-btn"

    // Icon related selectors (keep as is)
    const iconPreviewDiv = $("#mobooking-service-icon-preview");
    const iconValueInput = $("#mobooking-service-icon-value");
    const iconUploadInput = $("#mobooking-service-icon-upload");
    const presetIconsWrapper = $("#mobooking-preset-icons-wrapper");
    const removeIconBtn = $("#mobooking-remove-service-icon-btn");
    const noIconText = iconPreviewDiv.find(".mobooking-no-icon-text");

    // Image related selectors (keep as is)
    const imagePreview = $("#mobooking-service-image-preview");
    const imageUrlInput = $("#mobooking-service-image-url-value");
    const imageUploadInput = $("#mobooking-service-image-upload");
    const triggerImageUploadBtn = $(
      "#mobooking-trigger-service-image-upload-btn"
    );
    const removeImageBtn = $("#mobooking-remove-service-image-btn");
    const placeholderImageUrl =
      mobooking_services_params.placeholder_image_url ||
      "data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22150%22%20height%3D%22150%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20150%20150%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_17ea872690d%20text%20%7B%20fill%3A%23AAAAAA%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_17ea872690d%22%3E%3Crect%20width%3D%22150%22%20height%3D%22150%22%20fill%3D%22%23EEEEEE%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2250.00303268432617%22%20y%3D%2279.5%22%3E150x150%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E";

    // Status Toggle selectors (keep as is)
    const statusToggle = $("#mobooking-service-status-toggle");
    const statusHiddenInput = $("#mobooking-service-status");
    const statusText = $("#mobooking-service-status-text");

    // FIXED: Use correct template IDs that match the page templates
    const optionTemplateHtml = $("#option-template").html();
    if (optionTemplateHtml) $("#option-template").remove();
    else
      console.error(
        "MoBooking: Service option template not found on edit page!"
      );

    const choiceTemplateHTML = $("#choice-template").html();
    if (choiceTemplateHTML) $("#choice-template").remove();
    else
      console.error("MoBooking: Choice item template not found on edit page!");

    // FIXED: Update to use correct class names
    let optionClientIndex = optionsListContainer.find(".mb-option-item").length; // Changed from ".mobooking-service-option-row"

    // ... (keep icon and image functions as is) ...

    // FIXED: Update the add option button click handler
    addServiceOptionBtn.on("click", function () {
      // Validation logic (keep as is)
      const requiredFields = [
        { selector: "#mobooking-service-name", label: "Service Name" },
        { selector: "#mobooking-service-price", label: "Price" },
        { selector: "#mobooking-service-duration", label: "Duration" },
      ];

      let missingFields = [];
      requiredFields.forEach(function (field) {
        const value = $(field.selector).val();
        if (!value || value.trim() === "") {
          missingFields.push(field.label);
        }
      });

      if (missingFields.length > 0) {
        let message =
          mobooking_services_params.i18n.fill_required_fields ||
          "Please fill in required fields: %s";
        message = message.replace("%s", missingFields.join(", "));

        feedbackDiv
          .text(message)
          .removeClass("success error notice")
          .addClass("notice")
          .show();
        return;
      } else {
        feedbackDiv.empty().removeClass("success error notice").hide();
      }

      if (!optionTemplateHtml) {
        console.error("Cannot add option: template missing.");
        return;
      }

      // FIXED: Update to use correct class and structure
      optionsListContainer.find(".mb-no-options").remove(); // Changed from "p.mobooking-no-options-yet"
      optionClientIndex++;

      // Clone and process template
      let newOptionHtml = optionTemplateHtml.replace(
        /{INDEX}/g,
        optionClientIndex
      );
      const newOptionRow = $(newOptionHtml);

      // Set unique attributes
      newOptionRow.attr("data-option-index", optionClientIndex);

      // Update input names for indexed array
      newOptionRow.find("input, textarea, select").each(function () {
        const $input = $(this);
        let name = $input.attr("name");
        if (name && name.includes("[]")) {
          name = name.replace("[]", `[${optionClientIndex}]`);
          $input.attr("name", name);
        }
      });

      // Set sort order
      newOptionRow
        .find('input[name*="[sort_order]"]')
        .val(optionClientIndex + 1);

      optionsListContainer.append(newOptionRow);
      updateOptionSortOrders();
      newOptionRow.find('input[name*="[name]"]').focus();
    });

    // FIXED: Update event handlers to use correct class names
    optionsListContainer.on("click", ".mb-option-remove-btn", function () {
      // Changed from ".mobooking-remove-service-option-btn"
      const $row = $(this).closest(".mb-option-item"); // Changed from ".mobooking-service-option-row"
      const existingOptionId = $row.find('input[name*="[option_id]"]').val();

      if (
        existingOptionId &&
        existingOptionId !== "" &&
        existingOptionId !== "0"
      ) {
        if (
          !confirm(
            mobooking_services_params.i18n.confirm_delete_option ||
              "Are you sure you want to delete this option?"
          )
        )
          return;
      }

      $row.remove();
      updateOptionSortOrders();

      if (optionsListContainer.children(".mb-option-item").length === 0) {
        // Changed from ".mobooking-service-option-row"
        optionsListContainer.html(
          '<div class="mb-no-options">' +
            (mobooking_services_params.i18n.no_options_yet ||
              'No options added. Click "Add Option".') +
            "</div>"
        );
      }
    });

    // FIXED: Update choice management functions
    function updateChoicesFromContainer($optionRow) {
      const $choicesList = $optionRow.find(".mb-choices-list"); // Changed from ".mobooking-choices-list"
      const $textarea = $optionRow.find('textarea[name*="[option_values]"]');
      const parentOptionType = $optionRow
        .find('input[type="radio"]:checked, select')
        .val();
      let choicesData = [];

      $choicesList.find(".mb-choice-item").each(function () {
        // Changed from ".mobooking-choice-item"
        const $item = $(this);
        let choiceDataItem = {
          label: $item.find('input[name="choice_label[]"]').val(), // Updated selector
          value: $item.find('input[name="choice_value[]"]').val(), // Updated selector
          price_adjust:
            parseFloat($item.find('input[name="choice_price[]"]').val()) || 0, // Updated selector
        };
        choicesData.push(choiceDataItem);
      });

      try {
        $textarea.val(JSON.stringify(choicesData));
      } catch (e) {
        console.error("Error stringifying choices: ", e);
        $textarea.val("[]");
      }
    }

    function renderChoices($optionRow) {
      const $choicesList = $optionRow.find(".mb-choices-list"); // Changed from ".mobooking-choices-list"
      const $textarea = $optionRow.find('textarea[name*="[option_values]"]');
      const parentOptionType = $optionRow
        .find('input[type="radio"]:checked, select')
        .val();

      $choicesList.empty();
      let choicesData = [];

      try {
        const jsonData = $textarea.val();
        if (jsonData) choicesData = JSON.parse(jsonData);
      } catch (e) {
        console.error("Error parsing choices JSON: ", e, $textarea.val());
      }

      if (!Array.isArray(choicesData)) choicesData = [];

      choicesData.forEach(function (choice) {
        if (!choiceTemplateHTML) {
          console.error("Choice template HTML is missing for renderChoices");
          return;
        }

        let $newItem = $(choiceTemplateHTML);
        $newItem.find('input[name="choice_label[]"]').val(choice.label || "");
        $newItem.find('input[name="choice_value[]"]').val(choice.value || "");
        $newItem
          .find('input[name="choice_price[]"]')
          .val(choice.price_adjust || "");

        $choicesList.append($newItem);
      });
    }

    // FIXED: Update sort orders function
    function updateOptionSortOrders() {
      optionsListContainer.children(".mb-option-item").each(function (index) {
        // Changed from ".mobooking-service-option-row"
        $(this)
          .find('input[name*="[sort_order]"]')
          .val(index + 1);
      });
    }

    // Add choice button handler
    optionsListContainer.on("click", ".add-choice-btn", function () {
      const $optionRow = $(this).closest(".mb-option-item");
      const $choicesList = $optionRow.find(".mb-choices-list");

      if (!choiceTemplateHTML) {
        console.error("Choice template HTML is missing for add choice");
        return;
      }

      let $newItem = $(choiceTemplateHTML);
      $newItem.find('input[type="text"], input[type="number"]').val("");
      $choicesList.append($newItem);
    });

    // Remove choice button handler
    optionsListContainer.on("click", ".mb-choice-remove-btn", function () {
      $(this).closest(".mb-choice-item").remove();
    });

    serviceForm.on("submit", function (e) {
      e.preventDefault();
      feedbackDiv.empty().removeClass("success error").hide();
      let formData = $(this).serializeArray();
      let dataToSend = {};
      formData.forEach((item) => (dataToSend[item.name] = item.value));
      dataToSend.action = "mobooking_save_service";
      dataToSend.nonce = mobooking_services_params.nonce;

      let service_options_array = [];
      $("#mobooking-service-options-list .mobooking-service-option-row").each(
        function (idx) {
          const $row = $(this);
          // Determine if names are options[idx][field] or options[][field]
          let namePrefix = "options[" + idx + "]"; // For PHP rendered
          if (!$row.find('input[name^="options[' + idx + ']["]').length) {
            namePrefix = "options[]"; // For JS template rendered (or ensure template matches PHP idx)
          }
          // To simplify, assume PHP part generates indexed names, and JS template uses non-indexed `options[][]`
          // The loop processes whatever is in DOM. Key is that PHP needs indexed for existing.
          // For submission, we can re-index all client-side to be safe or rely on PHP's auto-indexing of `options[][]`

          const option = {
            option_id: $row.find('input[name$="[option_id]"]').val() || "",
            name: $row.find('input[name$="[name]"]').val(),
            description: $row.find('textarea[name$="[description]"]').val(),
            type: $row
              .find(
                'input[type="radio"][name^="options["][name$="[type]"]:checked, select[name^="options["][name$="[type]"], .mobooking-option-type:checked'
              )
              .val(),
            is_required: $row.find('input[name$="[is_required]"]').val(), // This should correspond to the hidden input if is_required_cb is used
            price_impact_type: $row
              .find('input[type="radio"][name$="[price_impact_type]"]:checked')
              .val(),
            price_impact_value:
              $row.find('input[name$="[price_impact_value]"]').val() || null,
            option_values: $row.find('textarea[name$="[option_values]"]').val(),
            sort_order:
              $row.find('input[name$="[sort_order]"]').val() || idx + 1,
          };

          if (option.name && option.name.trim() !== "") {
            if (
              (option.type === "select" || option.type === "radio") &&
              option.option_values &&
              option.option_values.trim() !== ""
            ) {
              try {
                JSON.parse(option.option_values.trim());
              } catch (jsonError) {
                feedbackDiv
                  .text(
                    (mobooking_services_params.i18n.invalid_json_for_option ||
                      "Invalid JSON: ") + sanitizeHTML(option.name)
                  )
                  .addClass("error")
                  .show();
                throw new Error(
                  "Invalid JSON for option: " +
                    option.name +
                    " - " +
                    jsonError.message
                );
              }
            }
            service_options_array.push(option);
          }
        }
      );
      dataToSend.service_options = JSON.stringify(service_options_array);

      const submitButton = $(this).find('[type="submit"]');
      const originalButtonText = submitButton.text();
      submitButton
        .prop("disabled", true)
        .text(mobooking_services_params.i18n.saving || "Saving...");

      $.ajax({
        url: mobooking_services_params.ajax_url,
        type: "POST",
        data: dataToSend,
        dataType: "json",
        success: function (response) {
          if (response.success) {
            feedbackDiv
              .text(response.data.message || "Service saved.")
              .addClass("success")
              .show();
            if (response.data.service_id && !$("#mobooking-service-id").val()) {
              $("#mobooking-service-id").val(response.data.service_id);
            }
            setTimeout(
              () => (window.location.href = servicesListPageUrl),
              1500
            );
          } else {
            feedbackDiv
              .text(response.data.message || "Error saving.")
              .addClass("error")
              .show();
          }
        },
        error: function () {
          feedbackDiv.text("AJAX error.").addClass("error").show();
        },
        complete: function () {
          submitButton.prop("disabled", false).text(originalButtonText);
        },
      });
    });

    $("#mobooking-cancel-service-edit-btn").on("click", function () {
      window.location.href = servicesListPageUrl;
    });
  }

  // --- Logic for Service List Page (page-services.php) ---
  if ($("#mobooking-services-list-container").length) {
    const servicesListContainer = $("#mobooking-services-list-container");
    const paginationContainer = $("#mobooking-services-pagination-container");
    const mainServiceItemTemplate = $(
      "#mobooking-service-item-template"
    ).html();
    if (mainServiceItemTemplate) $("#mobooking-service-item-template").remove();

    // Remove category_filter from currentServiceFilters initialization
    let currentServiceFilters = {
      paged: 1,
      per_page: 20,
      status_filter: "",
      search_query: "",
      orderby: "name",
      order: "ASC",
    };

    function fetchAndRenderServices(page = 1, filters = {}) {
      currentServiceFilters.paged = page;
      // Ensure category_filter is not part of filters spread here
      const { category_filter, ...otherFilters } = filters;
      currentServiceFilters = { ...currentServiceFilters, ...otherFilters };
      servicesListContainer.html(
        "<p>" +
          (mobooking_services_params.i18n.loading_services || "Loading...") +
          "</p>"
      );
      paginationContainer.empty();

      // Ensure category_filter is not sent in data
      const dataToSend = {
        action: "mobooking_get_services",
        nonce: mobooking_services_params.nonce,
      };
      for (const key in currentServiceFilters) {
        if (key !== "category_filter") {
          // Explicitly exclude
          dataToSend[key] = currentServiceFilters[key];
        }
      }

      $.ajax({
        url: mobooking_services_params.ajax_url,
        type: "POST",
        data: dataToSend,
        dataType: "json",
        success: function (response) {
          servicesListContainer.empty();
          if (
            response.success &&
            response.data.services &&
            response.data.services.length
          ) {
            if (!mainServiceItemTemplate) {
              servicesListContainer.html("<p>Error: UI template missing.</p>");
              return;
            }
            response.data.services.forEach(function (service) {
              let srv = { ...service };
              if (srv.price) {
                const price = parseFloat(srv.price);
                const decimals =
                  mobooking_services_params.currency_decimals || 2;
                const dec_point =
                  mobooking_services_params.currency_decimal_sep || ".";
                const thousands_sep =
                  mobooking_services_params.currency_thousand_sep || ",";
                let n = !isFinite(+price) ? 0 : +price;
                let s = "";
                const toFixedFix = function (n_fix, prec_fix) {
                  const k = Math.pow(10, prec_fix);
                  return "" + Math.round(n_fix * k) / k;
                };
                s = (
                  decimals ? toFixedFix(n, decimals) : "" + Math.round(n)
                ).split(".");
                if (s[0].length > 3) {
                  s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, thousands_sep);
                }
                if ((s[1] || "").length < decimals) {
                  s[1] = s[1] || "";
                  s[1] += new Array(decimals - s[1].length + 1).join("0");
                }
                const formatted_number = s.join(dec_point);
                if (mobooking_services_params.currency_position === "before") {
                  srv.formatted_price =
                    mobooking_services_params.currency_symbol +
                    formatted_number;
                } else {
                  srv.formatted_price =
                    formatted_number +
                    mobooking_services_params.currency_symbol;
                }
              } else {
                srv.formatted_price = "N/A"; // Or some other placeholder
              }
              srv.display_status =
                srv.status.charAt(0).toUpperCase() + srv.status.slice(1);
              let itemHtml = mainServiceItemTemplate;
              for (const k in srv)
                itemHtml = itemHtml.replace(
                  new RegExp("<%=\\s*" + k + "\\s*%>", "g"),
                  sanitizeHTML(String(srv[k]))
                );
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
                  "No services.") +
                "</p>"
            );
          } else {
            servicesListContainer.html(
              "<p>" + (response.data.message || "Error loading.") + "</p>"
            );
          }
        },
        error: function () {
          servicesListContainer.html("<p>AJAX error.</p>");
        },
      });
    }

    function renderPagination(totalCount, perPage, currentPage) {
      const totalPages = Math.ceil(totalCount / perPage);
      if (totalPages <= 1) {
        paginationContainer.empty();
        return;
      }
      let html = "<ul class='page-numbers'>";
      if (currentPage > 1)
        html += `<li><a href="#" class="page-numbers" data-page="${
          currentPage - 1
        }">&laquo; Prev</a></li>`;
      for (let i = 1; i <= totalPages; i++) {
        html +=
          i === currentPage
            ? `<li><span class="page-numbers current">${i}</span></li>`
            : `<li><a href="#" class="page-numbers" data-page="${i}">${i}</a></li>`;
      }
      if (currentPage < totalPages)
        html += `<li><a href="#" class="page-numbers" data-page="${
          currentPage + 1
        }">Next &raquo;</a></li>`;
      html += "</ul>";
      paginationContainer.html(html);
    }

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
              'Delete "%s"?'
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
              showGlobalFeedbackList(
                response.data.message ||
                  (response.success ? "Deleted." : "Error."),
                response.success ? "success" : "error"
              );
              if (response.success)
                fetchAndRenderServices(
                  currentServiceFilters.paged,
                  currentServiceFilters
                );
            },
            error: function () {
              showGlobalFeedbackList("AJAX error deleting.", "error");
            },
          });
        }
      }
    );

    function showGlobalFeedbackList(message, type = "info") {
      $(".mobooking-global-feedback").remove();
      const styles = `padding:10px; margin:10px 0; border-radius:4px; background-color:${
        type === "success" ? "#d4edda" : "#f8d7da"
      }; border-color:${type === "success" ? "#c3e6cb" : "#f5c6cb"}; color:${
        type === "success" ? "#155724" : "#721c24"
      };`;
      const fbHtml = `<div class="mobooking-global-feedback ${type}" style="${styles}">${sanitizeHTML(
        message
      )}</div>`;
      const $h1 = $("h1").first();
      if ($h1.length) $h1.after(fbHtml);
      else servicesListContainer.before(fbHtml);
      setTimeout(
        () =>
          $(".mobooking-global-feedback").fadeOut(500, function () {
            $(this).remove();
          }),
        5000
      );
    }

    paginationContainer.on("click", "a.page-numbers", function (e) {
      e.preventDefault();
      const page =
        $(this).data("page") ||
        $(this).attr("href").split("paged=")[1]?.split("&")[0];
      if (page) fetchAndRenderServices(parseInt(page), currentServiceFilters);
    });

    if (servicesListContainer.length && mainServiceItemTemplate) {
      fetchAndRenderServices(1, currentServiceFilters);
    }
  }
});
