<?php

namespace Breakdance\Fonts;

use Breakdance\Render\ScriptAndStyleHolder;

/**
 * @param  string  $slug
 * @param  string  $cssName
 * @param  string  $label
 * @param  string  $fallbackString
 * @param  ElementDependenciesAndConditions  $dependencies
 *
 * @return BreakdanceFont
 */
function font($slug, $cssName, $label, $fallbackString, $dependencies)
{
    return [
        'slug' => $slug,
        'cssName' => $cssName,
        'label' => $label,
        'fallbackString' => $fallbackString,
        'dependencies' => $dependencies,
    ];
}

/*
The entire public API is just one function to register a font and its
dependencies - dependencies are the CSS/JS necessary for the CSS
font-family: 'Some-Font-Family' to actually work
 */

/**
 * @param  string  $slug
 * @param  string  $cssName
 * @param  string  $label
 * @param  string  $fallbackString
 * @param  ElementDependencyWithoutConditions  $dependencies
 *
 * @return void
 */
function registerFont($slug, $cssName, $label, $fallbackString, $dependencies)
{
    $font = font(
        $slug,
        $cssName,
        $label,
        $fallbackString,
        $dependencies
    );

    /**
     * @var BreakdanceFont
     */
    $font = apply_filters("breakdance_register_font", $font);

    if ($font) {
        FontsController::getInstance()->fonts[$font['slug']] = $font;
    }
}

/**
 * @param  string  $slug
 * @param  string  $cssName
 * @param  string  $label
 * @param  string  $fallbackString
 * @param  ElementDependencyWithoutConditions  $dependencies
 *
 * @return void
 */
function registerFontAtTheStart($slug, $cssName, $label, $fallbackString, $dependencies)
{
    FontsController::getInstance()->fonts = [$slug => font(
            $slug,
            $cssName,
            $label,
            $fallbackString,
            $dependencies
        )] + FontsController::getInstance()->fonts;
}

/* NOT FOR PUBLIC USE, HOLDS THE 'STATE' I.E. WHATEVER FONTS ARE REGISTERED */

class FontsController
{

    use \Breakdance\Singleton;

    /**
     * keyed with slug
     *
     * @var array{slug:string,label:string,cssName:string,fallbackString:string,dependencies:ElementDependencyWithoutConditions}[]
     */
    public $fonts = [];

}

/*
Side-effect the font dependencies into the HTML when rendering
All fonts in Twig CSS templates must be wrapped with process_font function. i.e

font-family: process_font(design.font_family);
 */

\Breakdance\PluginsAPI\PluginsController::getInstance()->registerTwigFunction(
    'process_font',
    'Breakdance\Fonts\process_font',
    <<<JS
    (slug) => {
        const foundFont = window.Breakdance.stores.configStore.fonts.find(font => slug === font.slug);

        if (foundFont) {
            window.Breakdance.canvas.addDependenciesToHead({dependencies: [foundFont.dependencies]});
            return foundFont.fallbackString ? foundFont.cssName + ', ' + foundFont.fallbackString : foundFont.cssName;
        }

        return '';
    }
    JS
    // TODO MAKE THIS FUCKING TYPESCRIPT FOR FUCK SAKE HOW MANY FUCKING HOURS HAVE I SPENT DEBUGGING SHIT
    // WITH THIS SHIT BECAUSE THERE AR NO TYPES????
);

/**
 * @param string $slug
 * @return string
 *
 * https://twig.symfony.com/doc/3.x/advanced.html#filters
 * twig sends the argument to the called function
 */
function process_font($slug)
{
    if ($slug && isset(FontsController::getInstance()->fonts[$slug])) {
        $font = FontsController::getInstance()->fonts[$slug];

        ScriptAndStyleHolder::getInstance()->append($font['dependencies'], true);

        return $font['fallbackString'] ? $font['cssName'] . "," . $font['fallbackString'] : $font['cssName'];
    }

    return ''; // TODO: this isn't the right way to fallback if the font isnt found
}
