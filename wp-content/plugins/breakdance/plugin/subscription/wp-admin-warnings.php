<?php

namespace Breakdance\Subscription;

if (isTrial()) {
  add_action('admin_notices', function () {
    if (\Breakdance\Util\is_post_request()) {
      return;
    }

    if (isTrialExpired()) {
      ?>
      <div class="error notice breakdance-trial-expired-notice">
        <p>
          <b>Your free Breakdance trial has expired, you can continue using the free version with limited
            functionality.</b><br />
        </p>
        <p>
          You can get a license key at <a href='https://breakdance.com/' target="_blank">https://breakdance.com/</a>
        </p>

      </div>
      <?php
    } else {
      ?>
      <div class="notice-success notice breakdance-trial-expiring">
        <p>
          Breakdance - your free trial is valid for <?php echo daysLeftOnTrial(); ?> more days.
          Already purchased Breakdance? <a
            href='<?php echo admin_url("admin.php?page=breakdance_settings&tab=license"); ?>'>Enter your license
            key.</a>
        </p>
      </div>
      <?php
    }
  });
}

if (isFreeMode()) {
  add_action('admin_notices', function () {
    if (filter_input(INPUT_GET, 'breakdance_form_submissions_upgrade_pro', FILTER_SANITIZE_STRING)) {
      showWpAdminUpgradeToProNotice("Please upgrade to Breakdance Pro to export form submissions.", 'forms');
    }
  });

  add_action('admin_notices', function () {
    $isBreakdanceSettingsPage = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING) === 'breakdance_settings';

    if ($isBreakdanceSettingsPage){
      $currentTab = (string)filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_STRING);

      if ($currentTab === 'design_library') {
        showWpAdminUpgradeToProNotice("When the design library comes out of beta, it will only be available in Breakdance Pro.", 'design-library');
      }

      else if ($currentTab === 'permissions') {
        showWpAdminUpgradeToProNotice("Please upgrade to Breakdance Pro to configure permissions.", 'user-access');
      }

      else if ($currentTab === 'tools') {
        showWpAdminUpgradeToProNotice("Please upgrade to Breakdance Pro to import and export settings.", 'import-export');
      }
    }
  });
}


/**
 * @param string $text 
 * @param string $feature
 * @return void 
 */
function showWpAdminUpgradeToProNotice($text, $feature = 'unknown') {
  ?>
  <style>

.breakdance-wp-admin-upgrade-to-pro-notice-button {
  background-color: rgb(255, 197, 20);
  color: black !important;
  border-radius: 4px;
  font-size: var(--text-sm);
  font-weight: 600;
  line-height: 1.2;
  display: flex;
  align-items: center;
  flex-direction: column;
  padding: 12px;
  margin-top: 16px;
  text-decoration: none;
}

.breakdance-wp-admin-upgrade-to-pro-notice {
  background-color: #374151;
  color: #e5e7eb;
  border-radius: 4px;
  font-size: 15px;
  font-weight: 700;
  line-height: 1.2;
  padding: 80px;
  text-align: center;
  margin-left: auto;
  margin-right: auto;
  max-width: 450px;
  margin-top: 40px;
  margin-bottom: 40px;
}


  </style>
  <div class='breakdance-wp-admin-upgrade-to-pro-notice'>
    <?php echo $text; ?>
    <a href='https://breakdance.com/?utm_source=free-version&utm_medium=free-version&utm_campaign=<?php echo $feature; ?>' target='_blank' class='breakdance-wp-admin-upgrade-to-pro-notice-button'>Get Pro</a>
  </div>
  <?php
}