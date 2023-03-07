<?php

namespace Breakdance\Animations\Sticky;

add_filter('breakdance_element_dependencies', '\Breakdance\Animations\Sticky\addDependencies', 100, 1);

/**
 * @param ElementDependenciesAndConditions[] $deps
 * @return ElementDependenciesAndConditions[]
 */
function addDependencies($deps)
{
    $condition = "
        {% set position = settings.animations.sticky.position %}
        return {{ position and position != 'none' ? 'true' : 'false' }};
    ";

    $deps[] = [
        "frontendCondition" => $condition,
        "builderCondition"  => $condition,
        "scripts" => [
            "%%BREAKDANCE_ELEMENTS_PLUGIN_URL%%dependencies-files/gsap@3.8.0/gsap.min.js",
            "%%BREAKDANCE_ELEMENTS_PLUGIN_URL%%dependencies-files/gsap@3.8.0/ScrollTrigger.min.js",
        ]
    ];

    $deps[] = [
        "frontendCondition" => $condition,
        "builderCondition"  => $condition,
        "scripts" => [
            BREAKDANCE_PLUGIN_URL . 'plugin/animations/sticky/js/sticky.js',
        ],
    ];

    $deps[] = [
        "frontendCondition" => $condition,
        "builderCondition" => "return false;",
        "inlineScripts" => [
            "new BreakdanceSticky(
              '%%SELECTOR%%',
              {{ settings.animations.sticky|json_encode }}
            )"
        ],
    ];

    return $deps;
}
