<div class='breakdance-woocommerce'>
    <?php
    /**
     * @var array $propertiesData
     */

    use Breakdance\WPWidgets\WidgetsController;

    $widget = @$propertiesData['content']['content'];

    WidgetsController::getInstance()->render(@$widget['widget'], @$widget[@$widget['widget']]);

    ?>
</div>
