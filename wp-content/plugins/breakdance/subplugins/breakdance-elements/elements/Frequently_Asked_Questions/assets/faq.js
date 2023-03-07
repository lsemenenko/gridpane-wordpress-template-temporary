(function () {
  const { mergeObjects } = BreakdanceFrontend.utils;

  class BreakdanceFaq {
    options = {
      accordion: false,
      openFirst: false,
    };

    constructor(selector, options) {
      this.selector = selector;
      this.elements = document.querySelectorAll(
        `${this.selector} .js-faq-item`
      );
      this.options = mergeObjects(this.options, options || {});
      this.init();
    }

    toggleClass(event) {
      const { accordion } = this.options;

      if (accordion === true) {
        this.hideAll();
      }

      event.target.closest(".bde-faq__item").classList.toggle("is-active");
    }

    removeClass(item) {
      item.closest(".bde-faq__item").classList.remove("is-active");
    }

    hideAll() {
      this.elements.forEach((item) => this.removeClass(item));
    }

    update(options = {}) {
      this.options = mergeObjects(this.options, options);
      this.destroy();
      this.init();
    }

    openFirst() {
      this.elements[0].closest(".bde-faq__item").classList.add("is-active");
    }

    destroy() {
      if (!this.elements) return;
      this.hideAll();
      this.elements.forEach((item) => {
        item.onclick = "";
      });
    }

    init() {
      const { openFirst } = this.options;

      if (openFirst === true) {
        this.openFirst();
      }

      this.elements.forEach(
        (item) => (item.onclick = (event) => this.toggleClass(event))
      );
    }
  }

  window.BreakdanceFaq = BreakdanceFaq;
})();
