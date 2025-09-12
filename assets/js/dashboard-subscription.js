jQuery(document).ready(function($) {
    $('#nordbooking-subscribe-button').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        button.text('Creating session...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'nordbooking_create_business_owner_checkout_session',
            _wpnonce: nordbooking_dashboard_vars.nonce
        }).done(function(response) {
            if (response.success) {
                const stripe = Stripe(response.data.stripe_pk);
                stripe.redirectToCheckout({ sessionId: response.data.checkout_session_id });
            } else {
                alert(response.data.message);
                button.text('Subscribe Now').prop('disabled', false);
            }
        }).fail(function() {
            alert('An error occurred.');
            button.text('Subscribe Now').prop('disabled', false);
        });
    });

    $('#nordbooking-manage-subscription-button').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        button.text('Redirecting...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'nordbooking_create_customer_portal_session',
            _wpnonce: nordbooking_dashboard_vars.nonce
        }).done(function(response) {
            if (response.success) {
                window.location.href = response.data.url;
            } else {
                alert(response.data.message);
                button.text('Manage Subscription').prop('disabled', false);
            }
        }).fail(function() {
            alert('An error occurred.');
            button.text('Manage Subscription').prop('disabled', false);
        });
    });
});
