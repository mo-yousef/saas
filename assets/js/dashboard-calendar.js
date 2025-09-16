document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('booking-calendar');
    if (!calendarEl) {
        return;
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        events: {
            url: nordbooking_calendar_params.ajax_url,
            method: 'POST',
            extraParams: {
                action: 'nordbooking_get_all_bookings_for_calendar',
                nonce: nordbooking_calendar_params.nonce
            },
            failure: function() {
                alert(nordbooking_calendar_params.i18n.error_loading_events);
            }
        },
        loading: function(isLoading) {
            if (isLoading) {
                // You can add a loading indicator here if you want
                console.log(nordbooking_calendar_params.i18n.loading_events);
            } else {
                // and hide it here
            }
        },
        eventClick: function(info) {
            info.jsEvent.preventDefault(); // don't let the browser navigate

            const booking = info.event.extendedProps;
            let detailsHtml = `
                <p><strong>${nordbooking_calendar_params.i18n.booking_details}</strong></p>
                <ul>
                    <li><strong>Customer:</strong> ${booking.customer_name}</li>
                    <li><strong>Email:</strong> ${booking.customer_email}</li>
                    <li><strong>Phone:</strong> ${booking.customer_phone}</li>
                    <li><strong>Service:</strong> ${booking.service_name}</li>
                    <li><strong>Date:</strong> ${new Date(booking.booking_date).toLocaleDateString()}</li>
                    <li><strong>Time:</strong> ${booking.booking_time}</li>
                    <li><strong>Status:</strong> <span class="status-badge status-${booking.status}">${booking.status}</span></li>
                    <li><strong>Price:</strong> ${booking.total_price} ${booking.currency}</li>
                </ul>
            `;

            if (window.NordbookingDialog) {
                new window.NordbookingDialog({
                    title: `Booking #${booking.booking_id}`,
                    content: detailsHtml,
                    actions: [
                        {
                            label: 'View Details',
                            class: 'button-primary',
                            callback: function() {
                                window.location.href = info.event.url;
                            }
                        },
                        {
                            label: 'Close',
                            class: 'button',
                            callback: function(dialog) {
                                dialog.close();
                            }
                        }
                    ]
                }).open();
            } else {
                // Fallback if the dialog is not available
                alert(detailsHtml.replace(/<[^>]+>/g, '\n'));
                window.open(info.event.url, "_blank");
            }
        }
    });

    calendar.render();
});
