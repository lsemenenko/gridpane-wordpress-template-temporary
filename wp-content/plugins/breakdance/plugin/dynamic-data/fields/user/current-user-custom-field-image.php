<?php

namespace Breakdance\DynamicData;

class CurrentUserCustomFieldImage extends ImageField
{

    /**
     * @inheritDoc
     */
    public function label()
    {
        return 'Custom Field';
    }

    /**
     * @inheritDoc
     */
    public function category()
    {
        return 'Current User';
    }

    /**
     * @inheritDoc
     */
    public function slug()
    {
        return 'user_custom_field_image';
    }
    /**
     * @inheritDoc
     */
    public function controls()
    {
        return [
            \Breakdance\Elements\control('key', 'Key', [
                'type' => 'text',
                'layout' => 'vertical',
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function returnTypes()
    {
        return [
            'image_url'
        ];
    }
    /**
     * @inheritDoc
     */
    public function handler($attributes): ImageData
    {
        $userId = get_current_user_id();
        if (empty($attributes['key']) || $userId === 0) {
            return ImageData::emptyImage();
        }


        $url = get_user_meta($userId, $attributes['key'], true);
        $url = strip_shortcodes($url); // Security - strip_shortcodes: https://github.com/soflyy/breakdance/issues/4186
        
        return ImageData::fromUrl($url);
    }
}
