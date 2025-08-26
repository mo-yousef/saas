jQuery(document).ready(function($) {
    'use strict';

    const selector = $('#email-template-selector');
    const palette = document.getElementById('component-palette');
    const inspector = document.getElementById('inspector-content');
    const previewIframe = $('#email-preview-iframe');
    const variablesList = $('#email-variables-list');

    const emailTemplates = mobooking_email_builder_params.templates || {};
    let emailState = [];
    let selectedComponent = null;
    let activeCanvas = null;

    function initBuilder(templateKey) {
        const template = emailTemplates[templateKey];
        if (!template) return;

        // Init SortableJS on the canvas
        const canvasEl = document.getElementById(`${template.body_key}-builder`);
        if (canvasEl) {
            activeCanvas = new Sortable(canvasEl, {
                group: 'shared',
                animation: 150,
                onAdd: function (evt) {
                    const itemEl = evt.item;
                    const type = itemEl.dataset.type;
                    const newComponent = createComponent(type);
                    const newIndex = evt.newIndex;

                    emailState.splice(newIndex, 0, newComponent);
                    renderCanvas();
                },
                onUpdate: function (evt) {
                    const item = emailState.splice(evt.oldIndex, 1)[0];
                    emailState.splice(evt.newIndex, 0, item);
                    renderCanvas();
                },
            });
        }
    }

    new Sortable(palette, {
        group: {
            name: 'shared',
            pull: 'clone',
            put: false
        },
        sort: false,
        animation: 150
    });

    selector.on('change', function() {
        loadTemplate($(this).val());
    });

    function loadTemplate(templateKey) {
        const template = emailTemplates[templateKey];
        if (!template) return;

        // Show/hide editor fields
        $('.email-template-editor').hide();
        $('#' + templateKey + '-editor').show();

        // Populate variables list
        variablesList.html(template.variables.map(v => `<li>${v}</li>`).join(''));

        // Initialize emailState from hidden input
        const bodyKey = template.body_key;
        const bodyJson = $(`#${bodyKey}`).val();
        try {
            emailState = JSON.parse(bodyJson);
        } catch (e) {
            emailState = [];
        }

        initBuilder(templateKey);
        renderCanvas();
    }

    // Load initial template
    loadTemplate(selector.val());

    $(document).on('mobooking:save-email-builder', function() {
        const activeTemplateKey = selector.val();
        if (activeTemplateKey) {
            const bodyKey = emailTemplates[activeTemplateKey].body_key;
            $(`#${bodyKey}`).val(JSON.stringify(emailState));
        }
    });

    function createComponent(type) {
        const id = 'comp-' + Date.now();
        switch (type) {
            case 'header':
                return { id, type, content: 'Header Text' };
            case 'paragraph':
                return { id, type, content: 'This is a paragraph.' };
            case 'list':
                return { id, type, items: ['Item 1', 'Item 2'] };
            case 'table':
                return { id, type, rows: [['Row 1, Col 1', 'Row 1, Col 2'], ['Row 2, Col 1', 'Row 2, Col 2']] };
            case 'button':
                return { id, type, content: 'Button Text', link: '#' };
            case 'link':
                return { id, type, content: 'Link Text', link: '#' };
            case 'divider':
                return { id, type };
            default:
                return null;
        }
    }

    function renderCanvas() {
        const activeTemplateKey = selector.val();
        const canvasEl = document.getElementById(`${emailTemplates[activeTemplateKey].body_key}-builder`);
        if (!canvasEl) return;

        canvasEl.innerHTML = '';
        emailState.forEach(comp => {
            const compEl = document.createElement('div');
            compEl.className = 'canvas-component';
            compEl.dataset.id = comp.id;
            compEl.innerHTML = `
                <div class="component-label">${comp.type}</div>
                <div class="component-controls">
                    <button class="edit-btn">Edit</button>
                    <button class="delete-btn">Delete</button>
                </div>
            `;
            canvasEl.appendChild(compEl);
        });
        updatePreview();
    }

    $(document).on('click', '.edit-btn', function() {
        const compId = $(this).closest('.canvas-component').data('id');
        selectedComponent = emailState.find(c => c.id === compId);
        renderInspector();
    });

    $(document).on('click', '.delete-btn', function() {
        const compId = $(this).closest('.canvas-component').data('id');
        emailState = emailState.filter(c => c.id !== compId);
        renderCanvas();
    });

    function renderInspector() {
        inspector.innerHTML = '';
        if (!selectedComponent) return;

        let fields = '';
        switch (selectedComponent.type) {
            case 'header':
            case 'paragraph':
                fields = `<div class="form-group"><label>Content</label><textarea class="inspector-field" data-prop="content">${selectedComponent.content}</textarea></div>`;
                break;
            case 'button':
            case 'link':
                fields = `
                    <div class="form-group"><label>Text</label><input type="text" class="inspector-field" data-prop="content" value="${selectedComponent.content}"></div>
                    <div class="form-group"><label>Link URL</label><input type="text" class="inspector-field" data-prop="link" value="${selectedComponent.link}"></div>
                `;
                break;
            case 'list':
                fields = `<div class="form-group"><label>Items (one per line)</label><textarea class="inspector-field" data-prop="items" rows="5">${selectedComponent.items.join('\\n')}</textarea></div>`;
                break;
            case 'table':
                fields = `<div class="form-group"><label>Rows (CSV format)</label><textarea class="inspector-field" data-prop="rows" rows="5">${selectedComponent.rows.map(row => row.join(',')).join('\\n')}</textarea></div>`;
                break;
        }
        inspector.innerHTML = fields;
    }

    $(inspector).on('keyup change', '.inspector-field', function() {
        if (!selectedComponent) return;
        const prop = $(this).data('prop');
        let value = $(this).val();

        if (prop === 'items') {
            value = value.split('\\n');
        } else if (prop === 'rows') {
            value = value.split('\\n').map(row => row.split(','));
        }

        selectedComponent[prop] = value;
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

    function updatePreview() {
        if (!mobooking_email_builder_params.base_template_url) return;

        $.get(mobooking_email_builder_params.base_template_url, function(baseEmailTemplate) {
            let bodyHtml = '';
            emailState.forEach(comp => {
                switch (comp.type) {
                    case 'header':
                        bodyHtml += `<h2>${comp.content}</h2>`;
                        break;
                    case 'paragraph':
                        bodyHtml += `<p>${comp.content.replace(/\\n/g, '<br>')}</p>`;
                        break;
                    case 'list':
                        bodyHtml += '<ul>';
                        comp.items.forEach(item => {
                            bodyHtml += `<li>${item}</li>`;
                        });
                        bodyHtml += '</ul>';
                        break;
                    case 'table':
                        bodyHtml += '<table class="table">';
                        comp.rows.forEach(row => {
                            bodyHtml += '<tr>';
                            row.forEach(cell => {
                                bodyHtml += `<td>${cell}</td>`;
                            });
                            bodyHtml += '</tr>';
                        });
                        bodyHtml += '</table>';
                        break;
                    case 'button':
                        bodyHtml += `<p style="text-align: center;"><a href="${comp.link}" class="button">${comp.content}</a></p>`;
                        break;
                    case 'link':
                        bodyHtml += `<p><a href="${comp.link}">${comp.content}</a></p>`;
                        break;
                    case 'divider':
                        bodyHtml += '<hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">';
                        break;
                }
            });

            const bizSettings = mobooking_email_builder_params.biz_settings;
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
        });
    }
});
