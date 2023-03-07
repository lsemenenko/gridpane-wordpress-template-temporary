(function ($) {
  const debounce = function (callback, wait, immediate) {
    let timeout;
    return function () {
      const context = this,
        args = arguments;
      const later = function () {
        timeout = null;
        if (!immediate) callback.apply(context, args);
      };
      const callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
      if (callNow) callback.apply(context, args);
    };
  };

  const delayedUpdateCartItem = debounce((input) => {
    const event = new CustomEvent("breakdance_product_qty_update", {
      detail: input
    });

    dispatchEvent(event);
    blockUI(input);
  }, 300);

  const afterInputChange = (input) => {
    if (isLoopProduct(input)) {
      getAddToCartButton(input)
        ?.setAttribute("data-quantity", input.value);
    } else if (isSingleProduct(input)) {
      // Do nothing. WooCommerce automatically picks up the input.
    } else {
      delayedUpdateCartItem(input);
    }
  };

  const isIncrement = (target) => {
    return target.matches(".bde-quantity-button--inc");
  };

  const isSingleProduct = (input) => {
    return !!input.closest("form.cart");
  };

  const isLoopProduct = (input) => {
    return !!input.closest("li.product");
  };

  const blockUI = (input) => {
    const row = input.closest(".woocommerce-mini-cart-item");

    $(row).block({
      message: null,
      overlayCSS: {
        opacity: 0.6
      }
    });
  };

  const getAddToCartButton = (input) => {
    return input.closest("li.product").querySelector(".add_to_cart_button");
  };

  const getQuantityInput = (event) => {
    return event.target.parentElement.querySelector(".qty");
  };

  const onMaybeButtonClick = (event) => {
    if (!event.target.matches(".bde-quantity-button")) return;

    event.preventDefault();

    const input = getQuantityInput(event);
    const action = isIncrement(event.target) ? "stepUp" : "stepDown";

    input[action](); // Increment or decrement input

    afterInputChange(input);
  };

  addEventListener("DOMContentLoaded", () => {
    addEventListener("click", onMaybeButtonClick);
  });
}(jQuery));
