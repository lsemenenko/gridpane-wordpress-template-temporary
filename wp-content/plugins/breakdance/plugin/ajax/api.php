<?php

namespace Breakdance\AJAX;

use Exception;

use function Breakdance\Admin\is_breakdance_development_environment;

/**
 * @param string $route
 * @param callable $callback
 * @param string $minimum_permissions
 * @param bool $any_url
 * @param RegisterAjaxHandlerOptions $options
 * @return void
 * @throws Exception
 *
 * In case of renaming/moving to another namespace don't forget to adjust the readme.md file
 */
function register_handler($route, callable $callback, $minimum_permissions = 'never', $any_url = false, $options = [])
{

    HandlerNamesHolder::getInstance()->add($route);

    if ($minimum_permissions === 'never') {
        // bad request
        throw new Exception('You must specify the minimum permissions to access your AJAX action.', 500);
    }

    $action_name = 'wp_ajax_' . $route;
    $any_action_name = 'breakdance_ajax_' . $route;

    $handler = function () use ($callback, $any_url, $minimum_permissions, $route, $options) {
        try {
            ob_start();

            $isHandlerForRemoteRequest = !empty($options['remote']) && $options['remote'];

            $nonceTick = check_ajax_referer(get_nonce_key_for_ajax_requests(), false, !$isHandlerForRemoteRequest);

            if (!$isHandlerForRemoteRequest && !$nonceTick) {
                throw new BreakdanceAjaxHandlerException(
                    "Failed security check", 403
                );
            }

            $allowed = $isHandlerForRemoteRequest ? true : \Breakdance\Permissions\hasMinimumPermission($minimum_permissions);

            if (!$allowed) {
                if (!is_user_logged_in()) {
                    throw new BreakdanceAjaxHandlerException(
                        "You need to be logged in and have minimum permission level of \"{$minimum_permissions}\"", 401
                    );
                }

                // forbidden request
                $error = sprintf(
                    "Not enough permissions to perform the action \"%s\", minimum permission level is \"%s\".",
                    $route,
                    $minimum_permissions
                );
                throw new BreakdanceAjaxHandlerException($error, 403);
            }


            $callback_args = [];
            if (!empty($options['args'])) {
                /** @var null|false|array<string, string|int|null|bool|array> $callback_args_filtered */
                $callback_args_filtered = filter_input_array(INPUT_POST, $options['args'], true);

                if ($callback_args_filtered === false || $callback_args_filtered === null) {
                    $error_message = !is_breakdance_development_environment()
                        ? 'Bad request'
                        : 'All of the required POST parameters are missing';

                    throw new BreakdanceAjaxHandlerException($error_message, 400);
                }

                $erroneous_arg_names = [];
                foreach ($callback_args_filtered as $arg_name => $arg_value) {
                    $is_optional_arg =
                        isset($options['optional_args']) &&
                        in_array($arg_name, $options['optional_args']);

                    if ($arg_value === false || ($arg_value === null && !$is_optional_arg)) {
                        $erroneous_arg_names[] = $arg_name;
                    }
                }

                if (sizeof($erroneous_arg_names)) {
                    $error_message = !is_breakdance_development_environment()
                        ? 'Bad request'
                        : sprintf(
                            'Required POST parameters are missing or invalid: "%s"',
                            implode('", "', $erroneous_arg_names)
                        );
                    throw new BreakdanceAjaxHandlerException($error_message, 400);
                }

                $callback_args = $callback_args_filtered;
            }

            try {
                /** @var mixed $return_value */
                /** @psalm-suppress MixedAssignment */
                $return_value = call_user_func_array($callback, array_values($callback_args));
            } catch (Exception $callback_exec_exception) {
                throw new BreakdanceAjaxHandlerException($callback_exec_exception->getMessage(), 500, $callback_exec_exception);
            }

            if (is_array($return_value) && array_key_exists('error', $return_value)) {
                throw new BreakdanceAjaxHandlerException((string) $return_value['error'], 500);
            } elseif (!is_array($return_value) && !$return_value) {
                $return_value = (object) [];
            } elseif (!is_array($return_value)) {
                $error = sprintf(
                    "AJAX callbacks should return an associative array ([\"key\" => \"value\") or a blank array ([]). Got %s for route \"%s\"",
                    print_r($return_value, true),
                    $route
                );

                throw new BreakdanceAjaxHandlerException($error, 500);
            }

            $unexpected_output = ob_get_clean();

            if ($unexpected_output) {
                $error = 'Unexpected output during AJAX request. AJAX Callbacks should return an associative array. --------- ' . wp_strip_all_tags(
                        $unexpected_output
                    );
                // bad request
                throw new BreakdanceAjaxHandlerException($error, 500);
            } else {
                if ($nonceTick === 2) {
                    $refreshNonce = get_nonce_for_ajax_requests();
                    header('Breakdance-Refresh-Nonce:' . $refreshNonce);
                }
                wp_send_json($return_value, 200);
            }
        } catch (BreakdanceAjaxHandlerException $e) {
            if (ob_get_level()) {
                ob_end_clean();
            }

            throw $e;
        }
    };

    add_action($action_name, $handler);
    add_action('wp_ajax_nopriv_' . $route, $handler);

    if ($any_url) {
        add_action($any_action_name, $handler);
    }
}


/**
 * @return string
 */
function get_nonce_key_for_ajax_requests() {
    return 'breakdance_ajax';
}

/**
 * @return string
 */
function get_nonce_for_ajax_requests() {
    return wp_create_nonce(get_nonce_key_for_ajax_requests());
}


class BreakdanceAjaxHandlerException extends \Exception
{
}

class HandlerNamesHolder
{

    use \Breakdance\Singleton;

    /**
     * @var string[]
     */
    public $handlerNames = [];

    /**
     * @param string $name
     * @return void
     */
    public function add($name)
    {
        $this->handlerNames[] = $name;
    }

}
