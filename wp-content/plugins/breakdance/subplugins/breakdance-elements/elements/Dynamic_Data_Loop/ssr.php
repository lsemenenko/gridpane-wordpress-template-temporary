<?php

/**
 * @var array $propertiesData
 */
$fieldSlug = $propertiesData['content']['field']['repeater_field'];
$blockId = $propertiesData['content']['repeated_block']['global_block'];
$postTag = $propertiesData['content']['repeated_block']['tag'] ?? 'article';

/** @var \Breakdance\DynamicData\RepeaterField $field */
$field = \Breakdance\DynamicData\DynamicDataController::getInstance()->getField($fieldSlug);

$layout = (string) ($propertiesData['design']['list']['layout'] ?: '');
if ($layout == "list") {
    $wrapperClass = 'bde-dynamic-repeater-list';
} else if ($layout == "slider") {
    $wrapperClass = 'swiper-wrapper';
} else {
    $wrapperClass = 'bde-dynamic-repeater-grid';
}

if ($field) {
    $postId = null;
    $isOptionPage = $field->field['is_option_page'] ?? false;
    if ($isOptionPage) {
        $postId = 'option';
    }
    echo '<div class="ee-posts bde-dynamic-repeater bde-dynamic-repeater-%%ID%% ' . $wrapperClass . '">';
    while ($field->hasSubFields($postId)) {
        echo $layout == 'slider' ? '<div class="swiper-slide">' : '';
        echo '<' . $postTag . ' class="ee-post bde-dynamic-repeater-item">';
        echo \Breakdance\Render\render($blockId);
        echo '</' . $postTag . '>';
        echo $layout == 'slider' ? '</div>' : '';
    }
    echo '</div>';
}
