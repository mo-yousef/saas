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

  let currentFilters = { paged: 1, limit: 20 }; // Default limit, adjust as needed

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
  if (typeof mobooking_areas_params === "undefined") {
    console.error(
      "mobooking_areas_params is not defined. Please ensure it is localized correctly."
    );
    // Fallback for i18n to prevent crashes if params are missing
    window.mobooking_areas_params = { i18n: {} };
  }
});
