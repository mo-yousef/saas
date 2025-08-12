jQuery(function($) {
    // Service Edit functionality
    const ServiceEdit = {
        optionIndex: mobooking_service_edit_params.option_count,

        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initSwitches();
        },

        bindEvents: function() {
            const self = this;
            const $container = $('#options-container');

            // Add option button
            $(document).on('click', '#add-option-btn, .add-first-option', function() {
                self.addOption();
            });

            // Form submission
            $('#mobooking-service-form').on('submit', function(e) {
                e.preventDefault();
                self.saveService();
            });

            // Save as draft
            $('#save-draft-btn').on('click', () => this.saveService(true));

            // Delete and duplicate
            $('#delete-service-btn').on('click', () => this.deleteService());
            $('#duplicate-service-btn').on('click', () => this.duplicateService());

            // Icon and image handling
            $('#select-icon-btn').on('click', () => this.openIconSelector());
            $('#image-preview').on('click', function() {
                if (!$(this).find('img').length) {
                    $('#service-image-upload').click();
                }
            });
            $('#service-image-upload').on('change', function(e) {
                if (e.target.files[0]) {
                    self.handleImageUpload(e.target.files[0]);
                }
            });
            $(document).on('click', '.remove-image-btn', function(e) {
                e.stopPropagation();
                self.removeImage();
            });

            // --- Delegated Option Events ---

            // Toggle option
            $container.on('click', '.toggle-option', function() {
                const $optionElement = $(this).closest('.option-item');
                $optionElement.toggleClass('expanded');
                $optionElement.find('.option-content').slideToggle(200);
            });

            // Delete option
            $container.on('click', '.delete-option', function() {
                if (confirm(mobooking_service_edit_params.i18n.confirm_delete_option)) {
                    $(this).closest('.option-item').remove();
                    self.updateOptionsBadge();

                    if ($('.option-item').length === 0) {
                        self.showEmptyState();
                    }
                }
            });

            // Update option name in header
            $container.on('input', '.option-name-input', function() {
                const $input = $(this);
                const nameDisplay = $input.closest('.option-item').find('.option-name');
                nameDisplay.text($input.val() || 'New Option');
            });

            // Update option type badge and show/hide choices
            $container.on('change', '.option-type-radio', function() {
                const $radio = $(this);
                const type = $radio.val();
                const $optionItem = $radio.closest('.option-item');

                // Update badge
                const badge = $optionItem.find('.option-badges .badge-outline');
                const typeLabel = $radio.closest('.option-type-card').find('.option-type-title').text();
                if (badge.length) {
                    badge.text(typeLabel);
                }

                // Show/hide choices container and clear existing choices on type change
                const $choicesContainer = $optionItem.find('.choices-container');
                const $choicesList = $optionItem.find('.choices-list');
                const choiceTypes = ['select', 'radio', 'checkbox', 'sqm'];

                if (choiceTypes.includes(type)) {
                    $choicesContainer.slideDown(200);
                } else {
                    $choicesContainer.slideUp(200);
                }

                // Clear the list to prevent keeping choices from a different type
                $choicesList.empty();
                // Update the data attribute for the add choice button
                $choicesList.data('option-type', type);
            });

            // Add choice
            $container.on('click', '.add-choice-btn', function() {
                const $button = $(this);
                const $optionElement = $button.closest('.option-item');
                const $list = $optionElement.find('.choices-list');
                const optionIndex = $optionElement.data('option-index');
                const choiceIndex = $list.find('.choice-item').length;
                const optionType = $optionElement.find('.option-type-radio:checked').val();

                let newChoiceHtml = '';

                if (optionType === 'sqm') {
                    newChoiceHtml = `
                        <div class="choice-item flex items-center gap-2">
                            <input type="number" name="options[${optionIndex}][choices][${choiceIndex}][from_sqm]" class="form-input w-24" placeholder="From" step="0.01">
                            <span class="text-muted-foreground">-</span>
                            <input type="number" name="options[${optionIndex}][choices][${choiceIndex}][to_sqm]" class="form-input w-24" placeholder="To" step="0.01">
                            <input type="number" name="options[${optionIndex}][choices][${choiceIndex}][price_per_sqm]" class="form-input flex-1" placeholder="Price per SQM" step="0.01">
                            <button type="button" class="btn-icon remove-choice-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/></svg>
                            </button>
                        </div>
                    `;
                } else {
                    newChoiceHtml = `
                        <div class="choice-item flex items-center gap-2">
                            <input type="text" name="options[${optionIndex}][choices][${choiceIndex}][label]" class="form-input flex-1" placeholder="Choice Label">
                            <input type="number" name="options[${optionIndex}][choices][${choiceIndex}][price]" class="form-input w-24" placeholder="Price" step="0.01">
                            <button type="button" class="btn-icon remove-choice-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/></svg>
                            </button>
                        </div>
                    `;
                }

                $list.append(newChoiceHtml);
            });

            // Remove choice
            $container.on('click', '.remove-choice-btn', function() {
                $(this).closest('.choice-item').remove();
            });
        },

        initTabs: function() {
            $('.tabs-trigger').on('click', function() {
                const tabId = $(this).data('tab');

                $('.tabs-trigger').removeClass('active').attr('aria-selected', 'false');
                $(this).addClass('active').attr('aria-selected', 'true');

                $('.tabs-content').removeClass('active');
                $('#' + tabId).addClass('active');
            });
        },

        initSwitches: function() {
            $(document).on('click', '.switch', function() {
                const $switchEl = $(this);
                const $hiddenInput = $switchEl.parent().find('input[type="hidden"]');

                $switchEl.toggleClass('switch-checked');
                const isChecked = $switchEl.hasClass('switch-checked');

                if ($hiddenInput.length) {
                    $hiddenInput.val($switchEl.data('switch') === 'status' ? (isChecked ? 'active' : 'inactive') : (isChecked ? '1' : '0'));
                }

                if ($switchEl.data('switch') === 'status') {
                    const $label = $switchEl.parent().find('.text-sm');
                    if ($label.length) {
                        $label.text(isChecked ? 'Active' : 'Inactive');
                    }
                }
            });
        },

        addOption: function() {
            const $container = $('#options-container');
            $container.find('.empty-state').remove();

            const template = $('#mobooking-option-template').html();
            if (!template) {
                console.error('Option template not found!');
                return;
            }

            const optionHtml = template.replace(/__INDEX__/g, this.optionIndex);
            $container.append(optionHtml);

            const $newOption = $container.find('.option-item').last();
            if ($newOption.length) {
                $newOption.addClass('expanded');
                $newOption.find('.option-content').show();
                $newOption.find('.option-name-input').focus();
            }

            this.updateOptionsBadge();
            this.optionIndex++;
        },

        showEmptyState: function() {
            const i18n = mobooking_service_edit_params.i18n;
            const emptyStateHtml = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                            <line x1="9" y1="9" x2="9.01" y2="9"/>
                            <line x1="15" y1="9" x2="15.01" y2="9"/>
                        </svg>
                    </div>
                    <h3 class="empty-state-title">${i18n.no_options_yet || 'No options added yet'}</h3>
                    <p class="empty-state-description">
                        ${i18n.add_options_prompt || 'Add customization options like room size, add-ons, or special requirements to make your service more flexible.'}
                    </p>
                    <button type="button" class="btn btn-primary add-first-option">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14"/>
                            <path d="M12 5v14"/>
                        </svg>
                        ${i18n.add_first_option || 'Add Your First Option'}
                    </button>
                </div>`;
            $('#options-container').html(emptyStateHtml);
        },

        updateOptionsBadge: function() {
            const $trigger = $('[data-tab="service-options"]');
            const optionCount = $('.option-item').length;
            let $badge = $trigger.find('.badge-secondary');

            if (optionCount > 0) {
                if (!$badge.length) {
                    $badge = $('<span class="badge badge-secondary"></span>');
                    $trigger.append($badge);
                }
                $badge.text(optionCount);
            } else if ($badge.length) {
                $badge.remove();
            }
        },

        saveService: function(isDraft = false) {
            const self = this;
            const $form = $('#mobooking-service-form');
            const formData = new FormData($form[0]);

            if (isDraft) {
                formData.set('status', 'draft');
            }

            formData.append('action', 'mobooking_save_service');

            const $saveBtn = $('#save-service-btn');
            const originalText = $saveBtn.html();
            $saveBtn.html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> ' + mobooking_service_edit_params.i18n.saving).prop('disabled', true);

            $.ajax({
                url: mobooking_service_edit_params.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.showAlert('success', response.data.message);
                        setTimeout(() => {
                            window.location.href = mobooking_service_edit_params.redirect_url;
                        }, 1500);
                    } else {
                        self.showAlert('error', response.data.message || mobooking_service_edit_params.i18n.error_saving_service);
                    }
                },
                error: function() {
                    self.showAlert('error', mobooking_service_edit_params.i18n.error_ajax);
                },
                complete: function() {
                    $saveBtn.html(originalText).prop('disabled', false);
                }
            });
        },

        deleteService: function() {
            const self = this;
            if (!confirm(mobooking_service_edit_params.i18n.confirm_delete)) {
                return;
            }

            const serviceId = $('input[name="service_id"]').val();

            $.ajax({
                url: mobooking_service_edit_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mobooking_delete_service_ajax',
                    service_id: serviceId,
                    nonce: mobooking_service_edit_params.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showAlert('success', mobooking_service_edit_params.i18n.service_deleted);
                        setTimeout(() => {
                            window.location.href = mobooking_service_edit_params.redirect_url;
                        }, 1000);
                    } else {
                        self.showAlert('error', response.data.message || mobooking_service_edit_params.i18n.error_deleting_service);
                    }
                },
                error: function() {
                    self.showAlert('error', mobooking_service_edit_params.i18n.error_ajax);
                }
            });
        },

        duplicateService: function() {
            const self = this;
            const $form = $('#mobooking-service-form');
            const formData = new FormData($form[0]);

            formData.delete('service_id');
            const currentName = formData.get('name');
            formData.set('name', currentName + ' (Copy)');
            formData.append('action', 'mobooking_save_service');

            $.ajax({
                url: mobooking_service_edit_params.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.showAlert('success', mobooking_service_edit_params.i18n.service_duplicated);
                        setTimeout(() => {
                            window.location.href = mobooking_service_edit_params.redirect_url;
                        }, 1500);
                    } else {
                        self.showAlert('error', response.data.message || mobooking_service_edit_params.i18n.error_duplicating_service);
                    }
                },
                error: function() {
                    self.showAlert('error', mobooking_service_edit_params.i18n.error_ajax);
                }
            });
        },

        openIconSelector: function() {
            if (typeof MoBookingIconSelector !== 'undefined') {
                MoBookingIconSelector.open((selectedIcon) => {
                    $('#service-icon').val(selectedIcon);
                    $('#current-icon').html(selectedIcon);
                });
            }
        },

        handleImageUpload: function(file) {
            const self = this;
            if (!file.type.startsWith('image/')) {
                this.showAlert('error', 'Please select a valid image file');
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                this.showAlert('error', 'Image size must be less than 5MB');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'mobooking_upload_service_image');
            formData.append('image', file);
            formData.append('nonce', mobooking_service_edit_params.nonce);

            const $preview = $('#image-preview');
            $preview.html('<div class="upload-loading">Uploading...</div>');

            $.ajax({
                url: mobooking_service_edit_params.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        const imageUrl = response.data.url;
                        $('#service-image-url').val(imageUrl);
                        $preview.html(`
                            <img src="${imageUrl}" alt="Service Image">
                            <button type="button" class="remove-image-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/>
                                </svg>
                            </button>
                        `);
                        $preview.removeClass('empty');
                    } else {
                        self.showAlert('error', response.data.message || mobooking_service_edit_params.i18n.error_uploading_image);
                        self.resetImagePreview();
                    }
                },
                error: function() {
                    self.showAlert('error', mobooking_service_edit_params.i18n.error_uploading_image);
                    self.resetImagePreview();
                }
            });
        },

        removeImage: function() {
            $('#service-image-url').val('');
            this.resetImagePreview();
        },

        resetImagePreview: function() {
            const $preview = $('#image-preview');
            $preview.html(`
                <div class="upload-placeholder">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                    </svg>
                    <p>Click to upload image</p>
                    <p class="text-xs text-muted-foreground">PNG, JPG up to 5MB</p>
                </div>
            `);
            $preview.addClass('empty');
        },

        showAlert: function(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-destructive';
            const iconSvg = type === 'success'
                ? '<path d="m9 12 2 2 4-4"/><path d="M21 12c.552 0 1-.448 1-1V5a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v6c0 .552.448 1 1 1h18z"/>'
                : '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>';

            const $alert = $(`
                <div class="alert ${alertClass}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        ${iconSvg}
                    </svg>
                    <span>${message}</span>
                </div>
            `);

            $('#alert-container').append($alert);

            setTimeout(() => {
                $alert.fadeOut(300, function() { $(this).remove(); });
            }, 5000);
        }
    };

    ServiceEdit.init();
});
