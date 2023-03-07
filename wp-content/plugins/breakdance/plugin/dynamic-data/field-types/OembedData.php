<?php

namespace Breakdance\DynamicData;

class OembedData extends FieldData {

    /**
     * The Video Title
     *
     * @var string
     */
    public string $title = '';

    /**
     * The Oembed Provider (optional)
     *
     * @var string
     */
    public string $provider = '';

    /**
     * The Video URL
     *
     * @var string
     */
    public string $url = '';

    /**
     * The Oembed URL
     *
     * @var string
     */
    public string $embedUrl = '';

    /**
     * Video thumbnail image
     *
     * @var string
     */
    public string $thumbnail = '';

    /**
     * The Video format
     *
     * @var string
     */
    public string $format = '';

    /**
     * The Video Type 'oembed' or 'video'
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
        if (empty($this->url) && is_array($attributes) && isset($attributes['fallback_video'])) {
            $videoData = wp_prepare_attachment_for_js((int) $attributes['fallback_video']);
            if (!empty($videoData) && isset($videoData['url'])){
                return [
                    "embedUrl" => $videoData['url'],
                    "format" => $videoData['subtype'] ?? null,
                    "mime" => $videoData['mime'] ?? null,
                    "type" => $videoData['type'] ?? 'video',
                    "url" => $videoData['url'],
                ];
            }
        }
        return [
            'title'     => $this->title,
            'provider'  => $this->provider,
            'url'       => $this->url,
            'embedUrl'  => $this->embedUrl,
            'thumbnail' => $this->thumbnail,
            'format'    => $this->format,
            'type'      => $this->type,
        ];
    }

    /**
     * @return bool
     */
    public function hasValue()
    {
        return !empty($this->url);
    }

    /**
     * @param string $url
     * @return OembedData
     */
    public static function fromOembedUrl($url): self
    {
        $oembedValue = \Breakdance\OEmbed\retrieveOEmbed($url);
        if (!$oembedValue) {
            return self::emptyOembed();
        }
        $oembedData = new self;
        $oembedData->title = (string) ($oembedValue['title'] ?? '');
        $oembedData->provider = (string) ($oembedValue['provider'] ?? '');
        $oembedData->url = (string) ($oembedValue['url'] ?? '');
        $oembedData->embedUrl = (string) ($oembedValue['embedUrl'] ?? '');
        $oembedData->thumbnail = (string) ($oembedValue['thumbnail'] ?? '');
        $oembedData->format = (string) ($oembedValue['format'] ?? '');
        $oembedData->type = 'oembed';
        return $oembedData;
    }

    public static function emptyOembed(): self
    {
        $oembedData = new self;
        $oembedData->title = '';
        $oembedData->provider = '';
        $oembedData->url = '';
        $oembedData->embedUrl = '';
        $oembedData->thumbnail = '';
        $oembedData->format = '';
        return $oembedData;
    }
}
