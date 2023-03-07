(function () {
  const { mergeObjects, debounce, matchAnyMedia, matchMedia } = BreakdanceFrontend.utils;
  const swiperInstance = window.BreakdanceSwiper();

  class BreakdanceGallery {
    defaultOptions = {
      type: "grid",
      row_height: { number: 300 },
      columns: {
        breakpoint_base: 3,
        breakpoint_phone_portrait: 1,
      },
      slider: {
        settings: null,
        pagination: null,
      },
    };

    items = [];
    filterActiveClass = "is-active";
    sliderActiveClass = "is-active";

    constructor(selector, options) {
      this.relayout = this.relayout.bind(this);
      this.resize = this.resize.bind(this);
      this.update = debounce(this._update, 100);
      this.onImagesLoaded = this.onImagesLoaded.bind(this);
      this.onFilterChange = this.onFilterChange.bind(this);
      this.selector = selector;
      this.options = mergeObjects(this.defaultOptions, options);
      this.init();
    }

    checkForImagesLoaded() {
      return this.options.type === "masonry";
    }

    getIsotopeSettings(layoutInstant = true) {
      return {
        layoutMode: this.getLayoutMode(),
        layoutInstant,
        packery: { gutter: '.ee-gallery-gutter' },
        masonry: { gutter: '.ee-gallery-gutter' }
      };
    }

    /*
     * Turn DOM Elements into Isotope-ready Items
     * This is required in order to make Isotope work in the Builder.
     */
    itemize(items) {
      return items.map((elem) => new Isotope.Item(elem, this.iso));
    }

    /*
     * Force Isotope to accept our DOM Elements.
     * This is required due to a bug in the library.
     * "elem instanceof HTMLCollection" always return false inside same-origin iframes
     */
    appendItems(elems) {
      const items = this.itemize(elems);

      if (!items.length) return;

      this.iso.items = items;
      this.iso.filteredItems = items;
    }

    relayout() {
      if (!this.iso) return;
      this.iso.layout();
    }

    getItemElems(elem) {
      return [].slice.call(elem.querySelectorAll(".ee-gallery-item"));
    }

    onImagesLoaded() {
      this.relayout();
    }

    determineFilter(category) {
      return category === "all" ? "*" : `[data-category="${category}"]`;
    }

    // Filters
    filterBy(node) {
      const category = (node.value || node.id).split("-").pop();

      if (this.options.type === "slider") {
        this.filterSlider(category);
      } else {
        const filter = this.determineFilter(category);
        this.iso.arrange({
          layoutInstant: false,
          filter,
        });
      }

      const button = this.filterButtons.find((button) => button.id === node.id);

      if (button) this.setActiveButton(button);
    }

    filterSlider(category) {
      if (!this.swipers) return;

      this.swipers.forEach((swiper) => {
        swiper.classList.remove(this.sliderActiveClass);
      });

      document
        .querySelector(`[data-gallery-swiper-category="${category}"]`)
        .classList.add(this.sliderActiveClass);
    }

    setActiveButton(activeButton) {
      this.filterButtons.forEach((button) => {
        button.classList.remove(this.filterActiveClass);
      });

      activeButton.classList.add(this.filterActiveClass);
    }

    onFilterChange(event) {
      event.preventDefault();

      const target = event.currentTarget;
      this.filterBy(target);

      if (this.options.type !== "slider") {
        this.maybeJustifyItems();
      }
    }

    getAll(selector) {
      return [].slice.call(this.element.querySelectorAll(selector));
    }

    /*
     * Filter Bar
     * - Available when the gallery type is "Multiple".
     */

    initFilters() {
      const defaultActiveIndex = 0;
      const mobileFilter = this.element.querySelector(".js-tab-select");

      this.filterButtons = this.getAll(".js-tab");

      this.filterButtons.forEach((button, index) => {
        if (index === defaultActiveIndex) {
          this.filterBy(button);
        }

        button.addEventListener("click", this.onFilterChange);
      });

      if (mobileFilter) {
        mobileFilter.addEventListener("change", this.onFilterChange);
      }
    }

    maybeJustifyItems() {
      if (this.options.type !== "justified") {
        return this.unjustifyItems();
      }

      const justifiedLayout = require("justified-layout"); // TODO: Remove dependency on browserify.

      const items = this.iso.filteredItems;

      const geometry = items.map((item) => {
        const [width, height] = item.element.dataset.lgSize.split("-");
        return { width, height };
      });

      const config = {
        containerWidth: this.galleryEl.clientWidth,
        containerPadding: 0,
        boxSpacing: this.getMasonryGap(),
        targetRowHeight: this.options.row_height.number,
      };

      const output = justifiedLayout(geometry, config);

      items.forEach((item, index) => {
        const box = output.boxes[index];
        item.element.style.width = `${box.width}px`;
        item.element.style.height = `${box.height}px`;
      });

      this.relayout();
    }

    unjustifyItems() {
      const items = this.iso.filteredItems;
      items.forEach((item, index) => {
        item.element.style.removeProperty("width");
        item.element.style.removeProperty("height");
      });
    }

    getMasonryGap() {
      if (!this.options.gap) return 0;

      const { BASE_BREAKPOINT_ID, breakpoints } = window.BreakdanceFrontend.data;

      const foundBreakpoint = breakpoints
        .filter(b => b.id !== BASE_BREAKPOINT_ID)
        .find(b => matchMedia(b.id));

      const currentBreakpoint = foundBreakpoint?.id || BASE_BREAKPOINT_ID;

      // version >= 1.1 - backwards compatibility for non-breakpoint values.
      const gap = this.options.gap[currentBreakpoint]?.number || this.options.gap.number || 0;
      return Math.max(gap, 0);
    }

    getLayoutMode() {
      const type = this.options.type || "grid";

      const layoutModes = {
        grid: "masonry",
        masonry: "masonry",
        justified: "packery",
      };

      return layoutModes[type];
    }

    refresh(options = {}) {
      this.options = mergeObjects(this.defaultOptions, options);
      this.maybeJustifyItems();
      const config = this.getIsotopeSettings();
      this.iso.arrange(config);
      this.init();
    }

    _update(options = {}) {
      this.options = mergeObjects(this.defaultOptions, options);
      this.destroy();
      this.init();
    }

    resize() {
      this.maybeJustifyItems();
    }

    destroy() {
      this.destroyIsotope();
      this.destroySwiper();

      removeEventListener("resize", this.resize);
    }

    destroyIsotope() {
      if (!this.iso) return;

      this.iso.destroy();
      this.iso = null;

      if (this.checkForImagesLoaded()) {
        imagesLoaded(this.element).off("progress", this.onImagesLoaded);
      }
    }

    initIsotope(layoutInstant) {
      const config = this.getIsotopeSettings(layoutInstant);

      this.iso = new Isotope(this.galleryEl, config);
      this.appendItems(this.items);

      this.relayout();

      if (this.checkForImagesLoaded()) {
        imagesLoaded(this.element).on("progress", this.onImagesLoaded);
      }
    }

    initSwiper() {
      this.swipers = this.element.querySelectorAll(".ee-gallery-swiper");

      if (!this.swipers) return;

      this.swipers.forEach((swiper) => {
        swiperInstance.update({
          id: swiper.id,
          selector: `#${swiper.id}`,
          settings: this.options.slider.settings,
          paginationSettings: this.options.slider.pagination,
        });
      });
    }

    destroySwiper() {
      if (!this.swipers) return;
      this.swipers.forEach((swiper) => {
        swiperInstance.destroy(swiper.id);
      });
    }

    init(layoutInstant) {
      this.element = document.querySelector(this.selector);

      // Bail if slider option is on
      if (this.options.type === "slider") {
        this.initSwiper();
      } else {
        // Element that holds the images
        this.galleryEl = this.element.querySelector(".ee-gallery");

        // Images dom elements
        this.items = this.getItemElems(this.element);

        this.initIsotope(layoutInstant);
        this.maybeJustifyItems();
      }

      this.initFilters();

      addEventListener("resize", this.resize);
    }
  }

  window.BreakdanceGallery = BreakdanceGallery;
})();
