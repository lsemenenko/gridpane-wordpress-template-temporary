<?php

namespace Breakdance\DynamicData;

class AcfTaxonomyField extends AcfField
{

    public function returnTypes()
    {
        return ['string', 'url'];
    }

    public function handler($attributes): StringData
    {
        if ($this->field['is_sub_field']) {
            $settings = get_sub_field_object($this->field['name']);
        } else {
            $settings = get_field_object($this->field['name']);
        }

        if (empty($settings)) {
            return StringData::emptyString();
        }

        $ids = AcfField::getValue($this->field, false);
        $terms = get_terms($settings['taxonomy'], [
            'include' => $ids
        ]);

        $output = array_map(function($term) {
            return $term->name;
        }, $terms);
        $separator = $attributes['separator'] ?? ', ';
        return StringData::fromString(implode($separator, $output));
    }
}
