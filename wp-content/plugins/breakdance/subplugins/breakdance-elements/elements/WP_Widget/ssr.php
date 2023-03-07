<?php
/**
 * @var array $propertiesData
 */

use Breakdance\WPWidgets\WidgetsController;

$widget = @$propertiesData['content']['content'];

if (!@$widget['widget']) {
    echo "Please choose a widget from the dropdown.";
}

WidgetsController::getInstance()->render(@$widget['widget'], @$widget[@$widget['widget']]);

?>
