(function() {
  class BreakdanceSearchForm {
    constructor(selector) {
      this.selector = selector;
      this.element = this.queryElement(`${this.selector} .js-search-form`);
      this.type = this.element.dataset.type;
      this.expandButton = this.queryElement(
        `${this.selector} .js-search-form-expand-button`
      );
      this.closeButton = this.queryElement(
        `${this.selector} .js-search-form-close`
      );
      this.searchField = this.queryElement(
        `${this.selector} .js-search-form-field`
      );
      this.lightboxBg = this.queryElement(
        `${this.selector} .js-search-form-lightbox-bg`
      );

      this.init();
    }

    queryElement(element) {
      if (typeof element == "string") {
        return document.querySelector(element);
      }
      return element;
    }

    toggleForm() {
      this.element.classList.toggle("is-active");
      this.searchField.focus();
    }

    closeFormEscape(event) {
      if (event.key === "Escape") {
        this.element.classList.remove("is-active");
      }
    }

    closeForm(event) {
      this.element.classList.remove("is-active");
    }

    // Methods
    destroy() {
      if (this.type == "expand") {
        this.expandButton.onclick = "";
      }
      if (this.type == "full-screen") {
        this.closeButton.onclick = "";
        this.lightboxBg.onclick = "";
        this.element.removeEventListener("keyup", event =>
          this.closeFormEscape(event)
        );
      }
    }

    update() {
      this.destroy();
      this.init();
    }

    init() {
      if (this.type == "expand") {
        this.expandButton.onclick = event => this.toggleForm(event);
      }
      if (this.type == "full-screen") {
        this.expandButton.onclick = event => this.toggleForm(event);
        this.closeButton.onclick = event => this.closeForm(event);
        this.lightboxBg.onclick = event => this.closeForm(event);
        this.element.addEventListener("keyup", event =>
          this.closeFormEscape(event)
        );
      }
    }
  }

  window.BreakdanceSearchForm = BreakdanceSearchForm;
})();
