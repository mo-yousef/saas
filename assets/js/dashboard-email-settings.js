jQuery(document).ready(function($) {
    'use strict';

    const selector = $('#email-template-selector');
    const editorFields = $('#email-editor-fields');
    const variablesList = $('#email-variables-list');
    const previewIframe = $('#email-preview-iframe');

    const emailTemplates = mobooking_email_settings_params.templates || {};
    const bizSettings = mobooking_email_settings_params.biz_settings || {};
    let baseEmailTemplate = '';

    // Fetch the base email template
    $.get(mobooking_email_settings_params.base_template_url, function(data) {
        baseEmailTemplate = data;
        loadTemplate(selector.val());
    });

    selector.on('change', function() {
        loadTemplate($(this).val());
    });

    function loadTemplate(templateKey) {
        const template = emailTemplates[templateKey];
        if (!template) return;

        // Populate editor fields
        const subject = bizSettings[template.subject_key] || '';
        const body = bizSettings[template.body_key] || '';

        editorFields.html(`
            <div class="form-group">
                <label for="${template.subject_key}">${mobooking_email_settings_params.i18n.subject}</label>
                <input type="text" id="${template.subject_key}" name="${template.subject_key}" value="${subject}" class="regular-text email-template-field" data-key="subject">
            </div>
            <div class="form-group">
                <label for="${template.body_key}">${mobooking_email_settings_params.i18n.body}</label>
                <textarea id="${template.body_key}" name="${template.body_key}" rows="10" class="large-text email-template-field" data-key="body">${body}</textarea>
            </div>
        `);

        // Populate variables list
        variablesList.html(template.variables.map(v => `<li>${v}</li>`).join(''));

        updatePreview();
    }

    $(document).on('keyup change', '.email-template-field', function() {
        updatePreview();
    });

    function updatePreview() {
        if (!baseEmailTemplate) return;

        const subject = editorFields.find('[data-key="subject"]').val();
        const body = editorFields.find('[data-key="body"]').val();

        let previewHtml = baseEmailTemplate;

        const dummyData = {
            '{{customer_name}}': 'John Doe',
            '{{customer_email}}': 'john.doe@example.com',
            '{{booking_id}}': 'BOOK-12345',
            '{{booking_date}}': '2023-12-25',
            '{{service_name}}': 'Deluxe Cleaning',
            '{{service_duration}}': '120 minutes',
            '{{service_price}}': '$150.00',
            '{{discount}}': '$15.00',
            '{{total_price}}': '$135.00',
            '{{company_name}}': bizSettings.biz_name || 'Your Company',
            '{{company_logo}}': bizSettings.biz_logo_url || '',
            '{{company_email}}': bizSettings.biz_email || '',
            '{{company_phone}}': bizSettings.biz_phone || '',
            '{{company_address}}': bizSettings.biz_address || '',
            '{{staff_name}}': 'Jane Smith',
            '{{staff_dashboard_link}}': '#',
            '{{old_status}}': 'Pending',
            '{{new_status}}': 'Confirmed',
            '{{updater_name}}': 'Admin',
            '{{dashboard_link}}': '#',
            '{{worker_email}}': 'worker@example.com',
            '{{worker_role}}': 'Cleaner',
            '{{inviter_name}}': 'Admin',
            '{{registration_link}}': '#',
            '{{admin_booking_link}}': '#',
            '{{business_name}}': bizSettings.biz_name || 'Your Company',
            '{{booking_reference}}': 'BOOK-12345',
            '{{service_names}}': 'Deluxe Cleaning, Window Cleaning',
            '{{booking_date_time}}': '2023-12-25 10:00 AM',
            '{{service_address}}': '123 Main St, Anytown, USA',
            '{{special_instructions}}': 'Please use the back door.',
        };

        let processedBody = body.replace(/\\n/g, '<br>');
        for (const [key, value] of Object.entries(dummyData)) {
            processedBody = processedBody.replace(new RegExp(key, 'g'), value);
        }

        const replacements = {
            '{{SUBJECT}}': subject,
            '{{BODY_CONTENT}}': processedBody,
            '{{LOGO_URL}}': bizSettings.biz_logo_url || '',
            '{{SITE_NAME}}': bizSettings.biz_name || 'Your Company',
            '{{SITE_URL}}': mobooking_email_settings_params.site_url || '#',
            '{{BIZ_NAME}}': bizSettings.biz_name || 'Your Company',
            '{{BIZ_ADDRESS}}': bizSettings.biz_address || '',
            '{{BIZ_PHONE}}': bizSettings.biz_phone || '',
            '{{BIZ_EMAIL}}': bizSettings.biz_email || '',
            '{{THEME_COLOR}}': bizSettings.bf_theme_color || '#1abc9c',
            '{{THEME_COLOR_LIGHT}}': 'rgba(26, 188, 156, 0.1)',
        };

        for (const [key, value] of Object.entries(replacements)) {
            previewHtml = previewHtml.replace(new RegExp(key, 'g'), value);
        }

        previewIframe.attr('srcdoc', previewHtml);
    }
});
