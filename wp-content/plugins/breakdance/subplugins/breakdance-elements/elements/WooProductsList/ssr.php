<?php
/**
 * @var array $propertiesData
 */

use Breakdance\WooCommerce\WooActions;

WooActions::filterCatalog($propertiesData['design']['elements'])
    ->then(function() use ($propertiesData) {
        $content = $propertiesData['content']['content'];
        $layout = $propertiesData['design']['layout']['layout'];
        $products = \Breakdance\WooCommerce\getProducts($content);

        \Breakdance\WooCommerce\renderProducts($products, [
            'wrapperClass' => $layout === 'slider' ? 'swiper' : null
        ]);
    });



