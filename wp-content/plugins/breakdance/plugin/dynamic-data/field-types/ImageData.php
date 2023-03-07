<?php

namespace Breakdance\DynamicData;

class ImageData extends FieldData
{

    /**
     * The Image URL
     *
     * @var string
     */
    public string $url = '';

    /**
     * An array of image sizes
     * Keys are size slugs, each value is an array containing 'file', 'width', 'height', and 'mime-type'.
     *
     * @var array
     */
    public array $sizes = [];

    /**
     * The image alt text
     *
     * @var string
     */
    public string $alt = '';

    /**
     * The image caption text
     *
     * @var string
     */
    public string $caption = '';

    /**
     * The attachment type
     *
     * @var string
     */
    public string $type = '';


    /**
     * @param mixed $attributes
     * @return array
     */
    public function getValue($attributes = []): array
    {
        if (empty($this->url) && (is_array($attributes) && isset($attributes['fallback_image']))) {
            $attachmentId = (string) $attributes['fallback_image'];
            return self::fromAttachmentId($attachmentId)->getValue();
        }
        return [
            'type' => $this->type,
            'alt' => $this->alt,
            'caption' => $this->caption,
            'url' => $this->url,
            'sizes' => $this->sizes
        ];
    }

    public function hasValue()
    {
        return !empty($this->url);
    }

    /**
     * @param string $attachmentId
     * @return self
     */
    public static function fromAttachmentId($attachmentId): self
    {
        $attachmentData = \Breakdance\Media\Metadata\prepareMedia((int) $attachmentId);

        if (!$attachmentData) {
            return self::emptyImage();
        }

        $imageData = new static();
        $imageData->alt = (string) ($attachmentData['alt'] ?? '');
        $imageData->caption = (string) ($attachmentData['caption'] ?? '');
        $imageData->url = (string) ($attachmentData['url'] ?? '');
        $imageData->type = (string) ($attachmentData['type'] ?? '');
        $imageData->sizes = (array) ($attachmentData['sizes'] ?? []);

        return $imageData;
    }

    /**
     * @param string $url
     * @return self
     */
    public static function fromUrl($url): self
    {
        $imageData = new static();
        $imageData->url = $url;
        $imageData->type = 'external_image';

        return $imageData;
    }

    public static function emptyImage(): self
    {
        return new self();
    }
}
