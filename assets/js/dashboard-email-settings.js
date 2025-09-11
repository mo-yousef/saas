jQuery(document).ready(function ($) {
  "use strict";

  if (typeof nordbooking_email_settings_params === "undefined") {
    console.error("Email settings parameters are not available.");
    return;
  }

  const EmailEditor = {
    // DOM Elements
    selector: $("#email-template-selector"),
    subjectInput: $("#email-editor-subject"),
    bodyContainer: $("#email-editor-body"),
    variablesList: $("#email-variables-list"),
    previewIframe: $("#email-preview-iframe"),

    // State
    currentTemplateKey: null,
    templatesData: {},
    baseEmailTemplateHtml: "",
    isInitialized: false,

    // Config
    i18n: nordbooking_email_settings_params.i18n || {},

    componentRenderers: {}, // Will be initialized in init()
    previewRenderers: {
      header: (component) => `<h2>${component.text}</h2>`,
      text: (component) =>
        `<p style="white-space: pre-wrap;">${component.text}</p>`,
      button: (component) =>
        `<table border="0" cellpadding="0" cellspacing="0"><tr><td style="background-color: #3498db; border-radius: 5px; text-align: center;"><a href="${component.url}" target="_blank" style="display: inline-block; color: #ffffff; background-color: #3498db; border: solid 1px #3498db; border-radius: 5px; box-sizing: border-box; cursor: pointer; text-decoration: none; padding: 12px 25px;">${component.text}</a></td></tr></table>`,
      spacer: () => `<div style="height: 20px;"></div>`,
    },

    init: function () {
      if (!this.selector.length) return;

      this.templatesData = nordbooking_email_settings_params.email_templates_data || {};
      this.initializeRenderers();
      this.bindEvents();
      this.fetchBaseTemplate();

      this.currentTemplateKey = this.selector.val();
      this.loadTemplateIntoEditor();
      this.isInitialized = true;
    },

    initializeRenderers: function () {
      this.componentRenderers = {
        header: (component) => `<div class="email-component" data-type="header">
                                            <input type="text" class="component-input" value="${
                                              component.text || ""
                                            }" placeholder="${
          this.i18n.header_placeholder
        }">
                                            <button class="delete-component" title="${
                                              this.i18n.delete_component_title
                                            }"></button>
                                         </div>`,
        text: (component) => `<div class="email-component" data-type="text">
                                            <textarea class="component-input" placeholder="${
                                              this.i18n.text_placeholder
                                            }">${
          component.text || ""
        }</textarea>
                                            <button class="delete-component" title="${
                                              this.i18n.delete_component_title
                                            }"></button>
                                         </div>`,
        button: (component) => `<div class="email-component" data-type="button">
                                            <input type="text" class="component-input" value="${
                                              component.text || ""
                                            }" placeholder="${
          this.i18n.button_placeholder
        }">
                                            <input type="text" class="component-url-input" value="${
                                              component.url || ""
                                            }" placeholder="${
          this.i18n.button_url_placeholder
        }">
                                            <button class="delete-component" title="${
                                              this.i18n.delete_component_title
                                            }"></button>
                                         </div>`,
        spacer: (component) => `<div class="email-component" data-type="spacer">
                                            <span>${this.i18n.spacer_text}</span>
                                            <button class="delete-component" title="${this.i18n.delete_component_title}"></button>
                                         </div>`,
      };
    },

    fetchBaseTemplate: function () {
      $.get(nordbooking_email_settings_params.base_template_url, (data) => {
        this.baseEmailTemplateHtml = data;
        this.updatePreview();
      }).fail(() => console.error("Failed to fetch base email template."));
    },

    bindEvents: function () {
      this.selector.on("change", () => {
        this.currentTemplateKey = this.selector.val();
        this.loadTemplateIntoEditor();
      });

      this.subjectInput.on(
        "input",
        this.debounce(() => {
          this.templatesData[this.currentTemplateKey].subject = this.subjectInput.val();
          this.updatePreview();
        }, 300)
      );

      this.bodyContainer.on(
        "input",
        ".component-input, .component-url-input",
        this.debounce((e) => {
          this.updateBodyState();
          this.updatePreview();
        }, 300)
      );

      this.bodyContainer.on("click", ".delete-component", (e) => {
        $(e.target).closest(".email-component").remove();
        this.updateBodyState();
        this.updatePreview();
      });

      this.variablesList.on("click", "li", function () {
        const textToCopy = $(this).text();
        const originalText = textToCopy;

        const copyToClipboard = (text) => {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            } else {
                let textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.top = "0";
                textArea.style.left = "0";
                textArea.style.opacity = "0";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                } catch (err) {
                    console.error('Fallback: Oops, unable to copy', err);
                    return Promise.reject(err);
                }
                document.body.removeChild(textArea);
                return Promise.resolve();
            }
        };

        copyToClipboard(textToCopy).then(() => {
            $(this).text("Copied!");
            setTimeout(() => $(this).text(originalText), 1000);
        }).catch(err => {
            console.error('Could not copy text: ', err);
            $(this).text("Copy Failed");
            setTimeout(() => $(this).text(originalText), 1500);
        });
      });

    },

    loadTemplateIntoEditor: function () {
      if (!this.currentTemplateKey) return;
      const data = this.templatesData[this.currentTemplateKey];
      if (!data) return;

      this.subjectInput.val(data.subject);
      this.bodyContainer.empty();
      if (data.body && data.body.length > 0) {
        data.body.forEach((component) => {
          if (this.componentRenderers[component.type]) {
            this.bodyContainer.append(
              this.componentRenderers[component.type](component)
            );
          }
        });
      }

      this.loadVariablesList();
      if (this.isInitialized) this.updatePreview();
    },

    loadVariablesList: function () {
      const templateInfo =
        nordbooking_email_settings_params.templates[this.currentTemplateKey];
      if (templateInfo && templateInfo.variables) {
        this.variablesList.html(
          templateInfo.variables.map((v) => `<li>${v}</li>`).join("")
        );
      } else {
        this.variablesList.empty();
      }
    },

    updateBodyState: function () {
      if (!this.currentTemplateKey) return;
      const newBody = [];
      this.bodyContainer.find(".email-component").each(function () {
        const el = $(this);
        const type = el.data("type");
        const componentData = { type };
        if (type === "header" || type === "text") {
          componentData.text = el.find(".component-input").val();
        } else if (type === "button") {
          componentData.text = el.find(".component-input").val();
          componentData.url = el.find(".component-url-input").val();
        }
        newBody.push(componentData);
      });
      this.templatesData[this.currentTemplateKey].body = newBody;
    },

    renderBodyForPreview: function () {
      if (!this.currentTemplateKey) return "";
      const bodyData = this.templatesData[this.currentTemplateKey].body;
      return bodyData
        .map((c) =>
          this.previewRenderers[c.type] ? this.previewRenderers[c.type](c) : ""
        )
        .join("");
    },

    updatePreview: function () {
      if (!this.baseEmailTemplateHtml || !this.currentTemplateKey || !this.templatesData[this.currentTemplateKey]) return;
      let previewHtml = this.baseEmailTemplateHtml;
      const bodyHtml = this.renderBodyForPreview();
      previewHtml = previewHtml.replace(/{{BODY_CONTENT}}/g, bodyHtml);

      const allVars = {
        ...nordbooking_email_settings_params.biz_settings,
        ...nordbooking_email_settings_params.dummy_data,
      };
      allVars["{{SUBJECT}}"] = this.templatesData[this.currentTemplateKey].subject;

      for (const [key, value] of Object.entries(allVars)) {
        const regex = new RegExp(
          key.replace(/[-\/\\^$*+?.()|[\]{}]/g, "\\$&"),
          "g"
        );
        previewHtml = previewHtml.replace(regex, value || "");
      }

      this.previewIframe.attr("srcdoc", previewHtml);
    },

    debounce: function (func, wait) {
      let timeout;
      return function (...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
      };
    },
  };

  window.EmailEditor = EmailEditor;
  window.EmailEditor.init();
});
