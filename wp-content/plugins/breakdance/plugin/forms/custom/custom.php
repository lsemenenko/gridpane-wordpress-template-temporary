<?php

namespace Breakdance\Forms;

use Breakdance\Forms\Actions\Action;
use Breakdance\Forms\Actions\ActionProvider;
use function Breakdance\Forms\Render\getFieldAttributes;

/**
 * @param int $postId
 * @param int $formId
 * @param FormUserSubmittedContents $fields
 * @return FormSuccess|FormError|FormError[]
 */
function handleSubmission($postId, $formId, $fields)
{
    $settings = getFormSettings($postId, $formId);

    if (!$settings) {
        return [
            'type' => 'error',
            'message' => 'An unexpected error occurred.'
        ];
    }

    $actions = getFormActions($settings);
    $form    = getFormData($settings['form']['fields'], $fields);
    $successMessage = $settings['form']['success_message'];
    $errorMessage = $settings['form']['error_message'];

    // Metadata
    $ip        = (string) ($_SERVER['REMOTE_ADDR'] ?? null);
    $referer   = wp_get_referer();
    $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? null);
    $userId = get_current_user_id();

    // User access
    $breakdancePermissions = \Breakdance\Permissions\getUserPermission();
    $hasFullAccess = $breakdancePermissions && $breakdancePermissions['slug'] === 'full';

    // reCAPTCHA v3 challenge
    $recaptchaEnabled = $settings['advanced']['recaptcha']['enabled'] ?? false;
    if ($recaptchaEnabled) {
        $token   = (string) ($_POST['recaptcha_token'] ?? null);
        if (!$token) {
            return [
                'type' => 'error',
                'message' => $hasFullAccess ? '[reCAPTCHA] Error retrieving token.' : $errorMessage
            ];
        }
        $verified = \Breakdance\Forms\Recaptcha\verify($token, $ip, 'breakdance_submit', $settings['advanced']['recaptcha']['api_key_input']['apiKey']);

        if (!$verified) {
            return [
                'type' => 'error',
                'message' => $hasFullAccess ? '[reCAPTCHA] Invalid challenge. Please reload and try again.' : $errorMessage
            ];
        }
    }

    // Validate honeypot field
    $honeypotEnabled = $settings['advanced']['honeypot_enabled'] ?? false;
    if ($honeypotEnabled) {
        if (array_key_exists('hpname', $fields) && !empty($fields['hpname'])) {
            // failed the honeypot test. Ignore this
            // submission but return a success response
            return [
                'type' => 'success',
                'message' => $successMessage,
            ];
        }
    }

    // Upload files if any is present. Runs after the recaptcha.
    // We don't want to allow spam files in our server.
    /*
    SECURITY
    https://github.com/soflyy/breakdance/issues/4181
    Disable file uploads entirely
    we will add security for file uploads
    when we add a file upload field to the form
    */
    $files = []; // DELETE THIS LINE ONCE WE HAVE SECURE FILE UPLOAD HANDLING
    // $files = handleUploadedFiles(); - PUT THIS LINE BACK ONCE WE HAVE SECURE FILE UPLOAD HANDLING

    // Validate form. Validators available: Required, email, and files.
    $validation = validateFormData($form, $files);

    if (is_wp_error($validation)) {
        /** @psalm-suppress PossiblyInvalidMethodCall */
        return [
            'type' => 'error',
            'message' => $validation->get_error_message()
        ];
    }

    // Run all actions set for this form
    /** @var FormExtra $extra */
    $extra = [
        'formId'  => $formId,
        'postId'  => $postId,
        'fields'   => $fields,
        'files'    => $files,
        'ip'      => $ip,
        'referer' => $referer,
        'userAgent' => $userAgent,
        'userId' => $userId,
    ];

    $response = executeActions($actions, [$form, $settings, $extra]);
    /** @var int|null $storedId */
    $storedId = $response['store_submission']['id'] ?? null;

    if ($storedId) {
        unset($response['store_submission']); // We don't care for the 'store submission' action.
        \Breakdance\Forms\Submission\saveActionsLog($storedId, $response);
    }

    // Remove files after the form is submitted.
    cleanUpFiles($files);

    // Remove context data from the request response
    // we don't want to return this to the user
    foreach ($response as &$responseItem) {
        unset($responseItem['context']);
    }
    unset($responseItem);

    // Show errors if any is present and user is admin
    $actionsErrors = getActionsErrors($response);
    if (!empty($actionsErrors)) {
        if ($hasFullAccess) {
            // Show action errors if user is admin
            return $actionsErrors;
        }
        return [
            'type' => 'error',
            'message' => [$errorMessage]
        ];
    }

    // Otherwise return a success message.
    return [
        'type' => 'success',
        'message' => $successMessage,
    ];
}

/**
 * @param array|string $x
 * @return mixed
 */
function recursive_strip_tags($x) {
    if (is_array($x)) {
        return array_map('\Breakdance\Forms\recursive_strip_tags', $x);
    } else {
        return strip_tags($x);
    }
}


/**
 * Run a give list of actions
 * @param Action[] $actions
 * @param array $args
 * @return array<string, ActionSuccess|ActionError>
 */
function executeActions($actions, $args)
{
    $responses = [];
    foreach ($actions as $action) {
        /** @var ActionSuccess|ActionError $output */
        $output = call_user_func_array([$action, 'run'], $args);
        $output['context'] = $action->getContext();
        $output['executed_at'] = current_time( \DateTimeInterface::ATOM );
        /** @psalm-suppress MixedAssignment */
        $responses[$action::slug()] = $output;
    }
    return $responses;
}

/**
 * Get actions errors
 * @param array<array-key, ActionSuccess|ActionError> $responses
 * @return ActionError[]
 */
function getActionsErrors($responses)
{
    /** @var ActionError[] $errors */
    $errors = array_values(array_filter(array_map(static function($response, $slug) {
        if ($response['type'] !== 'error') {
            return false;
        }
        $action = ActionProvider::getInstance()->getActionBySlug((string) $slug);
        $actionName     = $action ? $action->name() : $slug;
        $response['message'] = "[$actionName]: " . $response['message'];
        return $response;
    }, $responses, array_keys($responses))));

    return $errors;
}

/**
 * @param int $postId
 * @param int $formId
 * @return FormSettings|null
 */
function getFormSettings($postId, $formId)
{
    $node = \Breakdance\Render\getNodeById($postId, $formId);

    if (!$node) return null;

    /** @var array{content: FormSettings, design: FormDesign, settings: array} $props */
    $props = $node['data']['properties'];

    /** @var FormSettings|null */
    return $props['content'];
}

/**
 * Get available actions for the given settings
 * @param FormSettings $settings
 * @return Action[]
 */
function getFormActions($settings)
{
    $enabledActions = $settings['actions']['actions'] ?? false;

    if (!$enabledActions) return [];

    $allActions = ActionProvider::getInstance()->getActions();

    return array_values(
        array_filter(
            $allActions,
            function (Action $action) use ($enabledActions) {
                return in_array($action::slug(), $enabledActions);
            }
        )
    );
}

/**
 * @param mixed $value
 * @param array $sanitizers
 * @return mixed|null
 */
function _sanitize($value, $sanitizers)
{
    if (is_array($value)) {
        return array_map(
            /**
             * @param mixed $itemVal
             * @return mixed
             */
            function ($itemVal) use ($sanitizers) {
                return _sanitize($itemVal, $sanitizers);
            },
            $value
        );
    }

    return array_reduce(
        $sanitizers,
        /**
         * @param mixed $val
         * @param \Closure $sanitizer
         * @return mixed
         */
        function ($val, $sanitizer) {
            return $sanitizer($val);
        },
        $value
    );
}

/**
 * @param mixed $value
 * @param string $type
 * @return mixed
 */
function sanitizeValue($value, $type)
{
    $sanitizers = [
        'email'    => ['sanitize_text_field', 'sanitize_email'],
        'text'     => ['sanitize_text_field'],
        'textarea' => ['sanitize_textarea_field']
    ];

    $default = ['sanitize_text_field'];

    return _sanitize($value, $sanitizers[$type] ?? $default);
}

/**
 * @param array $value
 * @return string
 */
function getCommaDelimitedValue($value)
{
    return implode(', ', $value);
}

/**
 * Merge labels and user submitted data together.
 * @param StoredFormField[] | null $storedFields
 * @param FormUserSubmittedContents $values
 * @return FormData
 */
function getFormData($storedFields, $values)
{
    if (!is_array($storedFields)) {
        return [];
    }

    return array_map(function ($storedField) use ($values) {
        $field = getFieldAttributes($storedField);
        /** @var mixed $original */
        $original = array_key_exists($field['advanced']['id'], $values) ? $values[$field['advanced']['id']] : '';
        /** @var string $value */
        $value = is_array($original) ? getCommaDelimitedValue($original) : $original;

        return array_merge($field, [
            'value' => $value,
            'originalValue' => $original
        ]);
    }, $storedFields);
}

/**
 * @param FormData $form
 * @param FormFileGroup $files
 * @return \WP_Error|true
 */
function validateFormData($form, $files)
{
    $bag = new \WP_Error();

    foreach ($form as $field) {
        $required = $field['advanced']['required'];
        if($required && array_key_exists('conditional', $field['advanced']) && $field['advanced']['conditional'] === true) {
            $required = isConditionalFieldRequired($field, $form);
        }

        // Required fields
        if ($required && $field['type'] !== 'file' && empty($field['value'])) {
            $bag->add('required', sprintf("%s is required.", $field['label']));
            continue; // Bail out from other validations if the value is undefined
        }

        // Email Addresses
        if ($required && $field['type'] === 'email' && !is_email($field['value'])) {
            $bag->add('invalid_email', "Please enter a valid email address.");
        }

        // File Upload
        /** @var FormFile[] $fieldFiles */
        $fieldFiles = $files[$field['advanced']['id']];

        if ($required && $field['type'] === 'file' && !$fieldFiles) {
            $bag->add('required_file', sprintf("%s is required.", $field['label']));
        }

        if ($field['type'] === 'file' && $fieldFiles) {
            $maxNumOfFiles = $field['max_number_of_files'] ?? 1;
            $maxFileSizeInMB = $field['max_file_size'] ?? 10;
            $maxFileSizeInBytes = $maxFileSizeInMB * pow(1024, 2);
            $allowedFileTypes = $field['allowed_file_types'] ?? [];

            if (count($fieldFiles) > $maxNumOfFiles) {
                $bag->add('file_max', sprintf("%s may not have more than %s file(s).", $field['label'], $maxNumOfFiles));
            }

            foreach ($fieldFiles as $file) {
                $size = filesize($file['file']);
                $isTypeValid = in_array($file['type'], $allowedFileTypes) || !$allowedFileTypes;

                if ($size > $maxFileSizeInBytes) {
                    $bag->add('file_size', sprintf("%s must be less than %s megabytes.", $field['label'], $maxFileSizeInMB));
                }

                if (!$isTypeValid) {
                    $parseFileTypes = implode(', ', preg_replace('(.*/)', '', $allowedFileTypes));
                    $bag->add('file_type', sprintf("%s must be a file of type: %s", $field['label'], $parseFileTypes));
                }
            }
        }
    }

    if ($bag->has_errors()) {
        return $bag;
    }

    return true;
}

// File handling

/**
 * Move all files to the appropriate upload directory.
 * @return FormFileGroup
 */
function handleUploadedFiles()
{
    $fields = [];
    if (array_key_exists('fields', $_FILES)) {
        /** @psalm-suppress MixedArgument */
        $fields = getFilesNormalized($_FILES['fields']);
    }
    $overrides = ['test_form' => false];

    /** @var FormFileGroup $output */
    $output = [];

    if (empty($fields)) return [];

    // Move files to server. We need to save it before attaching it to an email.
    foreach ($fields as $key => $files) {
        foreach ($files as $file) {
            /** @var FormFile $uploaded */
            $uploaded = wp_handle_upload($file, $overrides);

            if ($uploaded && !isset($uploaded['error'])) {
                $output[$key][] = $uploaded;
            }
        }
    }

    return $output;
}

/**
 * Remove an array of files from the server
 * @param FormFileGroup $fields
 */
function cleanUpFiles($fields)
{
    foreach ($fields as $files) {
        foreach ($files as $file) {
            wp_delete_file($file['file']);
        }
    }
}

/**
 * Normalize $_FILES array into a cleaner array and groups by input.
 * @param _FILES $_files
 * @return array<string, array<array-key, array<string, int|string>>> // WHAT THE FUCK
 */
function getFilesNormalized($_files)
{
    $files = [];

    foreach($_files as $key => $field) {
        foreach ($field as $name => $values) {
            foreach ($values as $index => $value) {
                $files[$name][$index][$key] = $value;
            }
        }
    }

    return $files;
}


/**
 * @param FormFieldWithValue $field
 * @param FormData $form
 * @return bool
 */
function isConditionalFieldRequired($field, $form)
{
    $condition = $field['advanced']['condition'] ?? [];
    if (array_key_exists('field', $condition) && array_key_exists('operand', $condition) && array_key_exists('value', $condition)) {
        /** @var array<array-key, FormFieldWithValue> $allFieldsByID */
        $allFieldsByID = array_column($form, null, 'id');
        if (!array_key_exists($condition['field'], $allFieldsByID)) {
            return false;
        }
        return shouldBeRequired($condition['value'], $condition['operand'], $allFieldsByID[$condition['field']]['value']);
    }

    return false;
}

/**
 * The below is replicated in JS in forms/shared/js/shared.js
 * so if you change it here, you need to change it there too.
 *
 * @param string $aValue
 * @param string $operand
 * @param string $bValue
 * @return bool
 */
function shouldBeRequired($aValue, $operand, $bValue) {
    if ($operand === "equals") {
        return $aValue == $bValue;
    }

    if ($operand === "not equals") {
        return $aValue != $bValue;
    }

    if ($operand === "is one of") {
        $bValueArray = explode($bValue, ',');
        return count(array_filter($bValueArray, static function($bValue) use ($aValue) { return trim($bValue) == $aValue; })) > 0;
    }

    if ($operand === "is none of") {
        $bValueArray = explode($bValue, ',');
        return count(array_filter($bValueArray, static function($bValue) use ($aValue) { return trim($bValue) == $aValue; })) === 0;
    }

    if ($operand === "contains") {
        if ($aValue && $bValue) {
            return stripos($aValue, $bValue) !== false;
        }
        return false;
    }

    if ($operand === "does not contain") {
        if ($aValue && $bValue) {
            return stripos($aValue, $bValue) !== false;
        }
        return true;
    }

    return false;
}

add_filter('posts_join', 'Breakdance\Forms\submission_search_join');
/**
 * @param string $join
 * @return string
 */
function submission_search_join($join)
{
    global $pagenow, $wpdb;

    $postType = (string) ($_GET['post_type'] ?? '');

    if ($pagenow === 'edit.php'  && 'breakdance_form_res' === $postType && !empty($_GET['s']) && is_admin()) {
        /** @psalm-suppress MixedPropertyFetch */
        $join .= 'LEFT JOIN ' . (string) $wpdb->postmeta . ' ON ' . (string) $wpdb->posts . '.ID = ' . (string) $wpdb->postmeta . '.post_id ';
    }
    return $join;
}

add_filter('posts_where', 'Breakdance\Forms\submission_search_where');
/**
 * @param string $where
 * @return string
 */
function submission_search_where($where)
{
    global $pagenow, $wpdb;
    $postType = (string) ($_GET['post_type'] ?? '');

    if ($pagenow === 'edit.php'  && 'breakdance_form_res' === $postType && !empty($_GET['s']) && is_admin()) {
        /** @psalm-suppress MixedPropertyFetch */
        $where = preg_replace(
            "/\(\s*" . (string) $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
            "(" . (string) $wpdb->posts . ".post_title LIKE $1) OR (" . (string) $wpdb->postmeta . ".meta_value LIKE $1)", $where
        );
    }
    return $where;
}

add_filter('posts_distinct', 'Breakdance\Forms\submission_search_distinct');
/**
 * @param string $where
 * @return string
 */
function submission_search_distinct($where)
{
    global $pagenow;
    $postType = (string) ($_GET['post_type'] ?? '');

    if ($pagenow === 'edit.php'  && 'breakdance_form_res' === $postType && !empty($_GET['s']) && is_admin()) {
        return "DISTINCT";
    }
    return $where;
}
