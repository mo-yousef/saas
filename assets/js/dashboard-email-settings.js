jQuery(document).ready(function($) {
    'use strict';

    if (typeof mobooking_email_settings_params === 'undefined') {
        console.error('Email settings parameters are not available.');
        return;
    }

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
        i18n: mobooking_email_settings_params.i18n || {},

        componentRenderers: {}, // Will be initialized in init()
        previewRenderers: {
             'header': (component) => `<h2>${component.text}</h2>`,
             'text': (component) => `<p style="white-space: pre-wrap;">${component.text}</p>`,
             'button': (component) => `<table border="0" cellpadding="0" cellspacing="0"><tr><td style="background-color: #3498db; border-radius: 5px; text-align: center;"><a href="${component.url}" target="_blank" style="display: inline-block; color: #ffffff; background-color: #3498db; border: solid 1px #3498db; border-radius: 5px; box-sizing: border-box; cursor: pointer; text-decoration: none; padding: 12px 25px;">${component.text}</a></td></tr></table>`,
             'spacer': () => `<div style="height: 20px;"></div>`,
        },

        init: function() {
            if (!this.selector.length) return;

            this.initializeRenderers();
            this.parseInitialData();
            this.bindEvents();
            this.fetchBaseTemplate();

            this.currentTemplateKey = this.selector.val();
            this.loadTemplateIntoEditor();
            this.isInitialized = true;
        },

        initializeRenderers: function() {
            this.componentRenderers = {
                'header': (component) => `<div class="email-component" data-type="header">
                                            <span class="drag-handle"></span>
                                            <input type="text" class="component-input" value="${component.text || ''}" placeholder="${this.i18n.header_placeholder}">
                                            <button class="delete-component" title="${this.i18n.delete_component_title}"></button>
                                         </div>`,
                'text': (component) => `<div class="email-component" data-type="text">
                                            <span class="drag-handle"></span>
                                            <textarea class="component-input" placeholder="${this.i18n.text_placeholder}">${component.text || ''}</textarea>
                                            <button class="delete-component" title="${this.i18n.delete_component_title}"></button>
                                         </div>`,
                'button': (component) => `<div class="email-component" data-type="button">
                                            <span class="drag-handle"></span>
                                            <input type="text" class="component-input" value="${component.text || ''}" placeholder="${this.i18n.button_placeholder}">
                                            <input type="text" class="component-url-input" value="${component.url || ''}" placeholder="${this.i18n.button_url_placeholder}">
                                            <button class="delete-component" title="${this.i18n.delete_component_title}"></button>
                                         </div>`,
                'spacer': (component) => `<div class="email-component" data-type="spacer">
                                            <span class="drag-handle"></span>
                                            <span>${this.i18n.spacer_text}</span>
                                            <button class="delete-component" title="${this.i18n.delete_component_title}"></button>
                                         </div>`,
            }
        },

        parseInitialData: function() {
            const self = this;
            this.hiddenDataContainer.find('textarea.hidden-body-json').each(function() {
                const textarea = $(this);
                const key = textarea.attr('id').replace('hidden-body-', '');
                const subject = self.hiddenDataContainer.find(`#hidden-subject-${key}`).val();
                let body = [];
                try {
                    // Strip slashes that might be added by WordPress's data sanitization
                    const cleanJson = textarea.val().replace(/\\/g, '');
                    const parsedBody = JSON.parse(cleanJson);
                    if (Array.isArray(parsedBody) && parsedBody.length > 0) {
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
            }).fail(() => console.error("Failed to fetch base email template."));
        },

        bindEvents: function() {
            this.selector.on('change', () => {
                this.currentTemplateKey = this.selector.val();
                this.loadTemplateIntoEditor();
            });

            this.subjectInput.on('input', this.debounce(() => {
                this.saveSubject();
                this.updatePreview();
            }, 300));

            this.bodyContainer.on('input', '.component-input, .component-url-input', this.debounce((e) => {
                this.saveBodyState();
                this.updatePreview();
            }, 300));

            this.bodyContainer.on('click', '.delete-component', (e) => {
                $(e.target).closest('.email-component').remove();
                this.saveBodyState();
                this.updatePreview();
            });

            this.variablesList.on('click', 'li', function() {
                navigator.clipboard.writeText($(this).text());
                const originalText = $(this).text();
                $(this).text('Copied!');
                setTimeout(() => $(this).text(originalText), 1000);
            });

            if (typeof Sortable !== 'undefined') {
                new Sortable(this.bodyContainer[0], {
                    animation: 150,
                    handle: '.drag-handle',
                    onEnd: () => {
                        this.saveBodyState();
                        this.updatePreview();
                    }
                });
            }
        },

        loadTemplateIntoEditor: function() {
            if (!this.currentTemplateKey) return;
            const data = this.templatesData[this.currentTemplateKey];
            if (!data) return;

            this.subjectInput.val(data.subject);
            this.bodyContainer.empty();
            if (data.body && data.body.length > 0) {
                data.body.forEach(component => {
                    if (this.componentRenderers[component.type]) {
                        this.bodyContainer.append(this.componentRenderers[component.type](component));
                    }
                });
            }

            this.loadVariablesList();
            if (this.isInitialized) this.updatePreview();
        },

        loadVariablesList: function() {
            const templateInfo = mobooking_email_settings_params.templates[this.currentTemplateKey];
            if (templateInfo && templateInfo.variables) {
                this.variablesList.html(templateInfo.variables.map(v => `<li>${v}</li>`).join(''));
            } else {
                this.variablesList.empty();
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
                const el = $(this);
                const type = el.data('type');
                const componentData = { type };
                if (type === 'header' || type === 'text') {
                    componentData.text = el.find('.component-input').val();
                } else if (type === 'button') {
                    componentData.text = el.find('.component-input').val();
                    componentData.url = el.find('.component-url-input').val();
                }
                newBody.push(componentData);
            });
            this.templatesData[this.currentTemplateKey].body = newBody;
            $(`#hidden-body-${this.currentTemplateKey}`).val(JSON.stringify(newBody));
        },

        renderBodyForPreview: function() {
            if (!this.currentTemplateKey) return '';
            const bodyData = this.templatesData[this.currentTemplateKey].body;
            return bodyData.map(c => this.previewRenderers[c.type] ? this.previewRenderers[c.type](c) : '').join('');
        },

        updatePreview: function() {
            if (!this.baseEmailTemplateHtml || !this.currentTemplateKey) return;
            let previewHtml = this.baseEmailTemplateHtml;
            const bodyHtml = this.renderBodyForPreview();
            previewHtml = previewHtml.replace(/{{BODY_CONTENT}}/g, bodyHtml);

            const allVars = { ...mobooking_email_settings_params.biz_settings, ...mobooking_email_settings_params.dummy_data };
            allVars['{{SUBJECT}}'] = this.templatesData[this.currentTemplateKey].subject;

            for (const [key, value] of Object.entries(allVars)) {
                const regex = new RegExp(key.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'), 'g');
                previewHtml = previewHtml.replace(regex, value || '');
            }

            this.previewIframe.attr('srcdoc', previewHtml);
        },

        debounce: function(func, wait) {
            let timeout;
            return function(...args) {
                const context = this;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        }
    };

    EmailEditor.init();
});
