<?php
/**
 * @var array $propertiesData
 */

$url = @$propertiesData['content']['button']['url_to_like'] && @$propertiesData['content']['button']['custom_url'] ? @$propertiesData['content']['button']['custom_url'] : get_permalink();

echo $url;
