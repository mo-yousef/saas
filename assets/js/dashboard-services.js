jQuery(document).ready(function ($) {
  ("use strict");

  if (typeof mobooking_services_params === "undefined") {
    console.error("MoBooking: mobooking_services_params is not defined.");
    window.mobooking_services_params = {
      ajax_url: "/wp-admin/admin-ajax.php",
      nonce: "",
      i18n: {},
    };
  }

  // --- Logic for Service List Page (page-services.php) ---
  if ($("#services-list-container").length) {
    const servicesListContainer = $("#services-list-container");

    servicesListContainer.on(
      "click",
      ".mobooking-delete-service-btn",
      function () {
        const serviceId = $(this).data("id");
        const serviceName = $(this)
          .closest(".service-card")
          .find(".mobooking-card-title")
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
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data.message || 'Error deleting service.');
                }
            },
            error: function () {
                alert('AJAX error deleting service.');
            },
          });
        }
      }
    );
  }
});
