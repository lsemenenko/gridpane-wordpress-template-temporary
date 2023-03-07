<?php

namespace Breakdance\Subscription;

use function Breakdance\BrowseMode\isRequestFromBrowserIframe;
use function Breakdance\DesignLibrary\isRequestFromDesignLibraryModal;
use function Breakdance\isRequestFromBuilderDynamicDataGet;
use function Breakdance\isRequestFromBuilderIframe;
use function Breakdance\isRequestFromBuilderSsr;

/**
 * @return bool
 */
function isTrial() {
    return getSubscriptionMode() === 'trial';
}

/**
 * @return int
 */
function getTrialExpirationTime() {
    $trialExpirationTime = (int) get_option(BREAKDANCE_TRIAL_EXPIRATION_TIME_OPTION_NAME);

    if (!$trialExpirationTime) {
        $trialExpirationTime = time() + (60 * 60 * 24 * BREAKDANCE_TRIAL_LENGTH);
        update_option(BREAKDANCE_TRIAL_EXPIRATION_TIME_OPTION_NAME, $trialExpirationTime);
    }

    return (int) $trialExpirationTime;
}

/**
 * @return boolean
 */
function freeModeOnFrontend()
{
    if (isRequestFromBuilderSsr() || isRequestFromBuilderIframe() || isRequestFromBuilderDynamicDataGet() || isRequestFromDesignLibraryModal() || isRequestFromBrowserIframe()){
        return false;
    }

    return isFreeMode();
}

/**
 * @return bool
 */
function isFreeMode(){
    return getSubscriptionMode() === 'free';
}

/**
 * @return bool
 */
function isTrialExpired() {
    return time() > getTrialExpirationTime();
}

/**
 * @return "pro"|"trial"|"free"
 */
function getSubscriptionMode(){
    return SubscriptionMode::getInstance()->subscriptionMode;
}

/**
 * @return int
 */
function daysLeftOnTrial() {
    $secondsLeftOnTrial = getTrialExpirationTime() - time();

    if ($secondsLeftOnTrial < 0) {
        $daysLeftOnTrial = 0;
    } else {
        $daysLeftOnTrial = floor($secondsLeftOnTrial / (60 * 60 * 24));
    }

    return (int) $daysLeftOnTrial;
}

/**
 * @return void
 */
function setValidLicenseWasEnteredAtSomePoint() {
    update_option(BREAKDANCE_TRIAL_VALID_LICENSE_ENTERED_OPTION_NAME, true);
}

/**
 * @return mixed
 */
function validLicenseWasEnteredAtSomePoint() {
    return get_option(BREAKDANCE_TRIAL_VALID_LICENSE_ENTERED_OPTION_NAME);
}
