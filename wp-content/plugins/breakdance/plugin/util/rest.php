<?php

namespace Breakdance;

/**
 * @param string $url
 * @param string $action
 * @param array $bodyArgs
 * @return array|\WP_Error
 */
function remotePostToWpAjax($url, $action, $bodyArgs = [])
{
    return wp_remote_post($url . '/wp-admin/admin-ajax.php', [
        // PHP max_execution_time default is 30
        'timeout' => 25,
        'body' => array_merge(['action' => $action], $bodyArgs)
    ]);
}
