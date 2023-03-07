(function () {
  const { mergeObjects } = BreakdanceFrontend.utils;

  class BreakdanceTOC {
    options = {
      content: {
        table: {
          advanced: {
            container_selector: "body.breakdance",
          },
        },
      },
      design: {
        collapse: false,
        style: "none",
      },
      tocSelector: ".js-breakdance-toc",
    };

    constructor(selector, options) {
      this.selector = selector;
      this.element = document.querySelector(`${this.selector}`);
      if (!this.element) return;
      this.tocSelector = this.element.querySelector(
        `${this.options.tocSelector}`
      );
      this.options = mergeObjects(this.options, options || {});
      this.headingSelectors = this.generateSelectors();

      this.init();
    }

    generateSelectors() {
      const selectors = this.options.content.table.included_headings;
      if (selectors) {
        const keys = Object.keys(selectors)
          .filter((k) => selectors[k] === true)
          .toString();
        return keys;
      } else {
        return "h2,h3,h4";
      }
    }

    setHeadingIds() {
      const containerSelector =
        this.options.content.table.advanced.container_selector;
      if (containerSelector.length <= 1) return;
      const container = document.querySelector(containerSelector);

      if (!container) return;

      const headings = [].slice.call(
        container.querySelectorAll(this.headingSelectors)
      );

      headings.forEach(function (heading, index) {
        let ref = "heading-toc-" + index;
        if (heading.hasAttribute("id") && heading.id !== "")
          ref = heading.getAttribute("id");
        else heading.setAttribute("id", ref);
      });
    }

    maybeInitAccordion() {
      this.accordion = this.element.querySelector(".js-toc-accordion");
      if (!this.accordion) return;
      this.accordionClickHandler = this.accordionClick.bind(this);
      this.accordion.classList.remove("is-on");
      this.accordion.addEventListener("click", this.accordionClickHandler);
    }

    accordionClick(event) {
      event.preventDefault();
      this.accordion.classList.toggle("is-on");
    }

    destroyAccordionClick() {
      this.accordion.removeEventListener("click", this.accordionClickHandler);
    }

    initTocbot() {
      this.tocbotInstance = tocbot;
      const collapseDepth = this.options.design.collapse == true ? 0 : 6;

      this.tocbotInstance.init({
        // Where to render the table of contents.
        tocSelector: this.options.tocSelector,
        // Where to grab the headings to build the table of contents.
        contentSelector: this.options.content.table.advanced.container_selector,
        // Which headings to grab inside of the contentSelector element.
        headingSelector: this.headingSelectors,
        // Headings that match the ignoreSelector will be skipped.
        ignoreSelector:
          this.options.content.table.advanced.ignore_selector || ".toc-ignore",
        // How many heading levels should not be collapsed.
        // For example, number 6 will show everything since
        // there are only 6 heading levels and number 0 will collapse them all.
        // The sections that are hidden will open
        // and close as you scroll to headings within them.
        collapseDepth: collapseDepth,
        // Smooth scroll offset.
        scrollSmoothOffset: 20,
      });
      this.tocbotInstance.refresh();
    }

    init() {
      this.setHeadingIds();
      this.maybeInitAccordion();
      this.initTocbot();
    }

    update() {
      if (!this.tocbotInstance) return;
      this.tocbotInstance.refresh();
    }

    destroy() {
      if (!this.tocbotInstance) return;
      this.tocbotInstance.destroy();
      this.tocbotInstance = null;
    }
  }

  window.BreakdanceTOC = BreakdanceTOC;
})();
