<?php

namespace Breakdance\DynamicData;

if ( !function_exists( 'rwmb_get_registry' ) ) {
    return; // Bail out if Metabox is not installed.
}

function getFieldsForType($type) {
    $defaultFields = [MetaboxField::class];
    $fieldsMap = [
        'checkbox_list' => [MetaboxCheckboxField::class],
        'url' => [MetaboxField::class, MetaboxImageField::class],
        'single_image' => [MetaboxImageField::class, MetaboxImageUrlField::class],
        'image' => [MetaboxImageField::class, MetaboxGalleryField::class, MetaboxImageUrlField::class],
        'image_advanced' => [MetaboxImageField::class, MetaboxGalleryField::class, MetaboxImageUrlField::class],
        'video' => [MetaboxOembedField::class],
        'map' => [MetaboxMapField::class],
        'post' => [MetaboxPostField::class],
        'oembed' => [MetaboxOembedField::class],
        'taxonomy' => [MetaboxTaxonomyField::class],
        'taxonomy_advanced' => [MetaboxTaxonomyField::class],
        'user' => [MetaboxUserField::class],
        'group' => [MetaboxGroupField::class],
    ];
    return $fieldsMap[$type] ?? $defaultFields;
}

$excludedFieldTypes = [
    'fieldset_text',
    'file_advanced',
    'file_upload',
    'file_input',
    'file',
];

$fields = \Breakdance\DynamicData\get_metabox_fields(null, $excludedFieldTypes);
registerMetaboxFields($fields);

function registerMetaboxFields($fields, $parentField = null) {
    foreach($fields as $field) {
        if ($parentField) {
            $field['group'] = $parentField['name'];
            $field['group_id'] = $parentField['id'];
            $field['root_id'] = $parentField['root_id'] ?? $parentField['id'];
            $field['is_sub_field'] = true;
        }
        $dynamicFields = getFieldsForType($field['type']);
        foreach ($dynamicFields as $dynamicField) {
            if (class_exists($dynamicField)) {
                DynamicDataController::getInstance()->registerField(
                    new $dynamicField($field)
                );
            }
        }
        if ($field['type'] === 'group') {
            registerMetaboxFields($field['fields'], $field);
        }
    }
}

