jQuery(document).ready(function ($) {
  "use strict";

  const areasListContainer = $("#mobooking-areas-list-container");
  const areaForm = $("#mobooking-area-form"); // Updated form ID
  const areaFormTitle = $("#mobooking-area-form-title");
  const areaIdField = $("#mobooking-area-id");
  const areaCountryCodeField = $("#mobooking-area-country-code");
  const areaValueField = $("#mobooking-area-value");
  const saveAreaBtn = $("#mobooking-save-area-btn");
  const cancelEditBtn = $("#mobooking-cancel-edit-area-btn");
  const feedbackDiv = $("#mobooking-area-form-feedback").hide();
  const paginationContainer = $("#mobooking-areas-pagination-container");
  const itemTemplate = $("#mobooking-area-item-template").html();

  // New selectors for area selection UI
  const countrySelector = $("#mobooking-country-selector");
  const citySelector = $("#mobooking-city-selector");
  const areaZipSelectorContainer = $("#mobooking-area-zip-selector-container");
  const addSelectedAreasBtn = $("#mobooking-add-selected-areas-btn");
  const selectionFeedbackDiv = $("#mobooking-selection-feedback").hide();

  let currentFilters = { paged: 1, limit: 20 }; // Default limit, adjust as needed

  // Ensure mobooking_areas_params and i18n are initialized to prevent errors if not localized
  window.mobooking_areas_params = window.mobooking_areas_params || {};
  window.mobooking_areas_params.i18n = window.mobooking_areas_params.i18n || {};
  const i18n = window.mobooking_areas_params.i18n;


  // Basic XSS protection for display
  function sanitizeHTML(str) {
    if (typeof str !== "string") return "";
    var temp = document.createElement("div");
    temp.textContent = str;
    return temp.innerHTML;
  }

  function renderTemplate(templateId, data) {
    // Template rendering is now simpler as it's only for one item type
    if (!itemTemplate) return "";
    let currentItemHtml = itemTemplate;
    for (const key in data) {
      const value =
        data[key] === null || typeof data[key] === "undefined" ? "" : data[key];
      currentItemHtml = currentItemHtml.replace(
        new RegExp("<%=\\s*" + key + "\\s*%>", "g"),
        sanitizeHTML(String(value))
      );
    }
    return currentItemHtml;
  }

  function fetchAndRenderAreas(page = 1) {
    currentFilters.paged = page;
    areasListContainer.html(
      "<p>" + (mobooking_areas_params.i18n.loading || "Loading...") + "</p>"
    );
    paginationContainer.empty();

    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_areas",
        nonce: mobooking_areas_params.nonce, // This should be localized from mobooking_dashboard_nonce
        ...currentFilters,
      },
      success: function (response) {
        areasListContainer.empty();
        if (
          response.success &&
          response.data.areas &&
          response.data.areas.length
        ) {
          response.data.areas.forEach(function (area) {
            areasListContainer.append(
              renderTemplate("#mobooking-area-item-template", area)
            );
          });
          renderPagination(
            response.data.total_count,
            response.data.per_page,
            response.data.current_page
          );
        } else if (response.success) {
          areasListContainer.html(
            "<p>" +
              (mobooking_areas_params.i18n.no_areas || "No areas found.") +
              "</p>"
          );
        } else {
          areasListContainer.html(
            "<p>" +
              sanitizeHTML(
                response.data.message ||
                  mobooking_areas_params.i18n.error_loading
              ) +
              "</p>"
          );
        }
      },
      error: function () {
        areasListContainer.html(
          "<p>" +
            (mobooking_areas_params.i18n.error_loading ||
              "Error loading areas.") +
            "</p>"
        );
      },
    });
  }

  function renderPagination(totalItems, itemsPerPage, currentPage) {
    paginationContainer.empty();
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    if (totalPages <= 1) return;
    let paginationHtml = '<ul class="pagination-links">';
    for (let i = 1; i <= totalPages; i++) {
      paginationHtml += `<li style="display:inline; margin-right:5px;"><a href="#" data-page="${i}" class="page-numbers ${
        i == currentPage ? "current" : ""
      }">${i}</a></li>`;
    }
    paginationHtml += "</ul>";
    paginationContainer.html(paginationHtml);
  }

  function resetForm() {
    areaForm[0].reset();
    areaIdField.val("");
    areaFormTitle.text(
      mobooking_areas_params.i18n.add_title || "Add New Service Area"
    );
    saveAreaBtn.text(mobooking_areas_params.i18n.add_button || "Add Area");
    cancelEditBtn.hide();
    feedbackDiv.empty().removeClass("success error").hide();
  }

  areaForm.on("submit", function (e) {
    e.preventDefault();
    feedbackDiv.empty().removeClass("success error").hide();

    const areaId = areaIdField.val();
    const countryCode = areaCountryCodeField.val().trim();
    const areaValue = areaValueField.val().trim();

    if (!countryCode || !areaValue) {
      feedbackDiv
        .text(
          mobooking_areas_params.i18n.fields_required ||
            "Country code and ZIP/Area value are required."
        )
        .addClass("error")
        .show();
      return;
    }

    const originalButtonText = saveAreaBtn.text();
    saveAreaBtn
      .prop("disabled", true)
      .text(mobooking_areas_params.i18n.saving || "Saving...");

    let ajaxData = {
      nonce: mobooking_areas_params.nonce,
      country_code: countryCode,
      area_value: areaValue,
    };

    if (areaId) {
      ajaxData.action = "mobooking_update_area";
      ajaxData.area_id = areaId;
    } else {
      ajaxData.action = "mobooking_add_area";
    }

    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: ajaxData,
      success: function (response) {
        if (response.success) {
          feedbackDiv
            .text(response.data.message)
            .removeClass("error")
            .addClass("success")
            .show();
          resetForm();
          fetchAndRenderAreas(areaId ? currentFilters.paged : 1); // Refresh current page on edit, or go to page 1 on add
        } else {
          feedbackDiv
            .text(
              response.data.message || mobooking_areas_params.i18n.error_general
            )
            .removeClass("success")
            .addClass("error")
            .show();
        }
      },
      error: function () {
        feedbackDiv
          .text(
            mobooking_areas_params.i18n.error_general ||
              "An AJAX error occurred."
          )
          .removeClass("success")
          .addClass("error")
          .show();
      },
      complete: function () {
        saveAreaBtn.prop("disabled", false).text(originalButtonText);
        setTimeout(function () {
          feedbackDiv.fadeOut().empty();
        }, 4000);
      },
    });
  });

  areasListContainer.on("click", ".mobooking-edit-area-btn", function () {
    const $itemRow = $(this).closest(".mobooking-area-item");
    const areaIdToEdit = $itemRow.data("area-id");
    // Assuming country_code and area_value are available from the displayed item or need fetching.
    // For simplicity, let's extract from text if possible, though data attributes would be better.
    const itemText = $itemRow.find("span").text(); // "CC - ZIP"
    const parts = itemText.split(" - ");
    const countryCode = parts[0].trim();
    const areaValue = parts[1].trim();

    areaIdField.val(areaIdToEdit);
    areaCountryCodeField.val(countryCode);
    areaValueField.val(areaValue);

    areaFormTitle.text(
      mobooking_areas_params.i18n.edit_title || "Edit Service Area"
    );
    saveAreaBtn.text(mobooking_areas_params.i18n.save_button || "Save Changes");
    cancelEditBtn.show();
    feedbackDiv.empty().hide();
    $("html, body").animate(
      { scrollTop: areaFormWrapper.offset().top - 50 },
      500
    ); // Scroll to form
  });

  cancelEditBtn.on("click", function () {
    resetForm();
  });

  areasListContainer.on("click", ".mobooking-delete-area-btn", function () {
    const areaIdToDelete = $(this).data("id");
    if (
      confirm(
        mobooking_areas_params.i18n.confirm_delete ||
          "Are you sure you want to delete this area?"
      )
    ) {
      $.ajax({
        url: mobooking_areas_params.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_delete_area",
          nonce: mobooking_areas_params.nonce,
          area_id: areaIdToDelete,
        },
        success: function (response) {
          if (response.success) {
            fetchAndRenderAreas(currentFilters.paged);
            // Show a more prominent success message if needed
            alert(
              response.data.message ||
                mobooking_areas_params.i18n.deleted_successfully
            );
          } else {
            alert(
              sanitizeHTML(
                response.data.message ||
                  mobooking_areas_params.i18n.error_deleting
              )
            );
          }
        },
        error: function () {
          alert(
            mobooking_areas_params.i18n.error_deleting ||
              "AJAX error deleting area."
          );
        },
      });
    }
  });

  paginationContainer.on("click", "a.page-numbers", function (e) {
    e.preventDefault();
    const page =
      $(this).data("page") ||
      $(this).attr("href").split("paged=")[1]?.split("&")[0];
    if (page) {
      fetchAndRenderAreas(parseInt(page));
    }
  });

  // Initial load is now handled by PHP.
  // Ensure mobooking_areas_params is localized with nonce, ajax_url, and i18n strings.
  // The check for mobooking_areas_params is done earlier now.

  // --- New Area Selection Logic ---

  function populateCountries() {
    countrySelector.prop("disabled", true);
    selectionFeedbackDiv.hide().empty();

    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_countries",
        nonce: mobooking_areas_params.nonce,
      },
      success: function (response) {
        if (response.success && response.data.countries) {
          countrySelector.empty().append($("<option>", { value: "", text: i18n.select_country || "-- Select a Country --" }));
          response.data.countries.forEach(function (country) {
            countrySelector.append($("<option>", { value: country.code, text: country.name + " (" + country.code + ")" }));
          });
        } else {
          selectionFeedbackDiv.text(i18n.error_loading_countries || "Error loading countries.").addClass("error").show();
          console.error("Error loading countries:", response.data ? response.data.message : "Unknown error");
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        selectionFeedbackDiv.text(i18n.error_loading_countries_ajax || "AJAX error loading countries.").addClass("error").show();
        console.error("AJAX error loading countries:", textStatus, errorThrown);
      },
      complete: function () {
        countrySelector.prop("disabled", false);
      }
    });
  }

  countrySelector.on("change", function () {
    const selectedCountry = $(this).val();
    citySelector.empty().append($("<option>", { value: "", text: i18n.select_city || "-- Select a City --" })).prop("disabled", true);
    areaZipSelectorContainer.html(`<small>${i18n.select_city_first || "Select a city to see areas."}</small>`);
    addSelectedAreasBtn.prop("disabled", true);
    selectionFeedbackDiv.hide().empty();

    if (!selectedCountry) return;

    citySelector.prop("disabled", true); // Disable while loading
    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_cities_for_country",
        nonce: mobooking_areas_params.nonce,
        country_code: selectedCountry,
      },
      success: function (response) {
        if (response.success && response.data.cities) {
          if (response.data.cities.length > 0) {
            response.data.cities.forEach(function (city) {
              citySelector.append($("<option>", { value: city.name, text: city.name }));
            });
            citySelector.prop("disabled", false);
          } else {
             areaZipSelectorContainer.html(`<small>${i18n.no_cities_found || "No cities found for this country."}</small>`);
          }
        } else {
          selectionFeedbackDiv.text(i18n.error_loading_cities || "Error loading cities.").addClass("error").show();
           console.error("Error loading cities:", response.data ? response.data.message : "Unknown error");
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
         selectionFeedbackDiv.text(i18n.error_loading_cities_ajax || "AJAX error loading cities.").addClass("error").show();
         console.error("AJAX error loading cities:", textStatus, errorThrown);
      },
      complete: function() {
        // Re-enable even on error, unless it was intentionally disabled due to no cities
        if (citySelector.find('option').length > 1) { // Has more than the default "-- Select a City --"
            citySelector.prop("disabled", false);
        }
      }
    });
  });

  citySelector.on("change", function () {
    const selectedCountry = countrySelector.val();
    const selectedCity = $(this).val();
    areaZipSelectorContainer.html(`<small>${i18n.loading_areas || "Loading areas..."}</small>`);
    addSelectedAreasBtn.prop("disabled", true);
    selectionFeedbackDiv.hide().empty();

    if (!selectedCountry || !selectedCity) {
        areaZipSelectorContainer.html(`<small>${i18n.select_country_city_first || "Select a country and city first."}</small>`);
        return;
    }

    areaZipSelectorContainer.empty(); // Clear previous content

    $.ajax({
      url: mobooking_areas_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_areas_for_city",
        nonce: mobooking_areas_params.nonce,
        country_code: selectedCountry,
        city_name: selectedCity,
      },
      success: function (response) {
        if (response.success && response.data.areas) {
          if (response.data.areas.length > 0) {
            response.data.areas.forEach(function (area) {
              const checkboxId = `mobooking-area-zip-${sanitizeHTML(area.zip.replace(/\s+/g, ''))}-${sanitizeHTML(selectedCountry)}`;
              const label = $("<label>").attr("for", checkboxId).css({ display: 'block', marginBottom: '5px' });
              const checkbox = $("<input type='checkbox'>")
                .attr("id", checkboxId)
                .attr("name", "selected_areas")
                .val(area.zip)
                .data("country-code", selectedCountry) // Store country code with the checkbox
                .data("area-name", area.name);

              label.append(checkbox).append(` ${sanitizeHTML(area.name)} (${sanitizeHTML(area.zip)})`);
              areaZipSelectorContainer.append(label);
            });
            addSelectedAreasBtn.prop("disabled", false);
          } else {
            areaZipSelectorContainer.html(`<small>${i18n.no_areas_found || "No areas found for this city."}</small>`);
          }
        } else {
          selectionFeedbackDiv.text(i18n.error_loading_areas || "Error loading areas.").addClass("error").show();
          console.error("Error loading areas:", response.data ? response.data.message : "Unknown error");
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        selectionFeedbackDiv.text(i18n.error_loading_areas_ajax || "AJAX error loading areas.").addClass("error").show();
        console.error("AJAX error loading areas:", textStatus, errorThrown);
        areaZipSelectorContainer.html(`<small>${i18n.error_loading_areas_ajax || "AJAX error loading areas."}</small>`);
      }
    });
  });

  addSelectedAreasBtn.on("click", function () {
    const selectedCheckboxes = areaZipSelectorContainer.find("input[type='checkbox']:checked");
    const countryCode = countrySelector.val();
    selectionFeedbackDiv.hide().empty().removeClass("success error");

    if (!countryCode) {
        selectionFeedbackDiv.text(i18n.select_country_first || "Please select a country first.").addClass("error").show();
        return;
    }

    if (selectedCheckboxes.length === 0) {
      selectionFeedbackDiv.text(i18n.no_area_selected || "Please select at least one area/ZIP to add.").addClass("error").show();
      return;
    }

    let promises = [];
    let results = { success: [], error: [] };
    const originalButtonText = $(this).text();
    $(this).prop("disabled", true).text(i18n.adding_areas || "Adding...");

    selectedCheckboxes.each(function () {
      const zip = $(this).val();
      // const areaName = $(this).data("area-name"); // Available if needed for feedback

      promises.push(
        $.ajax({
          url: mobooking_areas_params.ajax_url,
          type: "POST",
          data: {
            action: "mobooking_add_area",
            nonce: mobooking_areas_params.nonce,
            country_code: countryCode, // Use country from the main selector
            area_value: zip,
            area_type: "zip_code" // Ensure this matches what backend expects
          },
        }).then(
            response => { // Success
                if (response.success) {
                    results.success.push(`ZIP ${zip}: ${response.data.message || (i18n.added_successfully || 'Added successfully')}`);
                } else {
                    results.error.push(`ZIP ${zip}: ${response.data.message || (i18n.error_adding_zip || 'Error adding ZIP')}`);
                }
            },
            () => { // Error
                results.error.push(`ZIP ${zip}: ` + (i18n.error_adding_zip_ajax || 'AJAX error adding ZIP.'));
            }
        )
      );
    });

    $.when.apply($, promises).always(function () {
        let feedbackMessages = [];
        if(results.success.length > 0) {
            feedbackMessages.push(`${i18n.successfully_added || 'Successfully added'}:<br>` + results.success.join('<br>'));
        }
        if(results.error.length > 0) {
            feedbackMessages.push(`${i18n.errors_encountered || 'Errors encountered'}:<br>` + results.error.join('<br>'));
        }

        selectionFeedbackDiv.html(feedbackMessages.join('<br><br>'))
            .addClass(results.error.length > 0 ? 'error' : 'success')
            .show();

        addSelectedAreasBtn.prop("disabled", false).text(originalButtonText);
        fetchAndRenderAreas(1); // Refresh the main list of areas to show new additions

        // Uncheck checkboxes after processing
        selectedCheckboxes.prop('checked', false);

        setTimeout(function () {
          selectionFeedbackDiv.fadeOut().empty();
        }, 7000); // Longer timeout for multiple messages
    });
  });


  // --- End New Area Selection Logic ---


  // Initial load of countries for the new selection UI
  if (countrySelector.length) { // Check if the selector exists on the page
    populateCountries();
  }
});
