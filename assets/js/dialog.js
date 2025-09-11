/**
 * Universal Dialog Component (Shadcn UI inspired)
 *
 * This script provides a flexible and consistent dialog (modal) system for the application.
 * It allows for the creation of dialogs with various configurations, including different
 * icons, titles, content (HTML or text), and action buttons.
 *
 * Usage:
 *
 * // For a simple confirmation dialog:
 * const myDialog = new MoBookingDialog({
 *   title: 'Confirm Action',
 *   content: '<p>Are you sure you want to proceed?</p>',
 *   icon: 'warning', // or 'success', 'info', 'error', or custom SVG
 *   buttons: [
 *     {
 *       label: 'Cancel',
 *       class: 'secondary',
 *       onClick: (dialog) => {
 *         dialog.close();
 *       }
 *     },
 *     {
 *       label: 'Confirm',
 *       class: 'primary',
 *       onClick: (dialog) => {
 *         console.log('Confirmed!');
 *         dialog.close();
 *       }
 *     }
 *   ]
 * });
 * myDialog.show();
 *
 * // To get a reference to the dialog's DOM element:
 * const dialogElement = myDialog.getElement();
 *
 * // To get a reference to a specific element within the dialog:
 * const contentArea = myDialog.findElement('.my-custom-selector');
 */
(function (window) {
  "use strict";

  const ICONS = {
    close:
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>',
    info: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
    success:
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
    warning:
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
    error:
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
    trash:
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>',
    copy: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>',
  };

  function MoBookingDialog(options) {
    const defaults = {
      title: "",
      content: "",
      icon: null, // 'info', 'success', 'warning', 'error', 'trash', or custom SVG
      buttons: [],
      onOpen: () => {},
      onClose: () => {},
    };
    this.options = { ...defaults, ...options };
    this.dialogEl = null;
    this.overlayEl = null;

    this._create();
  }

  MoBookingDialog.prototype._create = function () {
    // Create overlay
    this.overlayEl = document.createElement("div");
    this.overlayEl.className = "nordbooking-dialog-overlay";

    // Create dialog
    this.dialogEl = document.createElement("div");
    this.dialogEl.className = "nordbooking-dialog";
    this.dialogEl.setAttribute("role", "dialog");
    this.dialogEl.setAttribute("aria-modal", "true");
    this.dialogEl.setAttribute("aria-labelledby", "nordbooking-dialog-title");

    // Header
    const header = document.createElement("div");
    header.className = "nordbooking-dialog-header";

    let iconHtml = "";
    if (this.options.icon) {
      const iconSvg = ICONS[this.options.icon] || this.options.icon;
      iconHtml = `<div class="nordbooking-dialog-icon">${iconSvg}</div>`;
    }

    header.innerHTML = `
            ${iconHtml}
            <h2 id="nordbooking-dialog-title" class="nordbooking-dialog-title">${this.options.title}</h2>
            <button class="nordbooking-dialog-close-btn">
                ${ICONS.close}
            </button>
        `;

    // Content
    const content = document.createElement("div");
    content.className = "nordbooking-dialog-content";
    if (typeof this.options.content === "string") {
      content.innerHTML = this.options.content;
    } else if (this.options.content instanceof HTMLElement) {
      content.appendChild(this.options.content);
    }

    this.dialogEl.appendChild(header);
    this.dialogEl.appendChild(content);

    // Footer
    if (this.options.buttons.length > 0) {
      const footer = document.createElement("div");
      footer.className = "nordbooking-dialog-footer";
      this.options.buttons.forEach((btnOptions) => {
        const button = document.createElement("button");
        button.className = `btn btn-${btnOptions.class || "secondary"}`;
        button.textContent = btnOptions.label;
        button.addEventListener("click", () => btnOptions.onClick(this));
        footer.appendChild(button);
      });
      this.dialogEl.appendChild(footer);
    }

    // Event listeners
    this.overlayEl.addEventListener("click", () => this.close());
    this.dialogEl
      .querySelector(".nordbooking-dialog-close-btn")
      .addEventListener("click", () => this.close());

    document.body.appendChild(this.overlayEl);
    document.body.appendChild(this.dialogEl);
  };

  MoBookingDialog.prototype.show = function () {
    this.overlayEl.style.display = "block";
    this.dialogEl.style.display = "flex";
    document.body.style.overflow = "hidden";
    this.options.onOpen(this);
  };

  MoBookingDialog.prototype.close = function () {
    this.overlayEl.classList.add("is-closing");
    this.dialogEl.classList.add("is-closing");

    const handleAnimationEnd = () => {
      this.overlayEl.remove();
      this.dialogEl.remove();
      document.body.style.overflow = "";
      this.options.onClose(this);
    };

    this.dialogEl.addEventListener("animationend", handleAnimationEnd, {
      once: true,
    });
  };

  MoBookingDialog.prototype.getElement = function () {
    return this.dialogEl;
  };

  MoBookingDialog.prototype.findElement = function (selector) {
    return this.dialogEl.querySelector(selector);
  };

  // Expose to global window object
  window.MoBookingDialog = MoBookingDialog;
})(window);
