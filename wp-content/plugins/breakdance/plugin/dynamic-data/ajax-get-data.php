<?php

namespace Breakdance\DynamicData;

add_action('breakdance_loaded', function () {
    \Breakdance\AJAX\register_handler(
        'breakdance_dynamic_data_get',
        '\Breakdance\DynamicData\getData',
        'edit',
        true,
        ['args' => [
            'shortcodes' => ['filter' => FILTER_DEFAULT, 'flags' => FILTER_REQUIRE_ARRAY],
        ]]
    );

    \Breakdance\AJAX\register_handler(
        'breakdance_fetch_dynamic_repeater_fields',
        '\Breakdance\DynamicData\getDynamicRepeaterFields',
        'edit',
        true,
        []
    );
});

/**
 * @param string[] $shortcodes
 * @param string|null $repeaterField
 * @return array{data:array|object}
 * @throws \Exception
 */
function getData($shortcodes)
{
    if (!count($shortcodes)) {
        return ['data' => (object) []];
    }

    $result = [];
    foreach ($shortcodes as $shortcode) {
        $cleanShortcode = stripslashes($shortcode);
        /**
         * @psalm-suppress MixedAssignment
         */
        $result[$cleanShortcode] = breakdanceDoShortcode($cleanShortcode);
    }

    return ['data' => $result];
}

/**
 * @return array{text: mixed, value: mixed}[]
 */
function getDynamicRepeaterFields()
{
    $fields = DynamicDataController::getInstance()->getFieldsByReturnType('repeater');

    return array_values(array_map(static function($field) {
        return [
            'text' => $field->label(),
            'value' => $field->slug()
        ];
    }, $fields));
}
