(function ($) {
  const updateQty = async (input) => {
    const url = window.BreakdanceFrontend.data.ajaxUrl;
    const hash = input.getAttribute("name").replace(/cart\[(\w+)]\[qty]/g, "$1");
    const newQty = parseFloat(input.value);

    const data = new FormData();
    data.append("action", "breakdance_cart_update_qty");
    data.append("hash", hash);
    data.append("quantity", newQty);

    const payload = {
      method: "POST",
      credentials: "same-origin",
      body: data
    };

    const { makeAjaxRequest } = window.BreakdanceFrontend.utils;
    await makeAjaxRequest(url, payload);
    $(document.body).trigger("wc_fragment_refresh");
    $(document.body).trigger("wc_update_cart");
  };

  const mounted = () => {
    addEventListener("change", (event) => {
      const isInput = event.target.matches("input.qty");
      if (isInput) updateQty(event.target);
    });

    addEventListener("breakdance_product_qty_update", (event) => {
      updateQty(event.detail);
    });

    $(document.body).on("wc_cart_emptied", () => {
      const containsEl = document.querySelector(".bde-cart-contents");
      if (containsEl) {
        location.reload();
      }
    });
  };

  document.addEventListener("DOMContentLoaded", mounted);
}(jQuery));
