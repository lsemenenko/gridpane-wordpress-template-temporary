<?php

namespace Breakdance\Licensing;

/**
 * "To support auto updating ... make sure your updater call is loaded on the `init` hook"
 *
 * @see https://docs.easydigitaldownloads.com/article/1096-software-licensing---updater-implementation-for-wordpress-plugins
 */
add_action('init', function () {
    $license_key = get_stored_license_key();
    if ($license_key !== null) {
        /** @psalm-suppress UndefinedConstant */
        $version = (string) __BREAKDANCE_VERSION;

        /** @psalm-suppress UndefinedClass */
        (new EDD_SL_Plugin_Updater(
            BREAKDANCE_EDD_STORE_URL,
            path_join(dirname(__FILE__, 3), 'plugin.php'),
            array(
                'version' => $version,
                'license' => $license_key,
                'item_id' => BREAKDANCE_EDD_ITEM_ID,
                'author' => 'Soflyy',
                // set to true if you wish customers to receive update notifications of beta releases
                'beta' => get_option_receive_beta_updates(),
            )
        ));
    }
});
