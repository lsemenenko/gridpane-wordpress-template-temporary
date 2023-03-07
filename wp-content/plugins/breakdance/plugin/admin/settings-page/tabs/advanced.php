<?php

namespace Breakdance\Admin\SettingsPage\AdvancedTab;

use function Breakdance\Util\is_post_request;

add_action('breakdance_register_admin_settings_page_register_tabs', '\Breakdance\Admin\SettingsPage\AdvancedTab\register');

function register()
{
    \Breakdance\Admin\SettingsPage\addTab('Advanced', 'advanced', '\Breakdance\Admin\SettingsPage\AdvancedTab\tab', 1200);
}

function tab()
{
    $nonce_action = 'breakdance_admin_advanced_tab';
    if (is_post_request() && check_admin_referer($nonce_action)) {
        if (array_key_exists('enable_simulate_the_content', $_POST)) {
            \Breakdance\Data\set_global_option('breakdance_settings_enable_simulate_the_content', 'yes');
        } else {
            \Breakdance\Data\set_global_option('breakdance_settings_enable_simulate_the_content', false);
        }
    }

    // load data for use in form
    /**
     * @var mixed
     */
    $enable_simulate_the_content = \Breakdance\Data\get_global_option('breakdance_settings_enable_simulate_the_content');

    ?>

    <h2>Advanced</h2>

    <form action="" method="post">
        <?php wp_nonce_field($nonce_action); ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        Apply <code>the_content</code> filter to Breakdance content
                    </th>
                    <td>
                        <fieldset>
                            <label for="enable_simulate_the_content">
                                <input type="checkbox" <?php echo $enable_simulate_the_content ? 'checked' : ''; ?> name="enable_simulate_the_content" value="yes" id="enable_simulate_the_content"> Enable
                            </label>
                        </fieldset>

                        <p class="description">By default, Breakdance does not apply <code>the_content</code> filter to Breakdance-designed content. You can enable this option to make Breakdance run  <code>apply_filters('the_content', ...)</code> on singular content created with Breakdance, but you should understand the <a href='https://breakdance.com/documentation/other/security/'>potential security implications</a>.</p>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
        </p>

    </form>
    <?php
}
