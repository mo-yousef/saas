jQuery(document).ready(function($) {
    'use strict';

    const EmailEditor = {
        // DOM Elements
        selector: $('#email-template-selector'),
        subjectInput: $('#email-editor-subject'),
        bodyContainer: $('#email-editor-body'),
        variablesList: $('#email-variables-list'),
        previewIframe: $('#email-preview-iframe'),
        hiddenDataContainer: $('.mobooking-hidden-email-data'),

        // State
        currentTemplateKey: null,
        templatesData: {},
        baseEmailTemplateHtml: '',
        isInitialized: false,

        // Config
        componentRenderers: {
            'header': (component) => `<div class="email-component" data-type="header">
                                        <span class="drag-handle"></span>
                                        <input type="text" class="component-input" value="${component.text || ''}" placeholder="Header Text">
                                        <button class="delete-component"></button>
                                     </div>`,
            'text': (component) => `<div class="email-component" data-type="text">
                                        <span class="drag-handle"></span>
                                        <textarea class="component-input" placeholder="Paragraph text...">${component.text || ''}</textarea>
                                        <button class="delete-component"></button>
                                     </div>`,
            'button': (component) => `<div class="email-component" data-type="button">
                                        <span class="drag-handle"></span>
                                        <input type="text" class="component-input" value="${component.text || ''}" placeholder="Button Text">
                                        <input type="text" class="component-url-input" value="${component.url || ''}" placeholder="Button URL">
                                        <button class="delete-component"></button>
                                     </div>`,
            'spacer': (component) => `<div class="email-component" data-type="spacer">
                                        <span class="drag-handle"></span>
                                        <span>Spacer</span>
                                        <button class="delete-component"></button>
                                     </div>`,
        },

        previewRenderers: {
             'header': (component) => `<h2 style="font-family: sans-serif; color: #333;">${component.text}</h2>`,
             'text': (component) => `<p style="font-family: sans-serif; color: #555; line-height: 1.6;">${component.text.replace(/\n/g, '<br>')}</p>`,
             'button': (component) => `<table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: auto;"><tbody><tr><td style="font-family: sans-serif; font-size: 14px; vertical-align: top; background-color: #3498db; border-radius: 5px; text-align: center;"><a href="${component.url}" target="_blank" style="display: inline-block; color: #ffffff; background-color: #3498db; border: solid 1px #3498db; border-radius: 5px; box-sizing: border-box; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: bold; margin: 0; padding: 12px 25px; text-transform: capitalize; border-color: #3498db;">${component.text}</a></td></tr></tbody></table>`,
             'spacer': () => `<div style="height: 20px;"></div>`,
        },

        init: function() {
            if (!this.selector.length) return; // Don't run if not on settings page

            this.parseInitialData();
            this.bindEvents();
            this.fetchBaseTemplate();

            this.currentTemplateKey = this.selector.val();
            this.loadTemplateIntoEditor();
            this.isInitialized = true;
        },

        parseInitialData: function() {
            const self = this;
            this.hiddenDataContainer.find('textarea.hidden-body-json').each(function() {
                const textarea = $(this);
                const key = textarea.attr('id').replace('hidden-body-', '');
                const subject = self.hiddenDataContainer.find(`#hidden-subject-${key}`).val();
                let body = [];
                try {
                    // Use .val() for textareas to get correct content
                    const parsedBody = JSON.parse(textarea.val());
                    if (Array.isArray(parsedBody)) {
                        body = parsedBody;
                    }
                } catch (e) {
                    console.error(`Could not parse JSON for ${key}:`, textarea.val());
                }
                self.templatesData[key] = { subject, body };
            });
        },

        fetchBaseTemplate: function() {
            $.get(mobooking_email_settings_params.base_template_url, (data) => {
                this.baseEmailTemplateHtml = data;
                this.updatePreview();
            }).fail(() => {
                console.error("Failed to fetch base email template.");
            });
        },

        bindEvents: function() {
            // Template selection change
            this.selector.on('change', () => {
                this.currentTemplateKey = this.selector.val();
                this.loadTemplateIntoEditor();
            });

            // Subject input change
            this.subjectInput.on('input', () => {
                this.saveSubject();
                this.updatePreview();
            });

            // Body component changes (delegated)
            this.bodyContainer.on('input', '.component-input, .component-url-input', () => {
                this.saveBodyState();
                this.updatePreview();
            });

            this.bodyContainer.on('click', '.delete-component', (e) => {
                $(e.target).closest('.email-component').remove();
                this.saveBodyState();
                this.updatePreview();
            });

            // Init SortableJS
            new Sortable(this.bodyContainer[0], {
                animation: 150,
                handle: '.drag-handle',
                onEnd: () => {
                    this.saveBodyState();
                    this.updatePreview();
                }
            });
        },

        loadTemplateIntoEditor: function() {
            if (!this.currentTemplateKey) return;

            const data = this.templatesData[this.currentTemplateKey];
            if (!data) return;

            // Load subject
            this.subjectInput.val(data.subject);

            // Load body
            this.bodyContainer.empty();
            if (data.body && data.body.length > 0) {
                data.body.forEach(component => {
                    if (this.componentRenderers[component.type]) {
                        const componentHtml = this.componentRenderers[component.type](component);
                        this.bodyContainer.append(componentHtml);
                    }
                });
            }

            // Load variables
            this.loadVariablesList();

            // Update preview
            if (this.isInitialized) {
                this.updatePreview();
            }
        },

        loadVariablesList: function() {
            const templateInfo = mobooking_email_settings_params.templates[this.currentTemplateKey];
            if (templateInfo && templateInfo.variables) {
                const variablesHtml = templateInfo.variables.map(v => `<li>${v}</li>`).join('');
                this.variablesList.html(variablesHtml);
            }
        },

        saveSubject: function() {
            if (!this.currentTemplateKey) return;
            const newSubject = this.subjectInput.val();
            this.templatesData[this.currentTemplateKey].subject = newSubject;
            $(`#hidden-subject-${this.currentTemplateKey}`).val(newSubject);
        },

        saveBodyState: function() {
            if (!this.currentTemplateKey) return;
            const newBody = [];
            this.bodyContainer.find('.email-component').each(function() {
                const componentEl = $(this);
                const type = componentEl.data('type');
                const componentData = { type };

                if (type === 'header' || type === 'text') {
                    componentData.text = componentEl.find('.component-input').val();
                } else if (type === 'button') {
                    componentData.text = componentEl.find('.component-input').val();
                    componentData.url = componentEl.find('.component-url-input').val();
                }
                newBody.push(componentData);
            });

            this.templatesData[this.currentTemplateKey].body = newBody;
            const jsonString = JSON.stringify(newBody, null, 2);
            $(`#hidden-body-${this.currentTemplateKey}`).val(jsonString);
        },

        renderBodyForPreview: function() {
            if (!this.currentTemplateKey) return '';
            const bodyData = this.templatesData[this.currentTemplateKey].body;
            return bodyData.map(component => {
                return this.previewRenderers[component.type] ? this.previewRenderers[component.type](component) : '';
            }).join('');
        },

        updatePreview: function() {
            if (!this.baseEmailTemplateHtml || !this.currentTemplateKey) return;

            const data = this.templatesData[this.currentTemplateKey];
            const bizSettings = mobooking_email_settings_params.biz_settings || {};

            let previewHtml = this.baseEmailTemplateHtml;
            const bodyHtml = this.renderBodyForPreview();

            // Replace main content
            previewHtml = previewHtml.replace(/{{SUBJECT}}/g, data.subject);
            previewHtml = previewHtml.replace(/{{BODY_CONTENT}}/g, bodyHtml);

            // Replace branding variables
            previewHtml = previewHtml.replace(/{{LOGO_URL}}/g, bizSettings.biz_logo_url || '');
            previewHtml = previewHtml.replace(/{{THEME_COLOR}}/g, bizSettings.bf_theme_color || '#1abc9c');

            // Replace business info
            previewHtml = previewHtml.replace(/{{SITE_NAME}}/g, bizSettings.biz_name || 'Your Company');
            previewHtml = previewHtml.replace(/{{SITE_URL}}/g, mobooking_email_settings_params.site_url || '#');
            previewHtml = previewHtml.replace(/{{BIZ_NAME}}/g, bizSettings.biz_name || 'Your Company');
            previewHtml = previewHtml.replace(/{{BIZ_ADDRESS}}/g, bizSettings.biz_address || '');
            previewHtml = previewHtml.replace(/{{BIZ_PHONE}}/g, bizSettings.biz_phone || '');
            previewHtml = previewHtml.replace(/{{BIZ_EMAIL}}/g, bizSettings.biz_email || '');

            // Replace all other dummy variables
            const dummyData = mobooking_email_settings_params.dummy_data || {};
             for (const [key, value] of Object.entries(dummyData)) {
                // Ensure we create a global regex to replace all occurrences
                const regex = new RegExp(key.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'), 'g');
                previewHtml = previewHtml.replace(regex, value);
            }

            this.previewIframe.attr('srcdoc', previewHtml);
        }
    };

    EmailEditor.init();
});
