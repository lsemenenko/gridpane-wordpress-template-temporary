<?php

namespace Breakdance\Integrations\WPGB;

use function Breakdance\Integrations\getElementSlugsThatCanHaveFiltering;

add_action('breakdance_loaded', function () {
    if (!function_exists('wp_grid_builder')) {
        return;
    }

    /** @var string[] $supportedElements */
    $supportedElements = apply_filters('breakdance_wpgridbuilder_supported_elements', getElementSlugsThatCanHaveFiltering());

    add_filter('breakdance_element_classnames_for_html_class_attribute',
        /**
         * @param string[] $classesArg
         * @param string $elementSlug
         * @param mixed $props
         *
         * @return string[]
         */
        function ($classesArg, $elementSlug, $props) use ($supportedElements) {
        $classes = $classesArg;

        foreach ($supportedElements as $supportedElement) {
            if ($elementSlug === $supportedElement) {
                $classes[] = 'wpgb-content';

                return $classes;
            }
        }

        return $classes;
    },
        10,
        3
    );

    // Enable Wp Grid Builder on all our queries. Since it can't pick up on custom queries.
    // Could this have any side effect? If a non-supported element has "wp_grid_builder" on their query? I don't think so
    // https://docs.wpgridbuilder.com/resources/guide-filter-custom-queries/
    add_filter('breakdance_query_control_query', '\Breakdance\Integrations\WPGB\enableWpGriDBuilderForCustomQueries');
});

/**
 * @param array|string $query
 * @return array|string
 */
function enableWpGriDBuilderForCustomQueries($query)
{
    if (is_string($query)){
        return $query . "&wp_grid_builder=wpgb-content";
    }

    $query['wp_grid_builder'] = 'wpgb-content';

    return $query;
}
