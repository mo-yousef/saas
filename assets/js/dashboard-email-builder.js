jQuery(document).ready(function($) {
    'use strict';

    const selector = $('#email-template-selector');
    const variablesList = $('#email-variables-list');
    const previewIframe = $('#email-preview-iframe');

    const emailTemplates = mobooking_email_builder_params.templates || {};
    const emailBodies = mobooking_email_builder_params.email_bodies || {};
    const bizSettings = mobooking_email_builder_params.biz_settings || {};
    let baseEmailTemplate = '';
    let emailState = {};

    // Fetch the base email template
    $.get(mobooking_email_builder_params.base_template_url, function(data) {
        baseEmailTemplate = data;
        loadTemplate(selector.val());
    });

    selector.on('change', function() {
        loadTemplate($(this).val());
    });

    function loadTemplate(templateKey) {
        const template = emailTemplates[templateKey];
        if (!template) return;

        // Show/hide editor fields
        $('.email-template-editor').hide();
        const editorWrapper = $('#' + templateKey + '-editor');
        editorWrapper.show();

        const bodyKey = template.body_key;
        emailState = emailBodies[templateKey] || {};

        renderEditor(bodyKey, emailState);
        updatePreview();
    }

    function renderEditor(bodyKey, state) {
        const editorFields = $(`#${bodyKey}-editor-fields`);
        editorFields.html('');

        if (state.greeting !== undefined) {
            editorFields.append(`<div class="form-group"><label>Greeting</label><input type="text" class="regular-text email-body-field" data-key="greeting" value="${state.greeting}"></div>`);
        }
        if (state.main_content !== undefined) {
            editorFields.append(`<div class="form-group"><label>Main Content</label><textarea class="large-text email-body-field" data-key="main_content" rows="5">${state.main_content}</textarea></div>`);
        }
        if (state.summary_fields !== undefined) {
            let fieldsHtml = '<div class="form-group"><label>Summary Fields</label><ul class="sortable-list">';
            state.summary_fields.forEach(field => {
                fieldsHtml += `<li class="sortable-item"><input type="text" class="regular-text" value="${field.label}"><span>${field.variable}</span></li>`;
            });
            fieldsHtml += '</ul></div>';
            editorFields.append(fieldsHtml);
            const sortableList = editorFields.find('.sortable-list')[0];
            if (sortableList) {
                new Sortable(sortableList, {
                    animation: 150,
                    onUpdate: function () {
                        const newOrder = [];
                        $(sortableList).find('.sortable-item').each(function() {
                            const label = $(this).find('input').val();
                            const variable = $(this).find('span').text();
                            newOrder.push({ label, variable });
                        });
                        emailState.summary_fields = newOrder;
                        updatePreview();
                    }
                });
            }
        }
        if (state.button_text !== undefined) {
            editorFields.append(`<div class="form-group"><label>Button Text</label><input type="text" class="regular-text email-body-field" data-key="button_text" value="${state.button_text}"></div>`);
        }
    }

    $(document).on('keyup change', '.email-body-field', function() {
        const key = $(this).data('key');
        emailState[key] = $(this).val();
        updatePreview();
    });

    variablesList.on('click', 'li', function() {
        const variableText = $(this).text();
        navigator.clipboard.writeText(variableText).then(() => {
            const originalText = $(this).text();
            $(this).text('Copied!');
            setTimeout(() => {
                $(this).text(originalText);
            }, 1000);
        });
    });

    $(document).on('mobooking:save-email-builder', function() {
        const activeTemplateKey = selector.val();
        if (activeTemplateKey) {
            const bodyKey = emailTemplates[activeTemplateKey].body_key;
            $(`#${bodyKey}`).val(JSON.stringify(emailState));
        }
    });

    function updatePreview() {
        if (!baseEmailTemplate) return;

        let bodyHtml = '';
        if (emailState.greeting) bodyHtml += `<h2>${emailState.greeting}</h2>`;
        if (emailState.main_content) bodyHtml += `<p>${emailState.main_content.replace(/\\n/g, '<br>')}</p>`;
        if (emailState.summary_fields) {
            bodyHtml += '<table class="table">';
            emailState.summary_fields.forEach(field => {
                bodyHtml += `<tr><td><strong>${field.label}</strong></td><td>${field.variable}</td></tr>`;
            });
            bodyHtml += '</table>';
        }
        if (emailState.button_text) {
            bodyHtml += `<p style="text-align: center;"><a href="#" class="button">${emailState.button_text}</a></p>`;
        }

        const replacements = {
            '{{SUBJECT}}': 'Email Preview',
            '{{BODY_CONTENT}}': bodyHtml,
            '{{LOGO_HTML}}': bizSettings.biz_logo_url ? `<img src="${bizSettings.biz_logo_url}" alt="${bizSettings.biz_name}" style="max-width: 150px; height: auto;">` : `<h1 style="font-size: 24px; margin: 0; color: #333;">${bizSettings.biz_name}</h1>`,
            '{{SITE_NAME}}': bizSettings.biz_name || 'Your Company',
            '{{SITE_URL}}': mobooking_email_builder_params.site_url || '#',
            '{{BIZ_NAME}}': bizSettings.biz_name || 'Your Company',
            '{{BIZ_ADDRESS}}': bizSettings.biz_address || '',
            '{{BIZ_PHONE}}': bizSettings.biz_phone || '',
            '{{BIZ_EMAIL}}': bizSettings.biz_email || '',
            '{{THEME_COLOR}}': bizSettings.bf_theme_color || '#1abc9c',
            '{{THEME_COLOR_LIGHT}}': 'rgba(26, 188, 156, 0.1)',
        };

        let previewHtml = baseEmailTemplate;
        for (const [key, value] of Object.entries(replacements)) {
            previewHtml = previewHtml.replace(new RegExp(key, 'g'), value);
        }

        previewIframe.attr('srcdoc', previewHtml);
    }

    // Load initial template
    loadTemplate(selector.val());
});
