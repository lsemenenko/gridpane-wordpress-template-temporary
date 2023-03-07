<?php

/**
 * @param boolean $isCustomQueryUsed 
 * @param boolean $isPaginationEnabled 
 * @param boolean $isArchive 
 * @return void 
 */
function showWarningInBuilderForImproperUseOfPaginationAndCustomQueriesOnArchives($isCustomQueryUsed, $isPaginationEnabled, $isArchive) {

    if (!\Breakdance\isRequestFromBuilderSsr()) {
        return;
    }

    if ($isArchive && $isCustomQueryUsed && $isPaginationEnabled) {
        echo "<div class='bde-no-custom-query-error'>Pagination with Custom Queries in Archives isn't supported</div> ";
    }

}