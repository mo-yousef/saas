(function($) {
    'use strict';

    $(function() {
        var $form = $('#mobooking-booking-form');
        if (!$form.length) {
            return;
        }

        var currentStep = 1;
        var totalSteps = 6;

        function updateStep(step) {
            currentStep = step;
            $('.booking-step').removeClass('active');
            $('.booking-step.step-' + step).addClass('active');

            var progress = (step - 1) / (totalSteps - 1) * 100;
            $('.progress-fill').css('width', progress + '%');

            $('.progress-steps .step').removeClass('active');
            $('.progress-steps .step:nth-child(' + step + ')').addClass('active');
        }

        $('.next-step').on('click', function() {
            if (currentStep < totalSteps) {
                updateStep(currentStep + 1);
            }
        });

        $('.prev-step').on('click', function() {
            if (currentStep > 1) {
                updateStep(currentStep - 1);
            }
        });

        // Step 1: ZIP Code Validation
        $('#customer_zip_code').on('input', function() {
            var zip = $(this).val();
            var $nextBtn = $('.step-1 .next-step');
            if (zip.length >= 5) {
                $nextBtn.prop('disabled', false).text(mobookingBooking.strings.continue);
            } else {
                $nextBtn.prop('disabled', true).text(mobookingBooking.strings.zipRequired);
            }
        });

        // Step 2: Service Selection
        $('input[name="selected_services[]"]').on('change', function() {
            var selectedServices = $('input[name="selected_services[]"]:checked').length;
            var $nextBtn = $('.step-2 .next-step');
            if (selectedServices > 0) {
                $nextBtn.prop('disabled', false).text(mobookingBooking.strings.continue);
            } else {
                $nextBtn.prop('disabled', true).text(mobookingBooking.strings.selectService);
            }
        });

        // Step 3: Service Options
        // Options are loaded dynamically, so event handlers are added when options are rendered.

        // Step 4: Customer Information
        // Validation is handled by the browser's `required` attribute.

        // Step 5: Review & Confirm
        $form.on('submit', function(e) {
            e.preventDefault();
            // Final validation before submission
            // ...
            updateStep(6);
        });
    });
})(jQuery);
