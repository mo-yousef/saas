document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('schedule-x-calendar');
    if (!calendarEl) {
        return;
    }

    const params = new URLSearchParams();
    params.append('action', 'nordbooking_get_all_bookings_for_calendar');
    params.append('nonce', nordbooking_calendar_params.nonce);

    fetch(nordbooking_calendar_params.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && Array.isArray(data.data)) {
            const events = data.data.map(booking => {
                return {
                    id: booking.id,
                    title: booking.title,
                    start: booking.start,
                    end: booking.end,
                    extendedProps: booking.extendedProps
                };
            });

            const calendar = window.SXCalendar.createCalendar({
                views: [
                    window.SXCalendar.createViewMonthGrid(),
                    window.SXCalendar.createViewWeek(),
                    window.SXCalendar.createViewDay()
                ],
                events: events,
                plugins: [
                    window.SXDragAndDrop.createDragAndDropPlugin()
                ],
                eventClick: function(info) {
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
                            buttons: [
                                {
                                    label: 'Close',
                                    class: 'secondary',
                                    onClick: (dialog) => {
                                        dialog.close();
                                    }
                                },
                                {
                                    label: 'View Details',
                                    class: 'primary',
                                    onClick: () => {
                                        window.location.href = booking.url;
                                    }
                                }
                            ]
                        }).show();
                    } else {
                        alert(detailsHtml.replace(/<[^>]+>/g, '\n'));
                        window.open(booking.url, "_blank");
                    }
                }
            });

            calendar.render(calendarEl);
        } else {
            console.error('Invalid event data received from server:', data);
            alert(nordbooking_calendar_params.i18n.error_loading_events);
        }
    })
    .catch(error => {
        console.error('Error fetching events:', error);
        alert(nordbooking_calendar_params.i18n.error_loading_events);
    });
});
