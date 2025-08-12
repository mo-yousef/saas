document.addEventListener('DOMContentLoaded', function() {
    // Service Edit functionality
    const ServiceEdit = {
        optionIndex: mobooking_service_edit_params.option_count,

        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initSwitches();
            document.querySelectorAll('.option-item').forEach(option => this.bindOptionEvents(option));
        },

        bindEvents: function() {
            // Add option button
            document.getElementById('add-option-btn')?.addEventListener('click', () => this.addOption());
            document.querySelector('.add-first-option')?.addEventListener('click', () => this.addOption());

            // Form submission
            document.getElementById('mobooking-service-form').addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveService();
            });

            // Save as draft
            document.getElementById('save-draft-btn').addEventListener('click', () => this.saveService(true));

            // Delete and duplicate
            document.getElementById('delete-service-btn')?.addEventListener('click', () => this.deleteService());
            document.getElementById('duplicate-service-btn')?.addEventListener('click', () => this.duplicateService());

            // Icon and image handling
            document.getElementById('select-icon-btn').addEventListener('click', () => this.openIconSelector());
            document.getElementById('image-preview').addEventListener('click', function() {
                if (!this.querySelector('img')) {
                    document.getElementById('service-image-upload').click();
                }
            });
            document.getElementById('service-image-upload').addEventListener('change', (e) => {
                if (e.target.files[0]) this.handleImageUpload(e.target.files[0]);
            });
            document.querySelector('.remove-image-btn')?.addEventListener('click', (e) => {
                e.stopPropagation();
                this.removeImage();
            });
        },

        initTabs: function() {
            const triggers = document.querySelectorAll('.tabs-trigger');
            const contents = document.querySelectorAll('.tabs-content');

            triggers.forEach(trigger => {
                trigger.addEventListener('click', () => {
                    const tabId = trigger.dataset.tab;

                    triggers.forEach(t => {
                        t.classList.remove('active');
                        t.setAttribute('aria-selected', 'false');
                    });
                    trigger.classList.add('active');
                    trigger.setAttribute('aria-selected', 'true');

                    contents.forEach(content => content.classList.remove('active'));
                    document.getElementById(tabId).classList.add('active');
                });
            });
        },

        initSwitches: function() {
            document.addEventListener('click', (e) => {
                if (e.target.closest('.switch')) {
                    const switchEl = e.target.closest('.switch');
                    const isChecked = switchEl.classList.contains('switch-checked');
                    const hiddenInput = switchEl.parentNode.querySelector('input[type="hidden"]');

                    if (isChecked) {
                        switchEl.classList.remove('switch-checked');
                        if (hiddenInput) hiddenInput.value = switchEl.dataset.switch === 'status' ? 'inactive' : '0';
                    } else {
                        switchEl.classList.add('switch-checked');
                        if (hiddenInput) hiddenInput.value = switchEl.dataset.switch === 'status' ? 'active' : '1';
                    }

                    // Update status label
                    if (switchEl.dataset.switch === 'status') {
                        const label = switchEl.parentNode.querySelector('.text-sm');
                        if (label) {
                            label.textContent = switchEl.classList.contains('switch-checked') ? 'Active' : 'Inactive';
                        }
                    }
                }
            });
        },

        addOption: function() {
            const container = document.getElementById('options-container');
            const emptyState = container.querySelector('.empty-state');
            if (emptyState) {
                emptyState.remove();
            }

            const template = document.getElementById('mobooking-option-template');
            if (!template) {
                console.error('Option template not found!');
                return;
            }

            // Get the HTML from the template and replace the index placeholder
            const optionHtml = template.innerHTML.replace(/__INDEX__/g, this.optionIndex);

            container.insertAdjacentHTML('beforeend', optionHtml);

            const newOption = container.lastElementChild;
            if (newOption) {
                this.bindOptionEvents(newOption);

                // Expand the new option and focus
                newOption.classList.add('expanded');
                newOption.querySelector('.option-content').style.display = 'block';
                newOption.querySelector('.option-name-input').focus();
            }

            this.updateOptionsBadge();
            this.optionIndex++;
        },

        bindOptionEvents: function(optionElement) {
            // Toggle option
            optionElement.querySelector('.toggle-option').addEventListener('click', () => {
                optionElement.classList.toggle('expanded');
                const content = optionElement.querySelector('.option-content');
                content.style.display = optionElement.classList.contains('expanded') ? 'block' : 'none';
            });

            // Delete option
            optionElement.querySelector('.delete-option').addEventListener('click', () => {
                if (confirm('Are you sure you want to delete this option?')) {
                    optionElement.remove();
                    this.updateOptionsBadge();

                    if (document.querySelectorAll('.option-item').length === 0) {
                        this.showEmptyState();
                    }
                }
            });

            // Update option name in header
            optionElement.querySelector('.option-name-input').addEventListener('input', (e) => {
                const nameDisplay = optionElement.querySelector('.option-name');
                nameDisplay.textContent = e.target.value || 'New Option';
            });

            // Update option type badge
            optionElement.querySelectorAll('input[type="radio"][name^="options["]').forEach(radio => {
                radio.addEventListener('change', (e) => {
                    const badge = optionElement.querySelector('.option-badges .badge-outline');
                    const typeLabel = e.target.closest('.option-type-card').querySelector('.option-type-title').textContent;
                    if (badge) {
                        badge.textContent = typeLabel;
                    }
                });
            });
        },

        showEmptyState: function() {
            const container = document.getElementById('options-container');
            container.innerHTML = mobooking_service_edit_params.i18n.empty_state_html;
            container.querySelector('.add-first-option')?.addEventListener('click', () => this.addOption());
        },

        updateOptionsBadge: function() {
            const trigger = document.querySelector('[data-tab="service-options"]');
            const optionCount = document.querySelectorAll('.option-item').length;
            let badge = trigger.querySelector('.badge-secondary');

            if (optionCount > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'badge badge-secondary';
                    trigger.appendChild(badge);
                }
                badge.textContent = optionCount;
            } else if (badge) {
                badge.remove();
            }
        },

        saveService: function(isDraft = false) {
            const form = document.getElementById('mobooking-service-form');
            const formData = new FormData(form);

            if (isDraft) {
                formData.set('status', 'draft');
            }

            formData.append('action', 'mobooking_save_service');

            // Show loading state
            const saveBtn = document.getElementById('save-service-btn');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Saving...';
            saveBtn.disabled = true;

            // Submit via AJAX
            fetch(mobooking_service_edit_params.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showAlert('success', data.data.message);
                    setTimeout(() => {
                        window.location.href = mobooking_service_edit_params.redirect_url;
                    }, 1500);
                } else {
                    this.showAlert('error', data.data.message || 'An error occurred while saving the service.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showAlert('error', 'An unexpected error occurred. Please try again.');
            })
            .finally(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            });
        },

        deleteService: function() {
            if (!confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
                return;
            }

            const serviceId = document.querySelector('input[name="service_id"]').value;
            const formData = new FormData();
            formData.append('action', 'mobooking_delete_service_ajax');
            formData.append('service_id', serviceId);
            formData.append('nonce', mobooking_service_edit_params.nonce);

            fetch(mobooking_service_edit_params.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showAlert('success', 'Service deleted successfully');
                    setTimeout(() => {
                        window.location.href = mobooking_service_edit_params.redirect_url;
                    }, 1000);
                } else {
                    this.showAlert('error', data.data.message || 'Failed to delete service');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showAlert('error', 'An unexpected error occurred');
            });
        },

        duplicateService: function() {
            const form = document.getElementById('mobooking-service-form');
            const formData = new FormData(form);

            formData.delete('service_id');
            const currentName = formData.get('name');
            formData.set('name', currentName + ' (Copy)');
            formData.append('action', 'mobooking_save_service');

            fetch(mobooking_service_edit_params.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showAlert('success', 'Service duplicated successfully');
                    setTimeout(() => {
                        window.location.href = mobooking_service_edit_params.redirect_url;
                    }, 1500);
                } else {
                    this.showAlert('error', data.data.message || 'Failed to duplicate service');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showAlert('error', 'An unexpected error occurred');
            });
        },

        openIconSelector: function() {
            // Icon selector implementation
            if (typeof MoBookingIconSelector !== 'undefined') {
                MoBookingIconSelector.open((selectedIcon) => {
                    document.getElementById('service-icon').value = selectedIcon;
                    document.getElementById('current-icon').innerHTML = selectedIcon;
                });
            }
        },

        handleImageUpload: function(file) {
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

            const preview = document.getElementById('image-preview');
            preview.innerHTML = '<div class="upload-loading">Uploading...</div>';

            fetch(mobooking_service_edit_params.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const imageUrl = data.data.url;
                    document.getElementById('service-image-url').value = imageUrl;
                    preview.innerHTML = `
                        <img src="${imageUrl}" alt="Service Image">
                        <button type="button" class="remove-image-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18"/>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                <path d="m19 6-1 14H6L5 6"/>
                            </svg>
                        </button>
                    `;
                    preview.classList.remove('empty');
                } else {
                    this.showAlert('error', data.data.message || 'Failed to upload image');
                    this.resetImagePreview();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showAlert('error', 'Failed to upload image');
                this.resetImagePreview();
            });
        },

        removeImage: function() {
            document.getElementById('service-image-url').value = '';
            this.resetImagePreview();
        },

        resetImagePreview: function() {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = `
                <div class="upload-placeholder">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                        <circle cx="9" cy="9" r="2"/>
                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                    </svg>
                    <p>Click to upload image</p>
                    <p class="text-xs text-muted-foreground">PNG, JPG up to 5MB</p>
                </div>
            `;
            preview.classList.add('empty');
        },

        showAlert: function(type, message) {
            const container = document.getElementById('alert-container');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-destructive';
            const iconSvg = type === 'success'
                ? '<path d="m9 12 2 2 4-4"/><path d="M21 12c.552 0 1-.448 1-1V5a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v6c0 .552.448 1 1 1h18z"/>'
                : '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>';

            const alert = document.createElement('div');
            alert.className = `alert ${alertClass}`;
            alert.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    ${iconSvg}
                </svg>
                <span>${message}</span>
            `;

            container.appendChild(alert);

            setTimeout(() => {
                if (alert.parentElement) {
                    alert.remove();
                }
            }, 5000);
        }
    };

    // Initialize the service edit functionality
    ServiceEdit.init();
});
