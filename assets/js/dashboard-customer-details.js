jQuery(document).ready(function ($) {
  "use strict";

  // --- Edit Customer Modal Logic ---
  $("#NORDBOOKING-edit-customer-btn").on("click", function (e) {
    e.preventDefault();

    // This is a bit verbose, but necessary to get the form fields into the dialog
    const formHtml = $("#NORDBOOKING-edit-customer-form").html();

    const editDialog = new MoBookingDialog({
      title: "Edit Customer",
      content: `<form id="nordbooking-dialog-edit-form">${formHtml}</form>`,
      buttons: [
        {
          label: "Cancel",
          class: "secondary",
          onClick: (dialog) => dialog.close(),
        },
        {
          label: "Save Changes",
          class: "primary",
          onClick: (dialog) => {
            const form = dialog.findElement("#nordbooking-dialog-edit-form");
            const formData = $(form).serializeArray();
            let data = {
              action: "nordbooking_update_customer_details",
              nonce: nordbooking_customers_params.nonce,
            };

            formData.forEach((item) => {
              data[item.name] = item.value;
            });

            const saveBtn = dialog.findElement(".btn-primary");
            saveBtn.textContent = "Saving...";
            saveBtn.disabled = true;

            $.post(nordbooking_customers_params.ajax_url, data)
              .done(function (response) {
                if (response.success) {
                  location.reload();
                } else {
                  alert(
                    "Error: " +
                      (response.data.message || "Could not update customer.")
                  );
                  saveBtn.textContent = "Save Changes";
                  saveBtn.disabled = false;
                }
              })
              .fail(function () {
                alert("An unknown error occurred.");
                saveBtn.textContent = "Save Changes";
                saveBtn.disabled = false;
              });
          },
        },
      ],
    });
    editDialog.show();
  });
});
