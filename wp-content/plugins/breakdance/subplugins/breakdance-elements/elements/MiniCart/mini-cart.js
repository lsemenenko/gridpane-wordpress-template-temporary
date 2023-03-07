/* global BREAKDANCE_ADDED_TO_CART */
(function () {
  const { mergeObjects } = BreakdanceFrontend.utils;

  class BreakdanceMiniCart {
    defaultOptions = {
      cart: {
        open_cart_on_add: false,
      },
    };

    constructor(selector, options) {
      this.open = this.open.bind(this);
      this.onToggleClick = this.onToggleClick.bind(this);
      this.onCloseClick = this.onCloseClick.bind(this);
      this.onEscClick = this.onEscClick.bind(this);
      this.onMaybeClickOutside = this.onMaybeClickOutside.bind(this);

      this.selector = selector;
      this.options = mergeObjects(this.defaultOptions, options);
      this.activeClass = "bde-mini-cart-offcanvas--active";
      this.activeBodyClass = "is-breakdance-mini-cart-open";

      this.init();
    }

    update(options) {
      this.options = mergeObjects(this.defaultOptions, options);

      this.destroy();
      this.init();
    }

    destroy() {
      this.unbindListeners();
    }

    open() {
      this.offcanvas.classList.add(this.activeClass);
      document.body.classList.add(this.activeBodyClass);
    }

    close() {
      this.offcanvas.classList.remove(this.activeClass);
      document.body.classList.remove(this.activeBodyClass);
    }

    onToggleClick(event) {
      event.preventDefault();

      const isActive = this.offcanvas.classList.contains(this.activeClass);

      if (isActive) {
        this.close();
      } else {
        this.open();
      }
    }

    onCloseClick() {
      this.close();
    }

    onEscClick(event) {
      if (event.keyCode === 27) {
        this.close();
      }
    }

    onMaybeClickOutside(event) {
      if (!this.element.contains(event.target)) {
        this.close();
      }
    }

    bindListeners() {
      if (!this.toggleButton || !this.closeButton || !this.overlay) {
        return;
      }

      this.toggleButton.addEventListener("click", this.onToggleClick);
      this.closeButton.addEventListener("click", this.onCloseClick);
      this.overlay.addEventListener("click", this.onCloseClick);

      window.addEventListener("pointerup", this.onMaybeClickOutside);
      document.addEventListener("keydown", this.onEscClick);

      if (this.options.cart.open_cart_on_add) {
        // Ajax add to cart
        jQuery(document.body).on("added_to_cart", this.open);

        // Non-ajax add to cart
        if (typeof BREAKDANCE_ADDED_TO_CART !== "undefined") this.open();
      }
    }

    unbindListeners() {
      if (!this.toggleButton || !this.closeButton || !this.overlay) {
        return;
      }

      this.toggleButton.removeEventListener("click", this.onToggleClick);
      this.closeButton.removeEventListener("click", this.onToggleClick);
      this.overlay.removeEventListener("click", this.onToggleClick);

      window.removeEventListener("pointerup", this.onMaybeClickOutside);
      document.removeEventListener("keydown", this.onEscClick);

      jQuery(document.body).off("added_to_cart", this.open);
    }

    init() {
      this.element = document.querySelector(this.selector);

      if (!this.element) {
        return;
      }

      this.toggleButton = this.element.querySelector(".bde-mini-cart-toggle");
      this.closeButton = this.element.querySelector(
        ".bde-mini-cart-offcanvas__close-button"
      );
      this.offcanvas = this.element.querySelector(".bde-mini-cart-offcanvas");
      this.overlay = this.element.querySelector(".bde-mini-cart-offcanvas-overlay");

      this.bindListeners();
    }
  }

  window.BreakdanceMiniCart = BreakdanceMiniCart;
})();
