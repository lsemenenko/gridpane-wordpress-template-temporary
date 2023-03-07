<?php

namespace Breakdance\Elements\PresetSections;

add_action('init', function () {
    /**
     * @var Control[]
     * @psalm-suppress UndefinedClass
     */
    $sliderOptionsDesignControls = \EssentialElements\Slideroptionspreset::designControls();

    if (!$sliderOptionsDesignControls || !isset($sliderOptionsDesignControls[0]) || !$sliderOptionsDesignControls[0]) return;

    PresetSectionsController::getInstance()->register(
        "EssentialElements\\AtomV1SwiperSettings",
        $sliderOptionsDesignControls[0],
        true
    );
});
