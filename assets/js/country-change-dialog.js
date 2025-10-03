/**
 * Country Change Confirmation Dialog
 * Simple modal for confirming country changes that will clear selected cities
 */

(function ($) {
  "use strict";

  /**
   * Show country change confirmation dialog
   */
  function showCountryChangeDialog(currentCountry, newCountry, onConfirm, onCancel) {
    const dialogHtml = `
      <div class="country-change-dialog-overlay">
        <div class="country-change-dialog">
          <div class="dialog-header">
            <h3>Change Service Country?</h3>
          </div>
          <div class="dialog-content">
            <div class="warning-message">
              <svg class="warning-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
              </svg>
              <div class="warning-text">
                <p><strong>Changing from ${currentCountry} to ${newCountry} will remove all previously selected cities and areas.</strong></p>
                <p>This action cannot be undone. Are you sure you want to continue?</p>
              </div>
            </div>
          </div>
          <div class="dialog-actions">
            <button type="button" class="btn btn-secondary cancel-btn">Cancel</button>
            <button type="button" class="btn btn-primary confirm-btn">Yes, Change Country</button>
          </div>
        </div>
      </div>
    `;

    const $dialog = $(dialogHtml);
    $('body').append($dialog);

    // Bind events
    $dialog.find('.cancel-btn, .country-change-dialog-overlay').on('click', function(e) {
      if (e.target === this) {
        $dialog.remove();
        if (onCancel) onCancel();
      }
    });

    $dialog.find('.confirm-btn').on('click', function() {
      $dialog.remove();
      if (onConfirm) onConfirm();
    });

    // Show dialog with animation
    setTimeout(() => {
      $dialog.addClass('show');
    }, 10);
  }

  // Make function available globally
  window.showCountryChangeDialog = showCountryChangeDialog;

})(jQuery);