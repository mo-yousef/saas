<?php
function mobooking_get_cronofy_element_token_and_availability_query() {
    $availability = new MoBooking\Classes\Availability();
    $schedule = $availability->get_recurring_schedule(get_current_user_id());

    $query_periods = [];
    foreach ($schedule as $day) {
        if ($day['is_enabled']) {
            foreach ($day['slots'] as $slot) {
                $query_periods[] = [
                    'start' => '2025-07-26T' . $slot['start_time'] . ':00Z',
                    'end' => '2025-07-26T' . $slot['end_time'] . ':00Z',
                ];
            }
        }
    }

    $availability_query = [
        'participants' => [
            [
                'required' => 'all',
                'members' => [
                    [ 'sub' => 'acc_5ba21743f408617d1269ea1e' ],
                    [ 'sub' => 'acc_64b17d868090ea21640c914c' ]
                ]
            ]
        ],
        'required_duration' => [ 'minutes' => 30 ],
        'query_periods' => $query_periods
    ];

    $response = wp_remote_post('https://api.cronofy.com/v1/element_tokens', [
        'headers' => [
            'Authorization' => 'Bearer YOUR_CRONOFY_API_KEY',
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'version' => '1',
            'origin' => get_site_url(),
            'permissions' => [
                'availability'
            ]
        ])
    ]);

    if (is_wp_error($response)) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    return [
        'element_token' => $data['element_token'],
        'availability_query' => $availability_query
    ];
}
?>
