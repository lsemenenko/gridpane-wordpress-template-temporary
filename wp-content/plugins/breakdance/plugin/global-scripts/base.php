<?php

namespace Breakdance\GlobalScripts;

use Breakdance\Render\ScriptAndStyleHolder;
use function Breakdance\Subscription\getSubscriptionMode;

add_action('breakdance_loaded', '\Breakdance\GlobalScripts\enqueue');

function enqueue()
{
    $breakdance = json_encode([
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'ajaxNonce' => \Breakdance\AJAX\get_nonce_for_ajax_requests(),
        'elementsPluginUrl' => defined('BREAKDANCE_ELEMENTS_PLUGIN_URL') ? BREAKDANCE_ELEMENTS_PLUGIN_URL : null,
        'BASE_BREAKPOINT_ID' => BASE_BREAKPOINT_ID,
        'breakpoints' => \Breakdance\Config\Breakpoints\get_breakpoints(),
        'subscriptionMode' => getSubscriptionMode()
    ]);

    $url = BREAKDANCE_PLUGIN_URL . "plugin/global-scripts/breakdance-utils.js";

    $dependencies = [
        'scripts' => [$url],
        'inlineScripts' => [
            <<<JS
                if (!window.BreakdanceFrontend) {
                    window.BreakdanceFrontend = {}
                }

                window.BreakdanceFrontend.data = {$breakdance}
            JS
        ]
    ];

    ScriptAndStyleHolder::getInstance()->append($dependencies);
}


