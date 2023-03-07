<?php

namespace Breakdance\DynamicData;

use Breakdance\Themeless\ThemelessController;
use function Breakdance\Themeless\getTemplateById;

/**
 * @param array $arr
 * @param null|string $text
 * @param null|string $value
 * @return DynamicDropdown[]
 */
function format_for_dropdown($arr, $text = '', $value = '')
{
    return array_map(
        /**
         * @param array $field
         * @return DynamicDropdown
         */
        function ($field) use ($value, $text) {
            /** @var string */
            $text = empty($text) ? $field : $field[$text];
            /** @var string */
            $value = empty($value) ? $field : $field[$value];

            return [ 'text' => $text, 'value' => $value ];
        },
        $arr
    );
}

/**
 * Get the post type for the current post or template
 * @return string|null
 */
function get_dynamic_data_post_type()
{
    $id = (int) ($_POST['id'] ?? null);

    $templates = ThemelessController::getInstance()->templates;
    $template = getTemplateById($templates, $id);

    // If we are not in a template we need to discover what is the post type from the actual post.
    if (!$template) {
        return get_post_type($id) ?: null;
    }

    return $template['settings']['type'] ?? null;
}

/**
 * @return DynamicDropdown[]
 */
function get_taxonomies_for_builder_post()
{
    $postType = get_dynamic_data_post_type();

    if (!$postType) {
        return [];
    }

    if (in_array($postType, (array) BREAKDANCE_DYNAMIC_DATA_PREVIEW_POST_TYPES)) {
        // return all taxonomies for global blocks
        $taxonomies = array_values(get_taxonomies());
    } else {
        $taxonomies = get_object_taxonomies($postType);
    }

    return format_for_dropdown($taxonomies);
}

/**
 * Get all ACF Fields
 * @param string|null $type
 * @param array $ignore Field type to ignore
 * @return ACFField[]
 */
function get_acf_fields($type = null, $ignore = [])
{
    $fields = [];

    if (!function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')) {
        return $fields;
    }

    /** @var ACFGroup[] $groups */
    $groups = acf_get_field_groups();

    foreach($groups as $group) {
        /** @var ACFField[] $groupFields */
        $groupFields = acf_get_fields($group);
        $isOptionPage = is_acf_option_field($group);

        foreach ($groupFields as $field) {
            if (in_array($field['type'], $ignore, true)) {
                continue;
            }

            if ($type && ($field['type'] ?? null) !== $type) {
                continue;
            }

            if ($field['type'] === 'repeater') {
                $subFields = get_acf_sub_fields($field);
                foreach($subFields as $subField) {
                    $subField['is_option_page'] = $isOptionPage;
                    $subField['is_sub_field'] = true;
                    $fields[] = $subField;
                }
            }

            $field['group'] = $group['title'];
            $field['group_id'] = $group['ID'];
            $field['is_option_page'] = $isOptionPage;
            $field['is_sub_field'] = false;
            $fields[] = $field;
        }
    }

    return $fields;
}

/**
 * @param ACFField $field
 * @return ACFField[]
 */
function get_acf_sub_fields($field)
{
    if (!array_key_exists('sub_fields', $field)) {
        return [];
    }

    $fields = [];
    $subFields = [];
    foreach($field['sub_fields'] as $subField) {
        /** @var ACFField $subField */
        $subField['group'] = $field['label'];
        $subField['parent_key'] = $field['key'];
        $subField['group_id'] = $field['ID'];
        $subField['is_sub_field'] = true;
        $fields[] = $subField;
        if ($subField['type'] === 'repeater') {
            $subFields[] = get_acf_sub_fields($subField);
        }
    }
    return array_merge($fields, ...$subFields);
}

/**
 * @param ACFGroup $group
 * @return boolean
 */
function is_acf_option_field($group)
{
    foreach ($group['location'] as $location) {
        $hasOption = array_filter($location, function ($l) {
            return $l['param'] === 'options_page';
        });
        if ($hasOption) {
            return true;
        }
    }
    return false;
}

/**
 * Get toolset fields for post type
 * @param string|null $type
 * @param string[] $ignore Field types to ignore
 * @return array
 */
function get_toolset_fields($type = null, $ignore = [])
{
    /**
     * @var array
     * @psalm-suppress UndefinedFunction
     */
    $groups = wpcf_admin_fields_get_groups();
    $fields = [];
    $subFields = [];

    /** @var array $group */
    foreach($groups as $group) {
        if (!isset($group['id'])) {
            continue;
        }

        /**
         * @var array
         * @psalm-suppress UndefinedFunction
         */
        $groupFields = wpcf_admin_fields_get_fields_by_group($group['id']);

        /**
         * @var array
         * @psalm-suppress UndefinedFunction
         */
        $groupPostTypes = wpcf_admin_get_post_types_by_group($group['id']);
        /** @var array|string $field */
        foreach ($groupFields as $field) {
            if (!is_array($field)) {
                // if the field is not an array it is a repeater field which are not currently supported
                continue;
            }
            $field['group'] = (string) ($group['name'] ?? '');
            $field['group_id'] = (string) ($group['slug'] ?? '');
            $field['post_types'] = $groupPostTypes;
            $field['is_sub_field'] = false;
            $fields[] = $field;
        }
    }
    $fields = array_merge($fields, ...$subFields);

    return array_values(
        array_filter(
            $fields,
            /**
             * @param array $field
             * @return bool
             */
            function ($field) use ($ignore, $type) {
                if ($ignore && in_array($field['type'] ?? null, $ignore)) {
                    return false;
                }

                return !$type || ($field['type'] ?? null) === $type;
            }
        )
    );
}

/**
 * @param string[] $additional_formats
 * @return array{text: string, value: string}[]
 */
function get_date_formats($additional_formats = [])
{
    $default_and_additional_formats = array_merge(
        [__('F j, Y'), 'Y-m-d', 'm/d/Y', 'd/m/Y'],
        $additional_formats
    );

    /** @var string[] $date_formats */
    $date_formats = apply_filters('date_formats', $default_and_additional_formats);

    return array_map(function ($dateFormat) {
        return ['text' => (string)wp_date($dateFormat), 'value' => (string)$dateFormat];
    }, array_unique($date_formats));
}

/**
 * @param string[] $additional_formats
 * @return array{text: string, value: string}[]
 */
function get_time_formats($additional_formats = [])
{
    $default_and_additional_formats = array_merge(
        ['g:i a', 'g:i A', 'H:i'],
        $additional_formats
    );

    /** @var string[] $time_formats */
    $time_formats = apply_filters('time_formats', $default_and_additional_formats);

    return array_map(function ($timeFormat) {
        return ['text' => (string)wp_date($timeFormat), 'value' => (string)$timeFormat];
    }, array_unique($time_formats));
}

/**
 * @param string|null $type
 * @param array $ignore
 * @return array
 */
function get_metabox_fields($type = null, $ignore = [])
{
    $fields = [];

    if (!function_exists('rwmb_get_registry')) {
        return $fields;
    }

    /** @var MetaboxRegistry $registry */
    $registry = rwmb_get_registry('meta_box');

    /**
     * @psalm-suppress MixedMethodCall
     * @var MetaboxGroup[] $metaboxes
     */
    $metaboxes = $registry->all();

    foreach($metaboxes as $metabox) {
        foreach ($metabox->fields as $field) {
            if (in_array($field['type'], $ignore, true)) {
                continue;
            }
            if ($type && ($field['type'] ?? null) !== $type) {
                continue;
            }
            $field['group'] = $metabox->title;
            $field['group_id'] = $metabox->id;
            $field['is_sub_field'] = false;
            $fields[] = $field;
        }
    }

    return $fields;
}
