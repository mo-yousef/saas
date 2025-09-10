jQuery(document).ready(function ($) {
  "use strict";

  const listContainer = $("#NORDBOOKING-discounts-list");
  const paginationContainer = $("#NORDBOOKING-discounts-pagination-container");
  const itemTemplate = $("#NORDBOOKING-discount-item-template").html();
  const formTemplate = $("#NORDBOOKING-discount-form-template").html();
  let currentDialog = null;

  let currentFilters = { paged: 1, limit: 20 }; // Basic pagination, filters can be added later

  function sanitizeHTML(str) {
    if (typeof str !== "string") return "";
    var temp = document.createElement("div");
    temp.textContent = str;
    return temp.innerHTML;
  }

  function renderTemplate(templateHtml, data) {
    let template = templateHtml;
    for (const key in data) {
      const value =
        data[key] === null || typeof data[key] === "undefined" ? "" : data[key];
      template = template.replace(
        new RegExp("<%=\\s*" + key + "\\s*%>", "g"),
        sanitizeHTML(String(value))
      );
    }
    return template;
  }

  function loadDiscounts(page = 1) {
    currentFilters.paged = page;
    listContainer.html(
      '<tr><td colspan="7"><p>' +
        (nordbooking_discounts_params.i18n.loading || "Loading...") +
        "</p></td></tr>"
    );
    paginationContainer.empty();

    $.ajax({
      url: nordbooking_discounts_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_get_discounts",
        nonce: nordbooking_discounts_params.nonce,
        ...currentFilters,
      },
      success: function (response) {
        listContainer.empty();
        // Correctly access discounts and pagination data from the structured response
        if (
          response.success &&
          response.data &&
          response.data.discounts &&
          response.data.discounts.length
        ) {
          const discounts = response.data.discounts;
          const total_count = response.data.total_count;
          const per_page = response.data.per_page;
          const current_page = response.data.current_page;

          discounts.forEach(function (discount) {
            let displayData = { ...discount };
            displayData.type_display =
              nordbooking_discounts_params.types[discount.type] ||
              discount.type;
            displayData.value_display =
              discount.type === "percentage"
                ? parseFloat(discount.value).toFixed(2) + "%"
                : parseFloat(discount.value).toFixed(2); // Add currency symbol later
            displayData.expiry_date_display =
              discount.expiry_date ||
              nordbooking_discounts_params.i18n.never ||
              "Never";
            displayData.usage_display = `${discount.times_used} / ${
              parseInt(discount.usage_limit, 10) > 0
                ? discount.usage_limit
                : nordbooking_discounts_params.i18n.unlimited || "∞"
            }`;
            displayData.status_display =
              nordbooking_discounts_params.statuses[discount.status] ||
              discount.status;
            listContainer.append(renderTemplate(itemTemplate, displayData));
          });
          renderPagination(total_count, per_page, current_page);
        } else if (
          response.success &&
          response.data &&
          response.data.discounts &&
          response.data.discounts.length === 0
        ) {
          // Explicitly check for empty discounts array
          listContainer.html(
            '<tr><td colspan="7"><p>' +
              (nordbooking_discounts_params.i18n.no_discounts ||
                "No discounts found.") +
              "</p></td></tr>"
          );
        } else {
          // Handle cases where response.data might be missing or message is not in response.data.message
          const message =
            response.data && response.data.message
              ? sanitizeHTML(response.data.message)
              : nordbooking_discounts_params.i18n.error_loading || "Error.";
          listContainer.html(
            '<tr><td colspan="7"><p>' + message + "</p></td></tr>"
          );
        }
      },
      error: function () {
        listContainer.html(
          '<tr><td colspan="7"><p>' +
            (nordbooking_discounts_params.i18n.error_loading || "Error.") +
            "</p></td></tr>"
        );
      },
    });
  }

  function renderPagination(totalItems, itemsPerPage, currentPage) {
    paginationContainer.empty();
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    if (totalPages <= 1) return;
    let paginationHtml = '<ul class="NORDBOOKING-pagination">';
    for (let i = 1; i <= totalPages; i++) {
      paginationHtml += `<li class="${
        i == currentPage ? "active" : ""
      }"><a href="#" data-page="${i}">${i}</a></li>`;
    }
    paginationHtml += "</ul>";
    paginationContainer.html(paginationHtml);
  }

  paginationContainer.on("click", "a", function (e) {
    e.preventDefault();
    loadDiscounts(parseInt($(this).data("page"), 10));
  });

  function openDiscountDialog(discountId = null) {
    const isEdit = discountId !== null;
    const title = isEdit
      ? nordbooking_discounts_params.i18n.edit_title || "Edit Discount Code"
      : nordbooking_discounts_params.i18n.add_new_title ||
        "Add New Discount Code";

    currentDialog = new MoBookingDialog({
      title: title,
      content: formTemplate,
      buttons: [
        {
          label: nordbooking_discounts_params.i18n.cancel || "Cancel",
          class: "secondary",
          onClick: (dialog) => dialog.close(),
        },
        {
          label: nordbooking_discounts_params.i18n.save || "Save Discount",
          class: "primary",
          onClick: (dialog) => {
            const form = dialog.findElement("#NORDBOOKING-discount-form");
            submitDiscountForm(form, dialog);
          },
        },
      ],
      onOpen: (dialog) => {
        const form = dialog.findElement("#NORDBOOKING-discount-form");
        // Initialize datepicker or other elements here if needed
        if (typeof $.fn.datepicker === "function") {
          $(form).find(".NORDBOOKING-datepicker").datepicker({
            dateFormat: "yy-mm-dd",
            changeMonth: true,
            changeYear: true,
          });
        } else {
          $(form).find(".NORDBOOKING-datepicker").attr("type", "date");
        }

        if (isEdit) {
          loadDiscountDetailsIntoForm(discountId, form);
        }
      },
      onClose: () => {
        currentDialog = null;
      },
    });

    currentDialog.show();
  }

  function loadDiscountDetailsIntoForm(discountId, form) {
    $.ajax({
      url: nordbooking_discounts_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_get_discount_details",
        nonce: nordbooking_discounts_params.nonce,
        discount_id: discountId,
      },
      success: function (response) {
        if (response.success && response.data.discount) {
          const d = response.data.discount;
          $(form).find("#NORDBOOKING-discount-id").val(d.discount_id);
          $(form).find("#NORDBOOKING-discount-code").val(d.code);
          $(form)
            .find(`input[name="type"][value="${d.type}"]`)
            .prop("checked", true);
          $(form).find("#NORDBOOKING-discount-value").val(d.value);
          $(form)
            .find("#NORDBOOKING-discount-expiry")
            .val(d.expiry_date || "");
          $(form)
            .find("#NORDBOOKING-discount-limit")
            .val(d.usage_limit || "");
          $(form)
            .find("#NORDBOOKING-discount-status")
            .prop("checked", d.status === "active");
        } else {
          window.showAlert(
            response.data.message || "Error fetching details.",
            "error"
          );
          currentDialog.close();
        }
      },
      error: function () {
        window.showAlert("AJAX error fetching details.", "error");
        currentDialog.close();
      },
    });
  }

  $("#NORDBOOKING-add-new-discount-btn").on("click", function () {
    openDiscountDialog();
  });

  listContainer.on("click", ".NORDBOOKING-edit-discount-btn", function () {
    const discountId = $(this).closest("tr").data("id");
    openDiscountDialog(discountId);
  });

  // Delete Discount
  listContainer.on("click", ".NORDBOOKING-delete-discount-btn", function () {
    if (
      !confirm(
        nordbooking_discounts_params.i18n.confirm_delete || "Are you sure?"
      )
    )
      return;
    const discountId = $(this).closest("tr").data("id");
    $.ajax({
      url: nordbooking_discounts_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_delete_discount",
        nonce: nordbooking_discounts_params.nonce,
        discount_id: discountId,
      },
      success: function (response) {
        if (response.success) {
          loadDiscounts(currentFilters.paged);
        } else {
          alert(response.data.message || "Error deleting.");
        }
      },
      error: function () {
        alert("AJAX error deleting.");
      },
    });
  });

  function submitDiscountForm(form, dialog) {
    const $form = $(form);
    const discountId = $form.find("#NORDBOOKING-discount-id").val();
    // Here you can add validation if needed before submitting

    let formData = $form.serializeArray();
    let dataToSend = {
      action: "nordbooking_save_discount",
      nonce: nordbooking_discounts_params.nonce,
    };
    formData.forEach((item) => (dataToSend[item.name] = item.value));
    dataToSend.status = $form
      .find("#NORDBOOKING-discount-status")
      .is(":checked")
      ? "active"
      : "inactive";
    if (dataToSend.usage_limit === "0") dataToSend.usage_limit = "";

    $.ajax({
      url: nordbooking_discounts_params.ajax_url,
      type: "POST",
      data: dataToSend,
      success: function (response) {
        if (response.success) {
          window.showAlert(response.data.message, "success");
          dialog.close();
          loadDiscounts(discountId ? currentFilters.paged : 1);
        } else {
          window.showAlert(response.data.message || "Error saving.", "error");
        }
      },
      error: function () {
        window.showAlert("AJAX error saving.", "error");
      },
    });
  }

  // Generate Random Code (using event delegation on the body)
  $("body").on("click", "#NORDBOOKING-generate-code-btn", function () {
    if (!currentDialog) return; // Ensure this only works when a dialog is open
    const names = [
      "SUMMER",
      "WINTER",
      "SPRING",
      "AUTUMN",
      "HOLIDAY",
      "SPECIAL",
      "SALE",
    ];
    const randomName = names[Math.floor(Math.random() * names.length)];
    const randomNumber = Math.floor(Math.random() * 1000);
    const randomCode = `${randomName}${randomNumber}`;
    currentDialog.findElement("#NORDBOOKING-discount-code").value = randomCode;
  });

  // Initialize Datepicker
  if (typeof $.fn.datepicker === "function") {
    $(".NORDBOOKING-datepicker").datepicker({
      dateFormat: "yy-mm-dd",
      changeMonth: true,
      changeYear: true,
    });
  } else {
    $(".NORDBOOKING-datepicker").attr("type", "date");
  }

  // loadDiscounts(); // Initial load is now handled by PHP.
  // Ensure pagination links from PHP work with the existing loadDiscounts logic.
  // The PHP pagination uses hrefs like #?paged=X, JS should preventDefault and use the page number.
  // The current paginationContainer.on('click', 'a', ...) should handle this if WP's paginate_links outputs simple hrefs.
  // If it's a full URL, the selector might need adjustment or the link format in PHP.
  // For now, assuming current JS pagination handler is sufficient for links rendered by PHP.
});
