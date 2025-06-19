jQuery(document).ready(function ($) {
  "use strict";

  if (typeof mobooking_services_params === "undefined") {
    console.error("MoBooking: mobooking_services_params is not defined.");
    // Provide fallback to prevent crashes, though functionality will be impaired
    window.mobooking_services_params = {
      ajax_url: "/wp-admin/admin-ajax.php", // This is a common default
      nonce: "",
      i18n: {},
      currency_symbol: "$",
      site_url: "", // Should be localized
      dashboard_slug: "dashboard" // Default slug
    };
  }

  const siteUrl = mobooking_services_params.site_url || '';
  const dashboardSlug = mobooking_services_params.dashboard_slug || 'dashboard';
  const servicesListPageUrl = siteUrl + (siteUrl.endsWith('/') ? '' : '/') + dashboardSlug + '/services/';

  // Common helper
  function sanitizeHTML(str) {
    if (typeof str !== "string") return "";
    var temp = document.createElement("div");
    temp.textContent = str;
    return temp.innerHTML;
  }

  // --- Logic for Service Add/Edit Page (page-service-edit.php) ---
  if ($('#mobooking-service-form').length) {
    const serviceForm = $("#mobooking-service-form");
    const feedbackDiv = $("#mobooking-service-form-feedback").hide();
    const optionsListContainer = $("#mobooking-service-options-list");
    const addServiceOptionBtn = $("#mobooking-add-service-option-btn");

    const optionTemplateHtml = $("#mobooking-service-option-template").html();
    if (optionTemplateHtml) $("#mobooking-service-option-template").remove();
    else console.error("MoBooking: Service option template not found on edit page!");

    const choiceTemplateHTML = $("#mobooking-choice-item-template").html();
    if (choiceTemplateHTML) $("#mobooking-choice-item-template").remove();
    else console.error("MoBooking: Choice item template not found on edit page!");

    let optionClientIndex = optionsListContainer.find(".mobooking-service-option-row").length; // Start indexing after PHP-rendered items

    function toggleOptionDetailFields($row) {
      const type = $row.find(".mobooking-option-type, select[name^='options['][name$='[type]']").val();
      const $valuesField = $row.find(".mobooking-option-values-field");
      if (type === "select" || type === "radio") $valuesField.slideDown();
      else $valuesField.slideUp();

      const priceType = $row.find(".mobooking-option-price-type, select[name^='options['][name$='[price_impact_type]']").val();
      const $priceValueField = $row.find(".mobooking-option-price-value-field");
      if (priceType && priceType !== "") $priceValueField.slideDown();
      else {
        $priceValueField.slideUp();
        $row.find('input[name^="options["][name$="[price_impact_value]"], input[name="options[][price_impact_value]"]').val("");
      }
    }

    function updateOptionSortOrders() {
      optionsListContainer.find(".mobooking-service-option-row").each(function (idx, el) {
        $(el).find('input[name^="options["][name$="[sort_order]"], input[name="options[][sort_order]"]').val(idx + 1);
      });
    }

    function createCustomRadioButtons(selectElement, targetContainer) {
        if (!selectElement || !targetContainer) return;
        $(targetContainer).empty();
        const options = selectElement.options;
        const currentSelectValue = selectElement.value;
        for (let i = 0; i < options.length; i++) {
            const option = options[i];
            const radioLabel = $('<span class="mobooking-custom-radio-label"></span>')
                .attr('data-value', option.value).text(option.text);
            if (option.value === currentSelectValue) radioLabel.addClass('selected');
            radioLabel.on('click', function() {
                const $this = $(this);
                selectElement.value = $this.attr('data-value');
                $this.siblings('.mobooking-custom-radio-label').removeClass('selected');
                $this.addClass('selected');
                var event = new Event('change', { bubbles: true });
                selectElement.dispatchEvent(event);
            });
            $(targetContainer).append(radioLabel);
        }
    }

    function initializeCustomRadiosForRow($row) {
        if ($row.data('custom-radios-initialized')) return;
        const $selectElement = $row.find('.mobooking-option-type, select[name^="options["][name$="[type]"]');
        const $placeholder = $row.find('.mobooking-custom-radio-group-placeholder');
        if ($selectElement.length && $placeholder.length && $row.find('.mobooking-custom-radio-group').length === 0) {
            const $radioGroupDiv = $('<div class="mobooking-custom-radio-group"></div>');
            $placeholder.replaceWith($radioGroupDiv);
            createCustomRadioButtons($selectElement.get(0), $radioGroupDiv.get(0));
            $row.data('custom-radios-initialized', true);
        } else if ($selectElement.length && $row.find('.mobooking-custom-radio-group').length > 0) {
             const currentRadioValue = $row.find('.mobooking-custom-radio-label.selected').data('value');
             if (currentRadioValue && $selectElement.val() !== currentRadioValue) {
                 $selectElement.val(currentRadioValue).trigger('change');
             }
            $row.data('custom-radios-initialized', true);
        }
    }

    function syncTextarea($optionRow) {
        const $choicesList = $optionRow.find('.mobooking-choices-list');
        const $textarea = $optionRow.find('textarea[name^="options["][name$="[option_values]"], textarea[name="options[][option_values]"]');
        let choicesData = [];
        $choicesList.find('.mobooking-choice-item').each(function() {
            const $item = $(this);
            choicesData.push({
                label: $item.find('.mobooking-choice-label').val(),
                value: $item.find('.mobooking-choice-value').val(),
                price_adjust: parseFloat($item.find('.mobooking-choice-price-adjust').val()) || 0
            });
        });
        try { $textarea.val(JSON.stringify(choicesData)); }
        catch (e) { console.error("Error stringifying choices: ", e); $textarea.val("[]"); }
    }

    function renderChoices($optionRow) {
        const $choicesList = $optionRow.find('.mobooking-choices-list');
        const $textarea = $optionRow.find('textarea[name^="options["][name$="[option_values]"], textarea[name="options[][option_values]"]');
        $choicesList.empty();
        let choicesData = [];
        try { const jsonData = $textarea.val(); if (jsonData) choicesData = JSON.parse(jsonData); }
        catch (e) { console.error("Error parsing choices JSON: ", e, $textarea.val()); }
        if (!Array.isArray(choicesData)) choicesData = [];

        choicesData.forEach(function(choice) {
            if (!choiceTemplateHTML) { console.error("Choice template HTML is missing for renderChoices"); return; }
            const $newItem = $(choiceTemplateHTML);
            $newItem.find('.mobooking-choice-label').val(choice.label || '');
            $newItem.find('.mobooking-choice-value').val(choice.value || '');
            $newItem.find('.mobooking-choice-price-adjust').val(choice.price_adjust || '');
            $choicesList.append($newItem);
        });
    }

    function initializeChoiceManagementForRow($row) {
        if ($row.data('choice-management-fully-initialized')) return;
        renderChoices($row);
        const $choicesList = $row.find('.mobooking-choices-list');
        $row.find('.mobooking-add-choice-btn').off('click.mobooking').on('click.mobooking', function() {
            const $optionRowLocal = $(this).closest('.mobooking-service-option-row');
            if (!choiceTemplateHTML) { console.error("Choice template HTML is missing for add choice"); return; }
            const $newItem = $(choiceTemplateHTML);
            $newItem.find('input').val('');
            $optionRowLocal.find('.mobooking-choices-list').append($newItem);
            syncTextarea($optionRowLocal);
        });
        $choicesList.off('click.mobooking', '.mobooking-remove-choice-btn').on('click.mobooking', '.mobooking-remove-choice-btn', function() {
            const $optionRowLocal = $(this).closest('.mobooking-service-option-row');
            $(this).closest('.mobooking-choice-item').remove();
            syncTextarea($optionRowLocal);
        });
        $choicesList.off('change.mobooking input.mobooking', '.mobooking-choice-label, .mobooking-choice-value, .mobooking-choice-price-adjust')
            .on('change.mobooking input.mobooking', '.mobooking-choice-label, .mobooking-choice-value, .mobooking-choice-price-adjust', function() {
            const $optionRowLocal = $(this).closest('.mobooking-service-option-row');
            syncTextarea($optionRowLocal);
        });
        $row.find('.mobooking-option-type, select[name^="options["][name$="[type]"]').off('change.mobookingChoices').on('change.mobookingChoices', function() {
            renderChoices($row);
        });
        if ($.fn.sortable && !$choicesList.hasClass('ui-sortable')) {
            $choicesList.sortable({
                items: '.mobooking-choice-item', handle: '.mobooking-choice-drag-handle', axis: 'y',
                placeholder: 'mobooking-choice-item-placeholder', tolerance: 'pointer', containment: 'parent',
                stop: function() { syncTextarea($(this).closest('.mobooking-service-option-row')); }
            }).disableSelection();
        } else if (!$.fn.sortable) { console.warn('jQuery UI Sortable is not loaded for choices.'); }
        $row.data('choice-management-fully-initialized', true);
    }

    // Initialize existing options (rendered by PHP)
    optionsListContainer.find('.mobooking-service-option-row').each(function() {
        const $row = $(this);
        initializeCustomRadiosForRow($row);
        initializeChoiceManagementForRow($row);
        toggleOptionDetailFields($row);
    });

    // Observe for dynamically added options
    if (optionsListContainer.length) {
        const observer = new MutationObserver(function(mutationsList) {
            for (const mutation of mutationsList) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && $(node).hasClass('mobooking-service-option-row')) {
                            const $newNode = $(node);
                            initializeCustomRadiosForRow($newNode);
                            initializeChoiceManagementForRow($newNode);
                            toggleOptionDetailFields($newNode);
                        }
                    });
                }
            }
        });
        observer.observe(optionsListContainer[0], { childList: true });

        // Initialize Sortable for Service Options
        if ($.fn.sortable) {
            optionsListContainer.sortable({
                items: '.mobooking-service-option-row',
                handle: '.mobooking-option-drag-handle',
                axis: 'y',
                placeholder: 'mobooking-service-option-row-placeholder', // CSS for this placeholder should be in dashboard-service-edit.css
                tolerance: 'pointer',
                containment: 'parent',
                stop: function(event, ui) {
                    updateOptionSortOrders();
                }
            }).disableSelection();
        } else {
            console.warn('jQuery UI Sortable is not loaded. Service option reordering will not be available.');
        }
    }

    optionsListContainer.on("change", ".mobooking-option-type, select[name^='options['][name$='[type]'], .mobooking-option-price-type, select[name^='options['][name$='[price_impact_type]']", function () {
        toggleOptionDetailFields($(this).closest(".mobooking-service-option-row"));
    });

    addServiceOptionBtn.on("click", function () {
        if ($(this).is(":disabled")) return;

       const serviceId = $("#mobooking-service-id").val();
       const serviceName = $("#mobooking-service-name").val().trim();
       const servicePrice = $("#mobooking-service-price").val().trim();
       const serviceDuration = $("#mobooking-service-duration").val().trim();
       // feedbackDiv is already defined in this scope if this is inside the `if ($('#mobooking-service-form').length)` block

       if (!serviceId) { // Only apply this check for new services (serviceId is empty for new)
           if (!serviceName || !servicePrice || !serviceDuration) {
               let missingFields = [];
               if (!serviceName) missingFields.push(mobooking_services_params.i18n.service_name_label || 'Service Name');
               if (!servicePrice) missingFields.push(mobooking_services_params.i18n.service_price_label || 'Price');
               if (!serviceDuration) missingFields.push(mobooking_services_params.i18n.service_duration_label || 'Duration');

               const message = (mobooking_services_params.i18n.fill_service_details_first || 'Please fill in the following service details before adding options: %s.').replace('%s', missingFields.join(', '));

               feedbackDiv.text(message).removeClass("success error notice").addClass("notice").show();
               // Optional: Scroll to feedback or first empty field
               // Example: $(window).scrollTop($('#mobooking-service-form-feedback').offset().top - 100);
               return; // Prevent adding the option
           } else {
               feedbackDiv.empty().removeClass("success error notice").hide(); // Clear if previously shown and details are now filled
           }
       }

        if (!optionTemplateHtml) { console.error("Cannot add option: template missing."); return; }
        optionsListContainer.find("p.mobooking-no-options-yet").remove();
        optionClientIndex++;
        const newOptionRow = $(optionTemplateHtml);
        // Ensure new rows use `options[][field]` by making sure template has this form
        newOptionRow.find('input[name="options[][option_id]"]').val("");
        newOptionRow.find('select[name="options[][type]"]').val("checkbox");
        newOptionRow.find('input[name="options[][is_required]"]').val("0");
        newOptionRow.find('input[name="options[][sort_order]"]').val(optionsListContainer.children('.mobooking-service-option-row').length + 1);
        optionsListContainer.append(newOptionRow);
        // Initializers will be called by MutationObserver. If not, uncomment direct calls:
        // initializeCustomRadiosForRow(newOptionRow);
        // initializeChoiceManagementForRow(newOptionRow);
        // toggleOptionDetailFields(newOptionRow);
        updateOptionSortOrders();
        newOptionRow.find('input[name="options[][name]"]').focus();
    });

    optionsListContainer.on("click", ".mobooking-remove-service-option-btn", function () {
        const $row = $(this).closest(".mobooking-service-option-row");
        const existingOptionId = $row.find('input[name^="options["][name$="[option_id]"], input[name="options[][option_id]"]').val();
        if (existingOptionId && existingOptionId !== "" && existingOptionId !== "0") {
            if (!confirm(mobooking_services_params.i18n.confirm_delete_option || "Are you sure you want to delete this option?")) return;
        }
        $row.remove();
        updateOptionSortOrders();
        if (optionsListContainer.children(".mobooking-service-option-row").length === 0) {
            optionsListContainer.html('<p class="mobooking-no-options-yet">' + (mobooking_services_params.i18n.no_options_yet || 'No options added. Click "Add Option".') + "</p>");
        }
    });

    optionsListContainer.on("change", 'input[name^="options["][name$="[is_required_cb]"], input[name="options[][is_required_cb]"]', function () {
        const $checkbox = $(this);
        const nameAttr = $checkbox.attr('name');
        const hiddenName = nameAttr.substring(0, nameAttr.lastIndexOf('[is_required_cb]')) + '[is_required]';
        const $hiddenInput = $checkbox.closest(".mobooking-service-option-row").find('input[name="' + hiddenName + '"]');
        $hiddenInput.val($checkbox.is(":checked") ? "1" : "0");
    });

    serviceForm.on("submit", function (e) {
        e.preventDefault();
        feedbackDiv.empty().removeClass("success error").hide();
        let formData = $(this).serializeArray();
        let dataToSend = {};
        formData.forEach(item => dataToSend[item.name] = item.value);
        dataToSend.action = "mobooking_save_service";
        dataToSend.nonce = mobooking_services_params.nonce;

        let service_options_array = [];
        $("#mobooking-service-options-list .mobooking-service-option-row").each(function (idx) {
            const $row = $(this);
            // Determine if names are options[idx][field] or options[][field]
            let namePrefix = 'options[' + idx + ']'; // For PHP rendered
            if (!$row.find('input[name^="options[' + idx + ']["]').length) {
                 namePrefix = 'options[]'; // For JS template rendered (or ensure template matches PHP idx)
            }
            // To simplify, assume PHP part generates indexed names, and JS template uses non-indexed `options[][]`
            // The loop processes whatever is in DOM. Key is that PHP needs indexed for existing.
            // For submission, we can re-index all client-side to be safe or rely on PHP's auto-indexing of `options[][]`

            const option = {
                option_id: $row.find('input[name$="[option_id]"]').val() || "",
                name: $row.find('input[name$="[name]"]').val(),
                description: $row.find('textarea[name$="[description]"]').val(),
                type: $row.find('select[name$="[type]"]').val(),
                is_required: $row.find('input[name$="[is_required]"]').val(),
                price_impact_type: $row.find('select[name$="[price_impact_type]"]').val(),
                price_impact_value: $row.find('input[name$="[price_impact_value]"]').val() || null,
                option_values: $row.find('textarea[name$="[option_values]"]').val(),
                sort_order: $row.find('input[name$="[sort_order]"]').val() || (idx + 1),
            };

            if (option.name && option.name.trim() !== "") {
                if ((option.type === "select" || option.type === "radio") && option.option_values && option.option_values.trim() !== "") {
                    try { JSON.parse(option.option_values.trim()); }
                    catch (jsonError) {
                        feedbackDiv.text((mobooking_services_params.i18n.invalid_json_for_option || "Invalid JSON: ") + sanitizeHTML(option.name)).addClass("error").show();
                        throw new Error("Invalid JSON for option: " + option.name + " - " + jsonError.message);
                    }
                }
                service_options_array.push(option);
            }
        });
        dataToSend.service_options = JSON.stringify(service_options_array);

        const submitButton = $(this).find('[type="submit"]');
        const originalButtonText = submitButton.text();
        submitButton.prop("disabled", true).text(mobooking_services_params.i18n.saving || "Saving...");

        $.ajax({
            url: mobooking_services_params.ajax_url, type: "POST", data: dataToSend, dataType: "json",
            success: function (response) {
                if (response.success) {
                    feedbackDiv.text(response.data.message || "Service saved.").addClass("success").show();
                    if (response.data.service_id && !$('#mobooking-service-id').val()) {
                        $('#mobooking-service-id').val(response.data.service_id);
                    }
                    setTimeout(() => window.location.href = servicesListPageUrl, 1500);
                } else {
                    feedbackDiv.text(response.data.message || "Error saving.").addClass("error").show();
                }
            },
            error: function () { feedbackDiv.text("AJAX error.").addClass("error").show(); },
            complete: function () { submitButton.prop("disabled", false).text(originalButtonText); }
        });
    });

    $("#mobooking-cancel-service-edit-btn").on("click", function () {
        window.location.href = servicesListPageUrl;
    });
  }

  // --- Logic for Service List Page (page-services.php) ---
  if ($('#mobooking-services-list-container').length) {
    const servicesListContainer = $("#mobooking-services-list-container");
    const paginationContainer = $("#mobooking-services-pagination-container");
    const mainServiceItemTemplate = $("#mobooking-service-item-template").html();
    if (mainServiceItemTemplate) $("#mobooking-service-item-template").remove();
    // else console.warn("MoBooking: Service item template not found on list page."); // Already handled in previous step

    let currentServiceFilters = { paged: 1, per_page: 20, status_filter: "", category_filter: "", search_query: "", orderby: "name", order: "ASC" };
    // let servicesDataCache = {}; // Not actively used on list page after modal removal

    function fetchAndRenderServices(page = 1, filters = {}) {
        currentServiceFilters.paged = page;
        currentServiceFilters = { ...currentServiceFilters, ...filters };
        servicesListContainer.html("<p>" + (mobooking_services_params.i18n.loading_services || "Loading...") + "</p>");
        paginationContainer.empty();

        $.ajax({
            url: mobooking_services_params.ajax_url, type: "POST",
            data: { action: "mobooking_get_services", nonce: mobooking_services_params.nonce, ...currentServiceFilters },
            dataType: "json",
            success: function (response) {
                servicesListContainer.empty();
                if (response.success && response.data.services && response.data.services.length) {
                    if (!mainServiceItemTemplate) {
                         servicesListContainer.html("<p>Error: UI template missing.</p>"); return;
                    }
                    response.data.services.forEach(function (service) {
                        let srv = { ...service };
                        srv.formatted_price = (mobooking_services_params.currency_symbol || '$') + parseFloat(srv.price).toFixed(2);
                        srv.display_status = srv.status.charAt(0).toUpperCase() + srv.status.slice(1);
                        let itemHtml = mainServiceItemTemplate;
                        for (const k in srv) itemHtml = itemHtml.replace(new RegExp("<%=\\s*" + k + "\\s*%>", "g"), sanitizeHTML(String(srv[k])));
                        servicesListContainer.append(itemHtml);
                    });
                    renderPagination(response.data.total_count, response.data.per_page, response.data.current_page);
                } else if (response.success) {
                    servicesListContainer.html("<p>" + (mobooking_services_params.i18n.no_services_found || "No services.") + "</p>");
                } else {
                    servicesListContainer.html("<p>" + (response.data.message || "Error loading.") + "</p>");
                }
            },
            error: function () { servicesListContainer.html("<p>AJAX error.</p>"); }
        });
    }

    function renderPagination(totalCount, perPage, currentPage) {
        const totalPages = Math.ceil(totalCount / perPage);
        if (totalPages <= 1) { paginationContainer.empty(); return; }
        let html = "<ul class='page-numbers'>";
        if (currentPage > 1) html += `<li><a href="#" class="page-numbers" data-page="${currentPage - 1}">&laquo; Prev</a></li>`;
        for (let i = 1; i <= totalPages; i++) {
            html += (i === currentPage) ? `<li><span class="page-numbers current">${i}</span></li>` : `<li><a href="#" class="page-numbers" data-page="${i}">${i}</a></li>`;
        }
        if (currentPage < totalPages) html += `<li><a href="#" class="page-numbers" data-page="${currentPage + 1}">Next &raquo;</a></li>`;
        html += "</ul>";
        paginationContainer.html(html);
    }

    servicesListContainer.on("click", ".mobooking-delete-service-btn", function () {
        const serviceId = $(this).data("id");
        const serviceName = $(this).closest(".mobooking-service-item").find("h3").text();
        if (confirm(((mobooking_services_params.i18n.confirm_delete_service || 'Delete "%s"?').replace("%s", serviceName)))) {
            $.ajax({
                url: mobooking_services_params.ajax_url, type: "POST",
                data: { action: "mobooking_delete_service", nonce: mobooking_services_params.nonce, service_id: serviceId },
                dataType: "json",
                success: function (response) {
                    showGlobalFeedbackList(response.data.message || (response.success ? "Deleted." : "Error."), response.success ? "success" : "error");
                    if (response.success) fetchAndRenderServices(currentServiceFilters.paged, currentServiceFilters);
                },
                error: function () { showGlobalFeedbackList("AJAX error deleting.", "error"); }
            });
        }
    });

    function showGlobalFeedbackList(message, type = "info") {
        $(".mobooking-global-feedback").remove();
        const styles = `padding:10px; margin:10px 0; border-radius:4px; background-color:${type === "success" ? "#d4edda" : "#f8d7da"}; border-color:${type === "success" ? "#c3e6cb" : "#f5c6cb"}; color:${type === "success" ? "#155724" : "#721c24"};`;
        const fbHtml = `<div class="mobooking-global-feedback ${type}" style="${styles}">${sanitizeHTML(message)}</div>`;
        const $h1 = $('h1').first();
        if ($h1.length) $h1.after(fbHtml); else servicesListContainer.before(fbHtml);
        setTimeout(() => $(".mobooking-global-feedback").fadeOut(500, function () { $(this).remove(); }), 5000);
    }

    paginationContainer.on("click", "a.page-numbers", function (e) {
        e.preventDefault();
        const page = $(this).data("page") || $(this).attr("href").split("paged=")[1]?.split("&")[0];
        if (page) fetchAndRenderServices(parseInt(page), currentServiceFilters);
    });

    if (servicesListContainer.length && mainServiceItemTemplate) {
        fetchAndRenderServices(1, currentServiceFilters);
    }
  }
});
