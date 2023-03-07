<?php


namespace Breakdance\Subscription;

class SubscriptionMode
{
    use \Breakdance\Singleton;

    /** @var "free"|"trial"|"pro"  */
    public string $subscriptionMode = "free";


    function __construct() {

        if(validLicenseWasEnteredAtSomePoint()){
            $this->subscriptionMode = 'pro';
            return;
        }

        // if(!isTrialExpired()){
        //     $this->subscriptionMode = 'trial';
        //     return;
        // }

        $this->subscriptionMode = 'free';
    }
}
