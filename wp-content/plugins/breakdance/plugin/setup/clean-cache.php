<?php

namespace Breakdance\Setup;

use function Breakdance\Data\get_global_option;
use function Breakdance\Data\set_global_option;
use function Breakdance\Render\clearAllCssCachesAndDeleteCachedFiles;
use function Breakdance\Render\generateCacheForGlobalSettings;

// We can't use "upgrader_process_complete" because a user may just replace the plugin folder with a new one
add_action('breakdance_loaded', "\Breakdance\Setup\cleanCacheWhenCacheCountChanges");

function cleanCacheWhenCacheCountChanges(){
    $optionName = 'last_clear_css_cache_flag_value';

    /** @var integer | false $lastCleanCacheFlag */
    $lastCleanCacheFlag = get_global_option($optionName);
    $currentClearCacheFlag = defined('__BREAKDANCE_CLEAR_CSS_CACHE_FLAG__') ? (int) __BREAKDANCE_CLEAR_CSS_CACHE_FLAG__ : false;

    if($lastCleanCacheFlag === false || $lastCleanCacheFlag !== $currentClearCacheFlag){
        clearAllCssCachesAndDeleteCachedFiles();
        generateCacheForGlobalSettings();

        set_global_option($optionName, $currentClearCacheFlag);
    }
}
