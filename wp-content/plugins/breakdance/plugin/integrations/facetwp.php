<?php

namespace Breakdance\Integrations\FacetWp;

use function Breakdance\Integrations\getElementSlugsThatCanHaveFiltering;

add_action('breakdance_loaded', function () {
    if (!function_exists('FWP')) {
        return;
    }

    /** @var string[] $supportedElements */
    $supportedElements = apply_filters('breakdance_facetwp_supported_elements', getElementSlugsThatCanHaveFiltering());

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
                $classes[] = 'facetwp-template';

                return $classes;
            }
        }

        return $classes;
    },
        10,
        3
    );

    // Enable FacetWp on all our queries. Since it can't pick up on custom queries.
    // Could this have any side effect? If a non-supported element has "facetwp" on their query? I don't think so
    // https://facetwp.com/help-center/listing-templates/custom-wp-query/
    add_filter('breakdance_query_control_query', '\Breakdance\Integrations\FacetWp\enableFacetWpForCustomQueries');
});

/**
 * @param array|string $query
 * @return array|string
 */
function enableFacetWpForCustomQueries($query)
{
    if (is_string($query)){
        return $query . "&facetwp=true";
    }

    $query['facetwp'] = true;

    return $query;
}
