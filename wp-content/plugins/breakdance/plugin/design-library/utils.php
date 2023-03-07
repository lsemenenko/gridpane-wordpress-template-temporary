<?php

namespace Breakdance\DesignLibrary;

use function Breakdance\BrowseMode\isRequestFromBrowserIframe;
use function Breakdance\isRequestFromBuilderIframe;
use function Breakdance\remotePostToWpAjax;
use function Breakdance\Themeless\getTemplateSettingsFromDatabase;
use function Breakdance\Util\validateUrl;

class CacheWhetherDesignLibraryIsEnabled
{

    use \Breakdance\Singleton;

    /**
     * @var boolean
     */
    public $enabled = false;

    function __construct()
    {
        $isAdmin = \Breakdance\Permissions\hasPermission('full');
        $isDesignLibraryModal = isRequestFromDesignLibraryModal();
        $isRequestFromBuilderOrBrowseMode = isRequestFromBuilderIframe() || isRequestFromBrowserIframe();
        $enabledInDatabase = isDesignLibraryEnabled();

        $allowedForCurrentRequest = $isAdmin && $isDesignLibraryModal;

        $this->enabled = $enabledInDatabase && !$isRequestFromBuilderOrBrowseMode || $allowedForCurrentRequest;
    }

}

/**
 * @return bool
 */
function isDesignLibraryEnabled()
{
    return \Breakdance\Data\get_global_option('is_copy_from_frontend_enabled') == 'yes';
}

/**
 * @return bool
 */
function doesDesignLibraryRelyOnGlobalSettings()
{
    return \Breakdance\Data\get_global_option('design_library_relies_on_global_settings') == 'yes';
}

/**
 * @return bool
 */
function isDesignLibraryEnabledForCurrentRequest()
{
    return CacheWhetherDesignLibraryIsEnabled::getInstance()->enabled;
}

/**
 * @return bool
 */
function isRequestFromDesignLibraryModal()
{
    if (!isset($_GET['breakdance'])) {
        return false;
    }

    return $_GET['breakdance'] === 'design-library';
}

/**
 * @return string[]
 */
function getCopyableElements()
{
    $elements = [
        'EssentialElements\Section',
        'EssentialElements\HeaderBuilder',
    ];

    /** @var string[] */
    return apply_filters('breakdance_design_library_copyable_elements', $elements);
}

/**
 * Save providers to the database and turn commas into line breaks
 * @param array|string $providers
 * @return void
 */
function setDesignSets($providers)
{
    $providers = array_unique(array_map('esc_url', wp_parse_list($providers)));
    \Breakdance\Data\set_global_option('design_sets', $providers);
}

/**
 * @return string[]
 */
function getDesignSets()
{
    /** @var string[]|false */
    $designSets = \Breakdance\Data\get_global_option('design_sets');
    return is_array($designSets) ? $designSets : [];
}

/**
 * @return array{name: string, url: string, type?: string, isLocal?: boolean}[]
 */
function getDesignProviders()
{
    // TODO this is kinda ugly
    // but we either save it to the DB on install, or fetch from another server
    // both of which seem like overkill right now.
    $providers = [
        [
            "name" => "This Website",
            "url" => get_home_url(),
            "type" => "ui_kit",
            'isLocal' => true,
        ],
        // [
        //     "name" => "Jumpstart",
        //     "url" => "https://breakdancelibrary.com/jumpstart/",
        //     "type" => "ui_kit"
        // ],
        [
            "name" => "Samba",
            "url" => "https://breakdancelibrary.com/samba/",
            "type" => "ui_kit",
        ],
        [
            "name" => "Fancy Sections",
            "url" => "https://breakdancedemos.com/supa/",
            "type" => "sections",
        ],
        [
            "name" => "Element Demos",
            "url" => "https://breakdancedemos.com/elements/",
            "type" => "sections",
        ],
        [
            "name" => "Fitness Zone",
            "type" => "complete_site",
            "url" => "https://breakdancelibrary.com/fitness/",
            "meta" => [
                "thumbnailUrl" => "https://breakdance.com/wp-content/uploads/2023/01/fitness-featured.jpg",
            ],
        ],
        [
            "name" => "Security",
            "type" => "complete_site",
            "url" => "https://breakdancelibrary.com/security/",
            "meta" => [
                "thumbnailUrl" => "https://breakdance.com/wp-content/uploads/2023/01/security-featured.jpg",
            ],
        ],
        [
            "name" => "Auto Repair",
            "type" => "complete_site",
            "url" => "https://breakdancelibrary.com/auto/",
            "meta" => [
                "thumbnailUrl" => "https://breakdance.com/wp-content/uploads/2022/11/autogear-featured.jpg",
            ],
        ],
        [
            "name" => "Car Wash",
            "type" => "complete_site",
            "url" => "https://breakdancelibrary.com/carwash/",
            "meta" => [
                "thumbnailUrl" => "https://breakdance.com/wp-content/uploads/2022/11/carwash-featured-1.jpg",
            ],
        ],
        [
            "name" => "Architecture",
            "type" => "complete_site",
            "url" => "https://breakdancelibrary.com/architecture/",
            "meta" => [
                "thumbnailUrl" => "https://breakdance.com/wp-content/uploads/2022/11/arch-featured.jpg",
            ],
        ],
        [
            "name" => "Cleaning",
            "type" => "complete_site",
            "url" => "https://breakdancelibrary.com/cleaning-company/",
            "meta" => [
                "thumbnailUrl" => "https://breakdance.com/wp-content/uploads/2022/11/cleaning-featured-2.jpg",
            ],
        ],
        [
            "name" => "Plumbing",
            "type" => "complete_site",
            "url" => "https://breakdancelibrary.com/plumbing/",
            "meta" => [
                "thumbnailUrl" => "https://breakdance.com/wp-content/uploads/2022/11/plumbify-featured-1.jpg",
            ],
        ],
        [
            "name" => "Dental",
            "type" => "complete_site",
            "url" => "https://breakdancelibrary.com/dental/",
            "meta" => [
                "thumbnailUrl" => "https://breakdance.com/wp-content/uploads/2023/01/dental-featured.jpg",
            ],
        ],
        [
            "name" => "Beauty Salon",
            "type" => "complete_site",
            "url" => "https://breakdancelibrary.com/beauty-salon/",
            "meta" => [
                "thumbnailUrl" => "https://breakdance.com/wp-content/uploads/2023/01/salon-featured.jpg",
            ],
        ],
        [
            "name" => "Real Estate",
            "type" => "complete_site",
            "url" => "https://breakdancelibrary.com/real-estate/",
            "meta" => [
                "thumbnailUrl" => "https://breakdance.com/wp-content/uploads/2022/11/urban-estate-featured-1.jpg",
            ],
        ],
        [
            "name" => "Electrician",
            "type" => "complete_site",
            "url" => "https://breakdancelibrary.com/electricity/",
            "meta" => [
                "thumbnailUrl" => "https://breakdance.com/wp-content/uploads/2022/11/electrofix-featured-1.jpg",
            ],
        ],
        [
            "name" => "Law Firm",
            "type" => "complete_site",
            "url" => "https://breakdancelibrary.com/law-order/",
            "meta" => [
                "thumbnailUrl" => "https://breakdance.com/wp-content/uploads/2023/01/law-featured.jpg",
            ],
        ],
        [
            "name" => "Marketing Agency",
            "type" => "complete_site",
            "url" => "https://breakdancelibrary.com/marketingagency3/",
            "meta" => [
                "thumbnailUrl" => "https://breakdance.com/wp-content/uploads/2022/11/agency-featured.jpg",
            ],
        ],
    ];

    $designSets = array_filter(getDesignSets(), fn($url) => !empty($url));

    if (count($designSets)) {
        $designSets = array_map(
            fn($url) => [
                "name" => $url,
                "url" => $url,
            ],
            $designSets
        );
    }

    return array_merge($providers, $designSets ?: []);
}

/**
 * @return string[]
 */
function getInvalidDesignSets()
{
    $providers = getDesignSets();

    return array_filter(
        $providers,
        fn($provider) => !isValidDesignSet($provider)
    );
}

/**
 * @param string $url
 * @return DesignSetData|array{error: string}
 */
function getDesignSetRemoteData($url)
{
    $request = remotePostToWpAjax($url, 'breakdance_get_design_set');

    if (is_wp_error($request)) {
        /** @var \WP_Error $request */
        return ['error' => $request->get_error_message()];
    }

    if (is_array($request) && (!isset($request['response']['code']) || $request['response']['code'] !== 200)) {
        return ['error' => 'Unable to retrieve website'];
    }

    $body = wp_remote_retrieve_body($request);

    /** @var mixed */
    $data = json_decode($body);

    if (!is_object($data)) {
        return ['error' => 'Unable to decode data from website'];
    }

    /** @var DesignSetData */
    return (array) $data;
}

/**
 * @param boolean $includeDraft
 * @return array
 */
function getArgumentsForDesignSetPostsQuery($includeDraft)
{
    return [
        'post_type' => 'any',
        'numberposts' => -1,
        'post_status' => $includeDraft ? ['draft', 'publish'] : 'publish',
        'meta_query' => [
            'relation' => 'AND',
            [
                'relation' => 'OR',
                [
                    'key' => '_breakdance_hide_in_design_set',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key' => '_breakdance_hide_in_design_set',
                    'compare' => '!=',
                    'value' => '1',
                ],
            ],
            [
                'key' => 'breakdance_data',
                'compare' => 'EXISTS',
            ],
        ],
    ];
}

/**
 * @return DesignSetPost[]
 */
function getPostsForDesignSet()
{
    $isAdmin = \Breakdance\Permissions\hasPermission('full');

    /** @var \WP_Post[] */
    $posts = get_posts(getArgumentsForDesignSetPostsQuery(true));

    return array_map(function ($post) {
        /** @var string */
        $rawTags = get_post_meta($post->ID, '_breakdance_tags', true);
        /** @var string[] */
        $tags = wp_parse_list($rawTags);
        /** @var string */
        $url = get_permalink($post->ID);

        return [
            'id' => $post->ID,
            'name' => $post->post_title ?: '(no title)',
            'url' => $url,
            'tags' => $tags,
        ];
    }, $posts);
}

/**
 * @param string $url
 * @return bool
 */
function isValidDesignSet($url)
{
    if ($url === '') {
        return false;
    }

    if (!validateUrl($url)) {
        return false;
    }

    $response = getDesignSetRemoteData($url);

    if (array_key_exists('error', $response)) {
        return false;
    }

    if (!$response) {
        return false;
    }

    if (empty($response['enabled'])) {
        return false;
    }

    return true;
}

/**
 * @param int[] $templateIds
 * @return int[]
 */
function removeFallbacksFromTemplateIdsList($templateIds)
{
    return array_values(
        array_filter(
            $templateIds,
            /**
             * @param int $id
             */
            function ($id) {
                $settings = getTemplateSettingsFromDatabase($id);
                return !($settings['fallback'] ?? false);
            }
        )
    );
}
