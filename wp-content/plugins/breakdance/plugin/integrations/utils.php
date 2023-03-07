<?php

namespace Breakdance\Integrations;

/**
 * @return string[]
 */
function getElementSlugsThatCanHaveFiltering()
{
    return [
        'EssentialElements\\Postslist',
        'EssentialElements\\PostsLoop',
        "EssentialElements\\Wooproductslist",
        "EssentialElements\\Wooshoppage"
    ];
}
