<?php

namespace Breakdance\DesignLibrary;

use function Breakdance\Data\get_global_option;
use function Breakdance\Data\get_meta;
use function Breakdance\Data\save_global_settings;
use function Breakdance\Data\set_global_option;
use function Breakdance\Data\set_meta;
use function Breakdance\remotePostToWpAjax;
use function Breakdance\Themeless\getTemplateSettingsFromDatabase;
use function Breakdance\Themeless\ManageTemplates\deleteTemplatesByPostType;
use function Breakdance\Themeless\ManageTemplates\saveTemplate;

/**
 * @param string $url
 * @return DesignSetData|array{error: string}
 */
function externalEndpoint($url)
{
    return getDesignSetRemoteData($url);
}

// ****** Getting external data endpoints ****** //
add_action('breakdance_loaded', function () {
    \Breakdance\AJAX\register_handler(
        'breakdance_get_external_design_set',
        'Breakdance\DesignLibrary\externalEndpoint',
        'full',
        true,
        [
            'args' => [
                'url' => FILTER_VALIDATE_URL
            ],
        ]
    );

    \Breakdance\AJAX\register_handler(
        'breakdance_get_external_global_settings',
        'Breakdance\DesignLibrary\getExternalGlobalSettings',
        'full',
        true,
        [
            'args' => [
                'url' => FILTER_VALIDATE_URL
            ],
        ]
    );

    \Breakdance\AJAX\register_handler(
        'breakdance_import_external_global_settings',
        'Breakdance\DesignLibrary\importExternalGlobalSettings',
        'full',
        true,
        [
            'args' => [
                'url' => FILTER_VALIDATE_URL
            ],
        ]
    );

    \Breakdance\AJAX\register_handler(
        'breakdance_get_design_library_data',
        'Breakdance\DesignLibrary\getDesignLibraryData',
        'full',
    );
});

/**
 * @param string $url
 * @return array
 */
function getExternalGlobalSettings($url){
    $request = remotePostToWpAjax($url, 'breakdance_get_global_settings_for_design_library');

    if (is_wp_error($request)) {
        /** @var \WP_Error $request */
        $request = $request;

        return ['error' => $request->get_error_message()];
    }

    $body = wp_remote_retrieve_body($request);

    /** @var mixed */
    $data = json_decode($body, true);

    if (isset($data['error'])) {
        /** @var array{error: string} */
        return $data;
    }

    if (!isset($data['globalSettings'])) return ['error' => 'Wrong data from ' . $url];

    /** @var array{globalSettings: mixed} */
    return $data;
}

/**
 * @param string $url
 * @return array{success: string}|array{error: string}
 */
function importExternalGlobalSettings($url){
    $externalGlobalSettings = getExternalGlobalSettings($url);

    if (isset($externalGlobalSettings['error'])) {
        /** @var array{error: string} */
        return $externalGlobalSettings;
    }

    if (!isset($externalGlobalSettings['globalSettings'])){
        return ['error' => "Wrong data from $url"];
    }

    save_global_settings(json_encode($externalGlobalSettings['globalSettings']));
    set_global_option('design_library_global_settings_last_imported_from_url', $url);

    return ['success' => 'Global settings imported'];
}

/**
 * @return array
 */
function getDesignLibraryData(){
    return [
        'designProviders' => getDesignProviders(),
        'globalSettingsLastImportedFromUrl' => get_global_option('design_library_global_settings_last_imported_from_url') ?: null,
        'fullSiteLastImportedFromUrl' => get_global_option('design_library_full_site_last_imported_from_url') ?: null,
    ];
}

// ***** Importing a full site endpoints ***** //

add_action('breakdance_loaded', function () {
    \Breakdance\AJAX\register_handler(
        'breakdance_design_lib_get_ids_of_posts_and_pages_to_import',
        'Breakdance\DesignLibrary\getIdsOfPostsToImport',
        'full',
        true,
        [
            'args' => [
                'url' => FILTER_VALIDATE_URL
            ],
        ]
    );

    \Breakdance\AJAX\register_handler(
        'breakdance_design_lib_get_ids_of_templates_to_import',
        'Breakdance\DesignLibrary\getIdsOfTemplatesToImport',
        'full',
        true,
        [
            'args' => [
                'url' => FILTER_VALIDATE_URL
            ],
        ]
    );

    \Breakdance\AJAX\register_handler(
        'breakdance_design_lib_import_post_or_page',
        'Breakdance\DesignLibrary\importPostOrPage',
        'full',
        true,
        [
            'args' => [
                'url' => FILTER_VALIDATE_URL,
                'id' => FILTER_VALIDATE_INT
            ],
        ]
    );

    \Breakdance\AJAX\register_handler(
        'breakdance_design_lib_import_template_by_id',
        'Breakdance\DesignLibrary\importTemplateById',
        'full',
        true,
        [
            'args' => [
                'url' => FILTER_VALIDATE_URL,
                'id' => FILTER_VALIDATE_INT
            ],
        ]
    );


    \Breakdance\AJAX\register_handler(
        'breakdance_design_lib_trash_all_templates_by_type',
        'Breakdance\DesignLibrary\trashAllTemplatesByType',
        'full',
        true,
        [
            'args' => [
                'templateType' => FILTER_SANITIZE_STRING
            ],
        ]
    );
});

/**
 * @param string $url
 * @return int[]|array{error: string}
 */
function getIdsOfPostsToImport($url)
{
    $request = remotePostToWpAjax($url, 'breakdance_design_lib_get_ids_of_posts_and_pages_to_export');

    if (is_wp_error($request)) {
        /** @var \WP_Error $request */
        $request = $request;

        return ['error' => $request->get_error_message()];
    }


    $idsOfPostsToImport = wp_remote_retrieve_body($request);

    /** @var int[] */
    $data = json_decode($idsOfPostsToImport, true);

    return $data;
}

/**
 * @param string $url
 * @return array
 */
function getIdsOfTemplatesToImport($url)
{
    $request = remotePostToWpAjax($url, 'breakdance_design_lib_get_ids_of_templates_to_export');

    if (is_wp_error($request)) {
        /** @var \WP_Error $request */
        $request = $request;

        return ['error' => $request->get_error_message()];
    }

    $idsOfPostsToImport = wp_remote_retrieve_body($request);

    /** @var array */
    return json_decode($idsOfPostsToImport, true);
}

/**
 * @param string $url
 * @param int $id
 * @return array
 */
function importPostOrPage($url, $id)
{
    $request = remotePostToWpAjax($url, 'breakdance_design_lib_get_post_data', ['id' => $id]);

    if (is_wp_error($request)) {
        /** @var \WP_Error $request */
        $request = $request;

        return ['error' => $request->get_error_message()];
    }

    $postData = wp_remote_retrieve_body($request);
    /** @var array{postData: array{ post_title: string, post_name: string, post_content: string, post_excerpt: string, post_status: string, post_type: string},breakdanceData: string} $postDataJson
     */
    $postDataJson = json_decode($postData, true);

    if (!isset($postDataJson['postData']) || !isset($postDataJson['breakdanceData'])) {
        return ['error' => "Wrong data from " . $url];
    }

    $existingPostOrPageWithSameTitle = get_page_by_title(
        $postDataJson['postData']['post_title'],
        'OBJECT',
        $postDataJson['postData']['post_type']
    );

    // don't save post/page if it already exists
    if ($existingPostOrPageWithSameTitle) {
        /** @var \WP_Post $existingPostOrPageWithSameTitle */
        $existingPostOrPageWithSameTitle = $existingPostOrPageWithSameTitle;

        if ($existingPostOrPageWithSameTitle->post_status !== "trash") {
            /** @var string $existingBreakdanceData */
            $existingBreakdanceData = get_meta($existingPostOrPageWithSameTitle->ID, 'breakdance_data');

            if ($existingBreakdanceData === $postDataJson['breakdanceData']) {
                return ['success' => $postDataJson['postData']['post_type'] . ' already exists'];
            }
        }
    }

    $importedPostId = wp_insert_post($postDataJson['postData']);

    if (!$importedPostId) return ['error' => "Failed at importing post"];

    if (is_wp_error($importedPostId)) {
        /** @var \WP_Error $request */
        $request = $request;

        return ['error' => $request->get_error_message()];
    }

    set_meta((int)$importedPostId, 'breakdance_data', $postDataJson['breakdanceData']);

    return ['success' => $postDataJson['postData']['post_type'] . " '$id' imported successfully"];
}

/**
 * @param string $url
 * @param int $id
 * @return array
 */
function importTemplateById($url, $id)
{
    $request = remotePostToWpAjax($url, 'breakdance_design_lib_get_template_data', ['id' => $id]);

    if (is_wp_error($request)) {
        /** @var \WP_Error $request */
        $request = $request;

        return ['error' => $request->get_error_message()];
    }

    $templateData = wp_remote_retrieve_body($request);
    /** @var array{isFallback: bool}|array{title: string, settings: string, postType: string, breakdanceData: string} $templateDataJson */
    $templateDataJson = json_decode($templateData, true);

    // ignore fallback templates
    if (isset($templateDataJson['isFallback'])) {
        return ['success' => 'Fallback templates are ignored'];
    }

    if (!isset($templateDataJson['title']) || !array_key_exists('settings', $templateDataJson)) {
        return ['error' => "Wrong data from " . $url];
    }

    /** @var array{title: string, settings: string, postType: string, breakdanceData: string} $templateDataJson */
    $templateDataJson = $templateDataJson;

    /**
     * @var \WP_Post|null
     */
    $existingTemplateWithSameTitle = get_page_by_title($templateDataJson['title'], 'OBJECT', $templateDataJson['postType']);

    // Don't import already existing templates
    if ($existingTemplateWithSameTitle && $existingTemplateWithSameTitle->post_status !== "trash") {
        $existingTemplateSettings = getTemplateSettingsFromDatabase($existingTemplateWithSameTitle->ID);

        if (json_encode($existingTemplateSettings) === json_encode($templateDataJson['settings'])) {
            return ['success' => 'Template already exists'];
        }
    }

    $savedTemplate = saveTemplate(
        $templateDataJson['title'],
        // -1 to create new template
        -1,
        json_encode($templateDataJson['settings']),
        $templateDataJson['postType']
    );

    if ($savedTemplate['error'] ?? !isset($savedTemplate['data']['id'])) return ['error' => "Failed at importing template " . $id];

    set_meta($savedTemplate['data']['id'] ?? 0, 'breakdance_data', $templateDataJson['breakdanceData']);

    return ['success' => "Imported template '$id' successfully"];
}

/**
 * @param string $templateType
 * @return array
 */
function trashAllTemplatesByType($templateType){
    $success = false;

    if ($templateType === 'headers'){
        $success = trashAllTemplatesByPostType(BREAKDANCE_HEADER_POST_TYPE);
    } else if($templateType === 'footers'){
        $success = trashAllTemplatesByPostType(BREAKDANCE_FOOTER_POST_TYPE);
    } else if($templateType === 'templates'){
        $success = trashAllTemplatesByPostType(BREAKDANCE_TEMPLATE_POST_TYPE);
    } else if($templateType === 'global_blocks'){
        $success = trashAllTemplatesByPostType(BREAKDANCE_BLOCK_POST_TYPE);
    } else if($templateType === 'popups'){
        $success = trashAllTemplatesByPostType(BREAKDANCE_POPUP_POST_TYPE);
    }


    if (!$success) {
        return ['error' => 'Failed to delete ' . $templateType];
    }

    return ['success' => "Deleted $templateType successfully."];
}

/**
 * @param string $postType
 * @return array
 */
function trashAllTemplatesByPostType($postType){
    /** @var int[] $templatesIdToTrash */
    $templatesIdToTrash = get_posts([
        'post_type' => $postType,
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'fields' => 'ids'
    ]);

    $templatesIdToTrash = removeFallbacksFromTemplateIdsList($templatesIdToTrash);

    $failedToDeleteSomething = false ;

    foreach ($templatesIdToTrash as $templateIdToTrash) {
        $trashed = wp_trash_post($templateIdToTrash);

        if (!$trashed) {
            $failedToDeleteSomething = true;
        }
    }

    if ($failedToDeleteSomething){
        return ['error' => "Failed to delete all $postType."];
    }

    return ['success' => "Deleted all $postType successfully."];
}
