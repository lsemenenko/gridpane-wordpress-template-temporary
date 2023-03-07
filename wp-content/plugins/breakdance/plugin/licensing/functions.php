<?php

namespace Breakdance\Licensing;

use function Breakdance\Data\get_global_option;
use function Breakdance\Data\set_global_option;
use function Breakdance\Util\Http\http_get_json;

/**
 * @param bool $should_receive
 * @return void
 */
function save_option_receive_beta_updates($should_receive)
{
    set_global_option('receive_beta_updates', (bool)$should_receive);
}

/**
 * @return bool
 */
function get_option_receive_beta_updates()
{
    return (bool)get_global_option('receive_beta_updates');
}

/**
 * @return null|string
 */
function get_stored_license_key()
{
    /**
     * @var false|mixed $license_key
     */
    $license_key = get_global_option('license_key');
    return is_string($license_key) ? $license_key : null;
}

/**
 * @param string|null $license_key
 * @return void
 */
function set_stored_license_key($license_key)
{
    set_global_option('license_key', $license_key);
}


/**
 * @return false|mixed
 */
function activate_stored_license_key_at_edd()
{
    $stored_license_key = get_stored_license_key();

    if ($stored_license_key === null) {
        return false;
    }

    return http_get_json(
        sprintf(
            '%s?edd_action=activate_license&item_id=%s&license=%s&url=%s',
            BREAKDANCE_EDD_STORE_URL,
            BREAKDANCE_EDD_ITEM_ID,
            $stored_license_key,
            site_url()
        ),
        [
            // TODO remove once Breakdance.com becomes available
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('breakdance:cc3b7ab3')
            )
        ]
    );
}

/**
 * @return false|mixed
 */
function deactivate_stored_license_key_at_edd()
{
    $stored_license_key = get_stored_license_key();

    if ($stored_license_key === null) {
        return false;
    }

    return http_get_json(
        sprintf(
            '%s?edd_action=deactivate_license&item_id=%s&license=%s&url=%s',
            BREAKDANCE_EDD_STORE_URL,
            BREAKDANCE_EDD_ITEM_ID,
            $stored_license_key,
            site_url()
        ),
        [
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('breakdance:cc3b7ab3')
            )
        ]
    );
}

/**
 * @param string $license_key
 * @return false|array{expires: string, success: boolean, license: string}
 */
function check_license_key_at_edd_and_upgrade_trial_if_successful($license_key)
{
    /** @var false|array{expires: string, success: boolean, license: string} $response */
    $response = http_get_json(
        sprintf(
            '%s?edd_action=check_license&item_id=%s&license=%s&url=%s',
            BREAKDANCE_EDD_STORE_URL,
            BREAKDANCE_EDD_ITEM_ID,
            $license_key,
            site_url()
        ), [
            // @TODO Remove once Breakdance.com becomes publicly available
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('breakdance:cc3b7ab3')
            )
        ]
    );

    if ($response['success'] ?? false) {
        \Breakdance\Subscription\setValidLicenseWasEnteredAtSomePoint();
    }

    return $response;
}


/**
 * @param array{expires: string, success: boolean, license: string} $key_remote_info
 * @return array{is_valid: string, activation_status: string, expires: string, expires_in_human_readable: string}
 */
function get_human_readable_license_info_by_key_remote_info($key_remote_info)
{
    $activation_status_map = [
        'inactive' => 'Not activated',
        'valid' => 'Active',
        'invalid' => 'Invalid key',
        'disabled' => 'License key revoked',
        'expired' => 'License has expired',
        'key_mismatch' => 'License is not valid for this product',
    ];

    return [
        'is_valid' => ($key_remote_info['success'] ?? false) ? 'Valid' : 'Invalid',
        'activation_status' => isset($key_remote_info['license']) ? $activation_status_map[(string) $key_remote_info['license']] ?? 'Unknown' : 'Unknown',
        'expires' => isset($key_remote_info['expires']) ? (string) mysql2date(
            (string) get_option('date_format'),
            (string) $key_remote_info['expires']
        ) : 'Unknown',
        'expires_in_human_readable' => isset($key_remote_info['expires']) ? ' (in ' . human_time_diff(
                time(),
                strtotime((string) $key_remote_info['expires'])
            ) . ')' : '',
    ];
}
