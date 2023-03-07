<?php

namespace Breakdance\DynamicData;

class Shortcode extends StringField
{

    /**
     * @inheritDoc
     */
    public function label()
    {
        return 'Shortcode';
    }

    /**
     * @inheritDoc
     */
    public function category()
    {
        return 'Utility';
    }

    /**
     * @inheritDoc
     */
    public function slug()
    {
        return 'shortcode';
    }

    /**
     * @inheritDoc
     */
    public function controls()
    {
        return [
            \Breakdance\Elements\control('shortcode', 'Shortcode', [
                'type' => 'text',
                'layout' => 'vertical',
                'textOptions' => ['multiline' => true],
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function handler($attributes): StringData
    {
        if (!array_key_exists('shortcode', $attributes)) {
            return StringData::emptyString();
        }
        $output = do_shortcode($attributes['shortcode']);
        return StringData::fromString($output);
    }
}
