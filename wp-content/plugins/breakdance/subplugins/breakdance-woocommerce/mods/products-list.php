<?php

namespace Breakdance\WooCommerce;

/**
 * Get a value set in the Woo Global Settings or default to a value.
 * @param $prop
 * @param $default
 * @param $breakpoint
 * @return mixed
 */
function getProductListSetting($prop, $default = null, $breakpoint = 'breakpoint_base')
{
    $settings = \Breakdance\Data\get_global_settings_array();
    $globalOptions = $settings['settings']['woocommerce']['other']['products_list'] ?? [];

    return $globalOptions[$prop][$breakpoint] ?? $globalOptions[$prop] ?? $default;
}

/**
 * Change number of products that are displayed per page (shop page)
 * @param int $total
 * @return int
 */
function setProductsPerPage($total)
{
    return getProductListSetting('products_per_page', $total);
}
add_filter('loop_shop_per_page', '\Breakdance\WooCommerce\setProductsPerPage', 999);

/**
 * Change number or products per row
 * @return int
 */
function setProductsPerRow()
{
    return getProductListSetting('products_per_row', 4);
}
add_filter('loop_shop_columns', '\Breakdance\WooCommerce\setProductsPerRow', 999);

/**
 * The elements that will be visible in the shop page by default.
 * @return array
 */
function getDefaultOptions()
{
    return [
        'image' => true,
        'title' => true,
        'price' => true,
        'rating' => false,
        'sale_badge' => true,
        'excerpt' => false,
        'categories' => false,
        'quantity_input' => false,
        'button' => true
    ];
}

/**
 * Turn an array of "enable"|"disable" values into booleans.
 * @param $options
 * @return array
 */
function arrayOfStringToBoolean($options)
{
    $options = array_map(function ($include) {
        if ($include === 'enable') return true;
        if ($include === 'disable') return false;
        return null;
    }, $options);

    return array_filter($options, function ($include) {
        return $include !== null;
    });
}

/**
 * The elements that will be visible according to the Global Settings.
 * Global Settings > WooCommerce > Other > Products List
 * @return array
 */
function getGlobalOptions()
{
    $settings = \Breakdance\Data\get_global_settings_array();
    $globalOptions = $settings['settings']['woocommerce']['other']['products_list'] ?? [];
    return arrayOfStringToBoolean($globalOptions);
}

/**
 * The elements that will be visible according to the Current Element.
 * (E.g, A Shop Page or Products List element).
 * @param $props
 * @return array
 */
function getCustomOptions($props)
{
    if (!$props) return [];

    $formatted = array_map(function ($prop) {
        return $prop['include'];
    }, $props);

    return arrayOfStringToBoolean($formatted);
}

/**
 * Conditionally render parts of products based on defaults, global settings, and the Current Element.
 * (E.g, A Shop Page or Products List element).
 * @param $props
 * @return \array[][]
 */
function getActionsForCatalog($props = [])
{
    $defaultOptions = getDefaultOptions();
    $globalOptions = getGlobalOptions();
    $customOptions = getCustomOptions($props);
    $options = array_merge($defaultOptions, $globalOptions, $customOptions);

    $remove = [];
    $add = [];

    // Changes based on $options
    if (!$options['image']) {
        $remove[] = ['woocommerce_before_shop_loop_item_title', '\Breakdance\WooCommerce\wrapThumbnailInADiv', 10];
        $remove[] = ['woocommerce_before_subcategory_title', 'woocommerce_subcategory_thumbnail', 10]; // Product Cat
    }

    if ($options['excerpt']) {
        $add[] = ['woocommerce_after_shop_loop_item_title', 'woocommerce_template_single_excerpt', 20];
    }

    if ($options['categories']) {
        $add[] = ['woocommerce_before_shop_loop_item_title', '\Breakdance\WooCommerce\showProductCategories', 20];
    }

    if (!$options['title']) {
        $remove[] = ['woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10];
        $remove[] = ['woocommerce_shop_loop_subcategory_title', 'woocommerce_template_loop_category_title', 10];
    }

    if (!$options['price']) {
        $remove[] = ['woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10];
    }

    if (!$options['rating']) {
        $remove[] = ['woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5];
    }

    if (!$options['sale_badge']) {
        $remove[] = ['breakdance_before_shop_loop_item_image', 'woocommerce_show_product_loop_sale_flash', 10];
    }

    if (!$options['quantity_input']) {
        $remove[] = ['breakdance_shop_loop_footer', '\Breakdance\WooCommerce\addQuantityInputToShopLoop', 9];
    }

    if (!$options['button']) {
        $remove[] = ['breakdance_shop_loop_footer', 'woocommerce_template_loop_add_to_cart', 10];
    }

    return [
        'add' => $add,
        'remove' => $remove
    ];
}


