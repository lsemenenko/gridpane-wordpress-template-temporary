<?php
/**
 * @var array $propertiesData
 */

$blockId = @$propertiesData['content']['content']['block'];

if ($blockId) {
    echo \Breakdance\Render\render($blockId);
} else {
    if ($_REQUEST['triggeringDocument'] ?? true) {
        echo '<div class="breakdance-empty-ssr-message">Choose a Global Block from the dropdown.</div>';
    } else {
        echo "<!-- Breakdance error: $blockId not found -->";
    }
}