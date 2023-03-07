<?php
/**
 * @var array $propertiesData
 */
 
if (!function_exists('yoast_breadcrumb') && !function_exists('rank_math_the_breadcrumbs')) {
    echo "<p>Please install Yoast or RankMath to use breadcrumbs.</p>";
} else {
    if ($propertiesData["content"]["breadcrumbs"]["integration"]) {
        if ($propertiesData["content"]["breadcrumbs"]["integration"] == 'yoast' && function_exists('yoast_breadcrumb')) {
            yoast_breadcrumb();
        }

        if ($propertiesData["content"]["breadcrumbs"]["integration"] == 'rankmath' && function_exists('rank_math_the_breadcrumbs')) {
            rank_math_the_breadcrumbs();
        }
    } else {
        echo '<p>Please select a breadcrumbs integration.</p>';
    }
}
