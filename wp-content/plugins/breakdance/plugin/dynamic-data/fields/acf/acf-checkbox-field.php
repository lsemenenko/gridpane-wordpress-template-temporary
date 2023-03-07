<?php

namespace Breakdance\DynamicData;


class AcfCheckboxField extends AcfField
{
    /**
     * @inheritDoc
     */
    public function controls()
    {
        return [
            \Breakdance\Elements\control(
                'separator',
                'Separator',
                ['type' => 'text']
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public function handler($attributes): StringData
    {
        $maybeArrayOfSelectedValues = AcfField::getValue($this->field, false);
        $separator = $attributes['separator'] ?? ', ';
        if (!is_array($maybeArrayOfSelectedValues)) {
            return StringData::fromString((string) $maybeArrayOfSelectedValues);
        }
        $value = implode($separator, $maybeArrayOfSelectedValues);
        return StringData::fromString($value);
    }
}
