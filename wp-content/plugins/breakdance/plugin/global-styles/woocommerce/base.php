<?php

namespace Breakdance\GlobalSettings\WooCommerce;

use function Breakdance\Elements\PresetSections\getPresetSection;

/**
 * @return Control
 * @throws \Exception
 */
function WOO_SECTION()
{
  return getPresetSection("EssentialElements\\WooGlobalStylerOverride", "WooCommerce", "woocommerce", ['type' => 'accordion']);
}

/**
 * @return string
 */
function WOO_TEMPLATE()
{
  return "
      {% set woocommerceEnabled = true %}

      {% if woocommerceEnabled %}
          {{ macros.wooGlobalStyler(settings.woocommerce, null, breakpoint, settings) }}
      {% endif %}
    ";
}
