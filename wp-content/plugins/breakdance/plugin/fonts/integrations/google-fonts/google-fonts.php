<?php

namespace Breakdance\GoogleFontsPlugin;

/*
do operations like this really have to happen for every request?
should it really be on init? or instead, maybe it sould be on some sort
of setup hook fired by breakdance before rendering... or when its a builder AJAX
request that needs these fonts?
 */

add_action('init', '\Breakdance\GoogleFontsPlugin\loadGoogleFonts');

function loadGoogleFonts()
{
    $fonts = getFontListFromFile();

    foreach ($fonts as $font) {

        $slug = slugFromFontFamilyName($font['family']);
        $cssName = '"' . $font['family'] . '"';
        $dropdownLabel = $font['family'];
        $dependencies = [
            'googleFonts' => [$font['family']],
        ];


        $fallbackString = "";

        if ($font['category'] === 'serif') {
            $fallbackString = 'serif';
        } else if ($font['category'] === 'sans-serif') {
            $fallbackString = 'sans-serif';
        } else if ($font['category'] === 'display') {
            $fallbackString = 'sans-serif';
        } else {
            $fallbackString = 'sans-serif';
        }

        \Breakdance\Fonts\registerFont(
            $slug,
            $cssName,
            $dropdownLabel,
            $fallbackString,
            $dependencies,
        );

    }

}

/**
 * @return array{family:string,category:string}[]
 */
function getFontListFromFile()
{
    if (!is_readable(\Breakdance\Fonts\Consts::GOOGLE_FONT_FILE)) {
        return [];
    }
    $fileContents = file_get_contents(\Breakdance\Fonts\Consts::GOOGLE_FONT_FILE);
    if ($fileContents === false) {
        return [];
    }

    /**
     * @psalm-suppress MixedAssignment
     * @var array{family:string,category:string}[]
     */
    $validated_google_font_data = json_decode($fileContents, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }

    return $validated_google_font_data;
}

/**
 * @param string $fontFamily
 * @return string
 */
function slugFromFontFamilyName($fontFamily)
{
    return "gfont-" . strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $fontFamily));
}

