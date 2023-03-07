<?php

namespace Breakdance\Setup;

use function Breakdance\Filesystem\try_to_create_all_required_directories;

/**
 * @psalm-suppress UndefinedConstant
 * @psalm-suppress MixedArgument
 */

register_activation_hook(__BREAKDANCE_PLUGIN_FILE__, '\Breakdance\Setup\pluginActivated');

function install()
{
    \Breakdance\Data\set_global_option('icons', get_stock_icons());
    \Breakdance\Themeless\Fallbacks\set_fallback_defaults_from_filesystem();
}

function pluginActivated()
{

    $optionName = 'plugin_has_already_been_activated';

    $plugin_has_already_been_activated = (string) \Breakdance\Data\get_global_option($optionName);

    if ($plugin_has_already_been_activated === 'yes') {
        return;
    }

    install();
    try_to_create_all_required_directories();
    register_custom_post_types();
    flush_rewrite_rules();

    \Breakdance\Data\set_global_option($optionName, 'yes');

    add_action('activated_plugin',
        /**
         * @param string $plugin
         */
        function($plugin) {

            /**
             * @psalm-suppress UndefinedConstant
             * @var string $pluginFile
             */
            $pluginFile = __BREAKDANCE_PLUGIN_FILE__;
            if ($plugin == plugin_basename($pluginFile)) {
                exit(wp_redirect(admin_url("admin.php?page=breakdance_setup_wizard")));
            }    
        }
    );
}
