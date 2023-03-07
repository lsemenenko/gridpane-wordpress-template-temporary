<?php

namespace Breakdance\DynamicData;

class AuthorCustomFieldImage extends ImageField
{

    /**
     * @inheritDoc
     */
    public function label()
    {
        return 'Author Custom Field';
    }

    /**
     * @inheritDoc
     */
    public function category()
    {
        return 'Author';
    }

    /**
     * @inheritDoc
     */
    public function slug()
    {
        return 'author_custom_field_image';
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
        global $post;

        if (empty($attributes['key']) || !$post) {
            return ImageData::emptyImage();
        }

        $author_id = $post->post_author;
        $url = get_user_meta($author_id, $attributes['key'], true);
        return ImageData::fromUrl($url);
    }
}
