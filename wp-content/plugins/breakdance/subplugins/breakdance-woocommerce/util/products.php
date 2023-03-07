<?php

namespace Breakdance\WooCommerce;

use function Breakdance\WpQueryControl\getWpQueryArgumentsFromWpQueryControlProperties;

/**
 * @param $props
 * @return array|\stdClass
 */
function getProducts($props)
{
    $defaultLimit = apply_filters('loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page());
    $limit = $props['product_count'] ?? $defaultLimit;
    $type = $props['show_products'];

    $args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'order' => $props['order'],
        'posts_per_page' => $limit
    ];

    if ($props['order_by'] === 'price') {
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = '_price';
    } else {
        $args['orderby'] = $props['order_by'];
    }

    if ($type === 'query') {
        $argsFromInput = getWpQueryArgumentsFromWpQueryControlProperties($props['query']);
        $wpArgs = is_string($argsFromInput) ? wp_parse_args($argsFromInput) : $argsFromInput;
        $args = array_merge($args, $wpArgs);
    } elseif ($type === 'manually') {
        $args['post__in'] = $props['products'];
    } elseif ($type === 'featured') {
        $tax_query[] = [
            'taxonomy' => 'product_visibility',
            'field'    => 'name',
            'terms'    => 'featured',
            'operator' => 'IN',
        ];

        $args['tax_query'] = $tax_query;
    } elseif ($type === 'sale') {
        $args['post__in'] = wc_get_product_ids_on_sale();
    }

    return new \WP_Query($args);
}

/**
 * @param \WP_Query $products
 * @param array $attributes
 */
function renderProducts($query, $attributes = [])
{
    $classes = array_filter(['woocommerce', $attributes['wrapperClass']]);

    ob_start();

    wc_setup_loop([
        'name'         => 'products',
        'is_shortcode' => false,
        'is_search'    => false,
        'is_paginated' => false,
        'total'        => $query->found_posts,
        'total_pages'  => 1,
        'per_page'     => $query->found_posts,
        'current_page' => 1,
    ]);

    woocommerce_product_loop_start();

    if (!$query->have_posts()) {
        echo 'No products found.';
    }

    if ( wc_get_loop_prop( 'total' ) ) {
        if ( $query->have_posts() ) {

            // Start looping over the query results.
            while ( $query->have_posts() ) {
                $query->the_post();

                // Render product template.
                wc_get_template_part( 'content', 'product' );
            }

        }
    }

    woocommerce_product_loop_end();

    wp_reset_postdata();
    wc_reset_loop();

    echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">' . ob_get_clean() . '</div>';
}

/**
 * @param int $productId
 * @param callable $callback
 * @return void
 */
function renderProductPart($productId, $callback)
{
    global $post;

    $originalPost = $post;

    $post = get_post($productId);
    setup_postdata($post);

    global $product;
    $product = wc_get_product($post);

    if ($product) {
        if (is_callable($callback)) {
            $output = $callback($product);
        }

        echo $output;
    } else {
        echo '';
    }

    $post = $originalPost;
    setup_postdata($post);
}
