<?php

namespace Breakdance\CustomFonts;

use function Breakdance\Preferences\get_preferences;

add_action('init', '\Breakdance\CustomFonts\loadCustomFonts');

function loadCustomFonts()
{

    // By adding the fonts to the top of the array with registerFontAtTheStar
    // They end up 'last in array' as 'first in font list'
    // So we reverse the custom fonts to have them as newest-to-oldest instead
    $fonts = array_reverse(get_preferences()['customFonts']);

    foreach ($fonts as $font) {
        // Add the custom fonts at the start of the list
        \Breakdance\Fonts\registerFontAtTheStart(
            $font['id'],
            addQuotesToCssNameIfNecessary($font['cssName']),
            $font['family'],
            $font['fallbackString'],
            [
                'styles' => [$font['cssUrl']]
            ]
        );

    }

}
