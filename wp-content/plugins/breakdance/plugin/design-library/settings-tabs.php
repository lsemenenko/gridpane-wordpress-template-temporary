<?php

namespace Breakdance\DesignLibrary\Tab;

use function Breakdance\Setup\admin_notice;
use function Breakdance\Subscription\isFreeMode;
use function Breakdance\Util\is_post_request;
use function Breakdance\Data\set_global_option;

use function Breakdance\DesignLibrary\getDesignSets;
use function Breakdance\DesignLibrary\getInvalidDesignSets;
use function Breakdance\DesignLibrary\setDesignSets;

add_action('breakdance_register_admin_settings_page_register_tabs', '\Breakdance\DesignLibrary\Tab\register');

function register()
{
    \Breakdance\Admin\SettingsPage\addTab('Design Library', 'design_library', '\Breakdance\DesignLibrary\Tab\tab', 1200);
}

function submit()
{
    $enabled = array_key_exists('is_copy_from_frontend_enabled', $_POST) ? 'yes' : false;
    $reliesOnGlobalSettings = array_key_exists('design_library_relies_on_global_settings', $_POST) ? 'yes' : false;
    /** @var string */
    $providers = filter_input(INPUT_POST, 'providers', FILTER_UNSAFE_RAW);

    set_global_option('is_copy_from_frontend_enabled', $enabled);
    set_global_option('design_library_relies_on_global_settings', $reliesOnGlobalSettings);

    setDesignSets($providers);

    $invalidSets = getInvalidDesignSets();

    if ($invalidSets) {
        $urls = implode(", ", $invalidSets);
        $message = sprintf("The following URLs are invalid design sets and have been removed: <br><strong>%s</strong>", $urls);
        admin_notice($message, 'error');

        maybeDeleteInvalidSets();
    }
}

/**
 * @return bool
 */
function maybeDeleteInvalidSets()
{
    $sets = getDesignSets();
    $invalidSets = getInvalidDesignSets();

    if (!$invalidSets) return false;

    $pattern = implode('|', array_map(function($elem) {
        return preg_quote($elem, '/');
    }, $invalidSets));

    $invalidSetsRemoved = preg_replace("/{$pattern}/i", '', $sets);

    setDesignSets($invalidSetsRemoved);

    return true;
}

function tab()
{
    $nonce_action = 'breakdance_admin_design_library_tab';

    if (is_post_request() && check_admin_referer($nonce_action)) {
        submit();
    }

    /**
     * @var string
     */
    $copy_from_frontend = \Breakdance\Data\get_global_option('is_copy_from_frontend_enabled');
  /**
   * @var string
   */
    $reliesOnGlobalSettings = \Breakdance\Data\get_global_option('design_library_relies_on_global_settings');
    $providers = implode("\n", getDesignSets());
    ?>

    <h2>Design Library</h2>

    <form action="" method="post">
        <?php wp_nonce_field($nonce_action); ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        Design Set
                    </th>
                    <td>
                        <fieldset>
                            <div>
                              <label for="is_copy_from_frontend_enabled">
                                <input type="checkbox" <?php echo $copy_from_frontend ? 'checked' : ''; ?> name="is_copy_from_frontend_enabled" value="yes" id="is_copy_from_frontend_enabled">
                                Turn This Website Into a Design Set
                              </label>
                            </div>
                            <div>
                              <label for="design_library_relies_on_global_settings">
                                <input type="checkbox" <?php echo $reliesOnGlobalSettings ? 'checked' : ''; ?> name="design_library_relies_on_global_settings" value="yes" id="design_library_relies_on_global_settings">
                                This Design Set Relies On Global Settings
                              </label>
                            </div>
                        </fieldset>
                        <?php if ($copy_from_frontend): ?>
                            <p class="description">
                                Shareable URL: <input class="breakdance-design-library-shareable-url" type="url" value="<?php echo home_url(); ?>" readonly>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="providers">Custom Design Sets</label></th>
                    <td>

                        <fieldset>
                            <p>
                                <textarea class="large-text code" cols="50" id="providers" name="providers" rows="10"><?php echo $providers; ?></textarea>
                            </p>
                        </fieldset>
                        <p class="description">
                            Add custom Design Sets to your Breakdance installation.
                            By default, Breakdance provides a list of official Design Sets, but you can use this field to add any custom design sets you want.
                        </p>
                        <p><strong>Insert one URL per line.</strong></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
        </p>

        <script>
            document.querySelector('.breakdance-design-library-shareable-url')
              ?.addEventListener('focus', (event) => {
                event.currentTarget.select();
              });
        </script>
    </form>
    <?php
}
