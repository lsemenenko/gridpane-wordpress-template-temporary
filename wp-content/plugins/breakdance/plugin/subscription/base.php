<?php

namespace Breakdance\Subscription;

define('BREAKDANCE_TRIAL_LENGTH', 30.99);
define('BREAKDANCE_TRIAL_EXPIRATION_TIME_OPTION_NAME', 'breakdance_trial_expiration');
define('BREAKDANCE_TRIAL_VALID_LICENSE_ENTERED_OPTION_NAME', 'breakdance_trial_valid_license_key_was_entered_at_some_point');

require_once  __DIR__ . "/subscription-mode.php";
require_once  __DIR__ . "/helpers.php";
require_once  __DIR__ . "/wp-admin-warnings.php";
require_once  __DIR__ . "/free-mode-utils.php";
require_once  __DIR__ . "/forms.php";
require_once  __DIR__ . "/conditions.php";
require_once  __DIR__ . "/filters.php";
require_once  __DIR__ . "/twig.php";
require_once  __DIR__ . "/frontend-upgrade-notices/base.php";

