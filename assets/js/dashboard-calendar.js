document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('booking-calendar');
    const sidebarEl = document.getElementById('calendar-sidebar');
    const sidebarTitleEl = document.getElementById('sidebar-title');
    const sidebarContentEl = document.getElementById('sidebar-content');
    const sidebarCloseBtn = document.getElementById('sidebar-close-btn');

    if (!calendarEl || !sidebarEl) {
        console.error('Calendar or sidebar element not found.');
        return;
    }

    // --- Sidebar Helper Functions ---
    function showSidebar(title, content) {
        sidebarTitleEl.innerHTML = title;
        sidebarContentEl.innerHTML = content;
        sidebarEl.classList.add('is-visible');
    }

    function hideSidebar() {
        sidebarEl.classList.remove('is-visible');
    }

    // --- Event Handlers ---
    sidebarCloseBtn.addEventListener('click', hideSidebar);


    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            const params = new URLSearchParams();
            params.append('action', 'nordbooking_get_all_bookings_for_calendar');
            params.append('nonce', nordbooking_calendar_params.nonce);
            params.append('start', fetchInfo.startStr);
            params.append('end', fetchInfo.endStr);

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
                    successCallback(data.data);
                } else {
                    console.error('Invalid event data received from server:', data);
                    failureCallback(new Error('Invalid event data format.'));
                }
            })
            .catch(error => {
                console.error('Error fetching events:', error);
                failureCallback(error);
                alert(nordbooking_calendar_params.i18n.error_loading_events);
            });
        },
        loading: function(isLoading) {
            if (isLoading) {
                calendarEl.classList.add('is-loading');
            } else {
                calendarEl.classList.remove('is-loading');
            }
        },
        dateClick: function(info) {
            const clickedDate = info.date;
            const eventsOnDay = calendar.getEvents().filter(event => {
                const eventStart = new Date(event.start);
                // Compare dates without time
                return eventStart.toDateString() === clickedDate.toDateString();
            });

            let contentHtml = '';
            if (eventsOnDay.length > 0) {
                contentHtml = eventsOnDay.map(event => {
                    const booking = event.extendedProps;
                    const startTime = new Date(event.start).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    return `
                        <div class="booking-item">
                            <div class="booking-time">${startTime}</div>
                            <div class="booking-title">${booking.customer_name} - ${booking.service_name}</div>
                        </div>
                    `;
                }).join('');
            } else {
                contentHtml = '<p class="no-bookings-message">No bookings for this day.</p>';
            }

            const title = `Bookings for ${clickedDate.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}`;
            showSidebar(title, contentHtml);
        },
        eventClick: function(info) {
            info.jsEvent.preventDefault(); // don't let the browser navigate

            const booking = info.event.extendedProps;
            const title = `Booking #${booking.booking_id}`;
            const detailsHtml = `
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
                <a href="${info.event.url}" class="btn btn-primary" style="margin-top: 1rem; display: inline-block;">View Full Details</a>
            `;

            showSidebar(title, detailsHtml);
        }
    });

    calendar.render();
});
