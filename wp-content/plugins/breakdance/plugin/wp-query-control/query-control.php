<?php

namespace Breakdance\WpQueryControl;

use function Breakdance\Util\WP\isAnyArchive;

/**
 * @param QueryControlParams|null $wpQueryControlProperties
 * @param array $extraArgsToMerge
 * @psalm-suppress MixedInferredReturnType
 * @return array|string
 */
function getWpQueryArgumentsFromWpQueryControlProperties($wpQueryControlProperties, $extraArgsToMerge = [])
{
    if (isAnyArchive() && !$wpQueryControlProperties) {
        /**
         * @psalm-suppress InvalidGlobal
         */
        global $wp_query;

        /**
         * Use the default query when there's no "Custom Query" selected
         *
         * @psalm-suppress MixedReturnStatement
         * @psalm-suppress MixedPropertyFetch
         */
        return $wp_query->query_vars;
    }

    if (!$wpQueryControlProperties) {
        $defaultQuery = array_merge(
        // these defaults are the same as in "WpQuery.vue", keep them synced
            [
                'posts_per_page' => 8,
            ],
            $extraArgsToMerge
        );

        /** @var array $defaultQuery */
        $defaultQuery = apply_filters('breakdance_query_control_query', $defaultQuery);

        return $defaultQuery;
    }

    $wpCustomQuery = $wpQueryControlProperties['custom'];
    $wpTextQuery = $wpQueryControlProperties['text'];
    $phpCodeForQuery = $wpQueryControlProperties['php'];
    $activeQuery = $wpQueryControlProperties['active'];

    if ($activeQuery === "text"){
        // We ignore $extraArgsToMerge here
        /** @var string $wpTextQuery */
        $wpTextQuery = apply_filters('breakdance_query_control_query', $wpTextQuery);

        return $wpTextQuery;
    }

    if ($activeQuery === "php"){
        // If the "eval" fails the SSR will fail
        /** @psalm-suppress MixedReturnStatement */
        $manualPhpQuery = array_merge($extraArgsToMerge, eval($phpCodeForQuery));

        /** @var array $manualPhpQuery */
        $manualPhpQuery = apply_filters('breakdance_query_control_query', $manualPhpQuery);

        return $manualPhpQuery;
    }

    /** @psalm-suppress RedundantConditionGivenDocblockType
     *I know it's redundant but I like the explicitness, Psalm
     */
    if (
        $activeQuery === "custom" &&
        $wpCustomQuery['source'] === 'post_types' &&
        isset($wpCustomQuery['conditions']) &&
        sizeof($wpCustomQuery['conditions'])
    ) {
        // conditions is always an array of conditions, but only the first will be populated by the QueryControl
        $conditions = reset($wpCustomQuery['conditions']);
        $conditionsByRuleSlug = getConditionsByRuleSlug($conditions);

        // the "value" of the condition right now is mixed, so we tell Psalm what to expect here

        /** @var array{operand: string, value: array{value: string[]}}|null */
        $hasTaxonomy = $conditionsByRuleSlug['has-taxonomy'] ?? null;
        /** @var array{operand: string, value: string}|null */
        $featuredImage = $conditionsByRuleSlug['featured-image'] ?? null;
        /** @var array{operand: string, value: string}|null */
        $commentsNumber = $conditionsByRuleSlug['comments-number'] ?? null;
        /** @var array{operand: string, value: list<array{value: string, text: string}>}|null */
        $postAuthor = $conditionsByRuleSlug["post-author"] ?? null;

        $queryArguments = [
            'post_type' => $wpCustomQuery['postTypes'],
            'post_status' => 'publish',
            'orderby' => $wpCustomQuery['orderBy'],
            'order' => $wpCustomQuery['order'],
            'ignore_sticky_posts' => $wpCustomQuery['ignoreStickyPosts'],
            'date_query' => getDateQuery(
                $wpCustomQuery['date'],
                $wpCustomQuery['beforeDate'],
                $wpCustomQuery['afterDate']
            ),
            'tax_query' => getTaxonomyQuery($hasTaxonomy),
            'comment_count' => getCommentNumberQuery($commentsNumber),
            'meta_query' => getFeaturedImageMetaQuery($featuredImage),
            'post__not_in' => $wpCustomQuery['ignoreCurrentPost'] === true && get_the_ID() ? [get_the_ID()] : null,
            'post__in' => getPostInQuery($conditionsByRuleSlug),
            'posts_per_page' => getPostsPerPage($wpCustomQuery['postsPerPage'] ?? 8, $wpCustomQuery['totalPosts'] ?? null),
            // offset makes "paged" invalid and destroys pagination, so ignore it if its set
            'offset' => isset($extraArgsToMerge['paged']) && $extraArgsToMerge['paged'] ? null : $wpCustomQuery['offset'] ?? null,
        ];

        $authorQuery = getAuthorQuery($postAuthor);

        $customQuery = array_merge($extraArgsToMerge, $queryArguments, $authorQuery);
        /** @var array  $customQuery */
        $customQuery = apply_filters('breakdance_query_control_query', $customQuery);

        return $customQuery;

    }

    /** @psalm-suppress RedundantConditionGivenDocblockType
     *I know it's redundant but I like the explicitness, Psalm
     */
    if ($activeQuery === "custom" && $wpCustomQuery['source'] === 'related'){
        $post = get_post();

        if (!$post){
            /** @var array $noPostArgs */
            $noPostArgs =  apply_filters('breakdance_query_control_query', $extraArgsToMerge);

            return $noPostArgs;
        }

        /** @var \WP_Post $post */
        $post = $post;

        $id = $post->ID;

        $queryArguments = [
            'post_type' => get_post_type($id),
            'post_status' => 'publish',
            'orderby' => $wpCustomQuery['orderBy'],
            'order' => $wpCustomQuery['order'],
            // always ignore for related posts
            'ignore_sticky_posts' => true,
            'date_query' => getDateQuery(
                $wpCustomQuery['date'],
                $wpCustomQuery['beforeDate'],
                $wpCustomQuery['afterDate']
            ),
            'tax_query' => getTermsQuery($wpCustomQuery['includeByTaxonomies'], $id),
            'author' => $wpCustomQuery['includeByAuthor'] ? $post->post_author : null,
            'post__not_in' => [$id],
            'posts_per_page' => $wpCustomQuery['postsPerPage'] ?? 3,
            // ignore paged even if it's sent by $extraArgsToMerge, it makes no sense for "related" posts
            'paged' => null,
        ];

        $relatedQuery = array_merge($extraArgsToMerge, $queryArguments);
        /** @var array $relatedQuery */
        $relatedQuery = apply_filters('breakdance_query_control_query', $relatedQuery);

        return $relatedQuery;
    }

    /** @psalm-suppress RedundantConditionGivenDocblockType
     *I know it's redundant but I like the explicitness, Psalm
     */
    if ($activeQuery === "custom" && $wpCustomQuery['source'] === 'acf_relationship' && function_exists('get_field')){
        $fieldName = $wpCustomQuery['acfField'];
        /** @var int[]|\WP_Post[] $field */
        $field = get_field($fieldName);
        $relatedIds = array_map(static function($related) {
            if ($related instanceof \WP_Post) {
                return $related->ID;
            }
            return $related;
        }, $field);
        $queryArguments = [
            'post__in' => $relatedIds,
            'post_type' => 'any',
            'post_status' => 'publish',
            'orderby' => $wpCustomQuery['orderBy'],
            'order' => $wpCustomQuery['order'],
            // always ignore for related posts
            'ignore_sticky_posts' => true,
            'date_query' => getDateQuery(
                $wpCustomQuery['date'],
                $wpCustomQuery['beforeDate'],
                $wpCustomQuery['afterDate']
            ),
            'posts_per_page' => $wpCustomQuery['postsPerPage'] ?? 3,
            // ignore paged even if it's sent by $extraArgsToMerge, it makes no sense for "related" posts
            'paged' => null,
        ];

        $acfRelationshipQuery = array_merge($extraArgsToMerge, $queryArguments);
        /** @var array $acfRelationshipQuery */
        $acfRelationshipQuery = apply_filters('breakdance_query_control_query', $acfRelationshipQuery);

        return $acfRelationshipQuery;
    }

    /** @var array $defaultQuery */
    $defaultQuery = apply_filters('breakdance_query_control_query', $extraArgsToMerge);

    return $defaultQuery;
}

/**
 * @param TemplateRule[] $conditions
 * @return array<string, array{operand: string, value: mixed}>
 */
function getConditionsByRuleSlug($conditions)
{
    return array_reduce(
        $conditions,
        /**
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MixedArgument
         */

        /**
         * @param array<string, array{operand: string, value: mixed}> $acumulator
         * @param TemplateRule $condition
         * @return array<string, array{operand: string, value: mixed}>
         */
        function ($acumulator, $condition) {
            if (!isset($condition['ruleSlug']) || !isset($condition['value'])) {
                return $acumulator;
            }

            return array_merge(
                $acumulator,
                [$condition['ruleSlug'] => ['operand' => $condition['operand'], 'value' => $condition['value']]]
            );
        }, []);
}

/**
 * @param array{operand: string, value: list<array{value: string, text: string}>}|null $condition
 * @return array
 */
function getAuthorQuery($condition)
{
    if (!$condition) {
        return [];
    }

    $authorOperand = $condition['operand'];
    $authors = $condition['value'];

    $authorIds = array_map(function ($author) {
        return (int)$author['value'];
    }, $authors);

    $postAuthorQuery = [];

    if ($authorOperand === OPERAND_IS) {
        $postAuthorQuery['author__in'] = $authorIds;
    } elseif ($authorOperand === OPERAND_IS_NOT) {
        $postAuthorQuery['author__not_in'] = $authorIds;
    }

    return $postAuthorQuery;

}

/**
 * @param string $date
 * @param string|null $beforeDate
 * @param string|null $afterDate
 * @return array|null
 */
function getDateQuery($date, $beforeDate, $afterDate)
{
    if ($date === 'custom') {
        return [
            'before' => $beforeDate,
            'after' => $afterDate,
            'inclusive' => true
        ];
    } else if ($date !== 'all' && $date) {
        return [
            'after' => $date
        ];
    }

    return null;
}

/**
 * @param array{operand: string, value: array{value: string[]}}|null $condition
 * @return array|null
 */
function getTaxonomyQuery($condition)
{
    if (!$condition) {
        return null;
    }

    $terms_and_taxes = [];

    /**
     * @psalm-suppress MixedAssignment
     * @psalm-suppress PossiblyInvalidIterator
     */
    foreach ($condition['value'] as $taxonomyData) {
        /** @var array{value: string} */
        $taxonomyData = $taxonomyData;
        $taxonomyValue = $taxonomyData['value'];
        /** @var array{allInTax: string, slug: string, name: string} */
        $values = json_decode($taxonomyValue, true);

        if (isset($values['allInTax'])) {
            // the user selected "all in category/tag/etc"
            /**
             * @var string
             */
            $taxonomy = $values['allInTax'];
            // get all terms for that taxonomy and add them to the query
            $allTermsInTax = get_terms($taxonomy);

            if (!is_wp_error($allTermsInTax)) {
                /** @psalm-suppress PossiblyInvalidArgument */
                $allSlugsInTerms = wp_list_pluck($allTermsInTax, 'slug');

                $terms_and_taxes[] = [
                    'field' => 'slug',
                    'terms' => $allSlugsInTerms,
                    'taxonomy' => $taxonomy,
                ];
            }
        } elseif (isset($values['slug'], $values['name'])) {
            $terms_and_taxes[] = [
                'field' => 'slug',
                'terms' => [$values['slug']],
                'taxonomy' => $values['name'],
            ];
        }
    }

    if ($condition['operand'] === OPERAND_ONE_OF) {
        $relation = "OR";
    } elseif ($condition['operand'] === OPERAND_ALL_OF) {
        $relation = "AND";
    }

    if (isset($relation) && count($terms_and_taxes) > 0) {
        return array_merge(
            ['relation' => $relation],
            $terms_and_taxes
        );
    }

    return null;
}

/**
 * @param string[]|null $taxonomies
 * @param int $postId
 *
 * @return array
 */
function getTermsQuery($taxonomies, $postId){
    if (!$taxonomies){
        return [];
    }

    $termsQuery = [];
    foreach ($taxonomies as $taxonomy){
        $taxonomyTermsInPost = get_the_terms($postId, $taxonomy);

        if (is_wp_error($taxonomyTermsInPost) || !$taxonomyTermsInPost){
            continue;
        }

        /** @var \WP_Term[] $taxonomyTermsInPost */
        $taxonomyTermsInPost = $taxonomyTermsInPost;

        $termsQuery[] = [
            'terms' => array_map(fn($term) => $term->term_id, $taxonomyTermsInPost),
            'taxonomy' => $taxonomy
        ];
    }

    if (count($termsQuery) > 0) {
        return array_merge(
            ['relation' => 'OR'],
            $termsQuery
        );
    }


    return $termsQuery;
}

/**
 * @param array{operand: string, value: string}|null $condition
 * @return array{value: int, compare: string}|null
 */
function getCommentNumberQuery($condition)
{
    if (!$condition) {
        return null;
    }

    $operand = $condition['operand'];

    if ($operand === OPERAND_IS) {
        $comparison = '=';
    } elseif ($operand === OPERAND_IS_NOT) {
        $comparison = '!=';
    } elseif ($operand === OPERAND_GREATER_THAN) {
        $comparison = '>';
    } elseif ($operand === OPERAND_LESS_THAN) {
        $comparison = '<';
    } else {
        return null;
    }

    return [
        'value' => (int)$condition['value'],
        'compare' => $comparison,
    ];
}

/**
 * @param array{operand: string, value: string}|null $condition
 * @return \string[][]|null
 */
function getFeaturedImageMetaQuery($condition)
{
    if (!$condition) {
        return null;
    }

    return [
        [
            'key' => '_thumbnail_id',
            'compare' => $condition['value'] === 'set'
                ? 'EXISTS'
                : 'NOT EXISTS',
        ],
    ];
}

/**
 * @param array<string, array{operand: string, value: mixed}> $conditionsByRuleSlug
 * @return int[]|null
 */
function getPostInQuery($conditionsByRuleSlug)
{
    $postsIn = [];

    foreach ($conditionsByRuleSlug as $ruleSlug => $condition) {
        if (preg_match('/post-dropdown-.*/', $ruleSlug)) {
            /** @var list<array{value: string, text: string}> */
            $value = $condition['value'];

            foreach ($value as $postData) {
                /**
                 * @psalm-suppress PossiblyInvalidArrayAssignment
                 */
                $postsIn[] = (int)$postData['value'];
            }
        }
    }

    return count($postsIn) ? $postsIn : null;
}

/**
 * Returns the posts per page, or 0 if we've reached totalPosts
 * By returning 0 postsPerPage, we artificially limit the total posts available, since WP_Query doesn't have a better way
 * @param int $postsPerPage
 * @param int|null $totalPosts
 * @return int
 */
function getPostsPerPage($postsPerPage, $totalPosts){
    $paged = getPage();

    if ($totalPosts && $totalPosts > 0 && $paged){
        /** @var int $paged */
        $paged = $paged;
        $currentTotalPosts = $postsPerPage * $paged;

        if ($currentTotalPosts >= $totalPosts){
            // we can have reached the end but still need to show some posts
            // e.g: totalPosts = 20. postsPerPage = 6. On the 4th page we'll have currentTotal = 24.
            // We still need to show 2 posts to reach totalPosts (we've shown 18 so far)
            $postsShownUntilNow = ($postsPerPage * ($paged - 1));
            $postsStillToShow = $totalPosts - $postsShownUntilNow;

            return $postsStillToShow > 0 ? $postsStillToShow : 0;
        }
    }

    return $postsPerPage;
}

/**
 * global $paged doesn't work in homepage for custom queries (like adding using a Post List)
 * Try to get the paged query in every way possible.
 *
 * @return int
 */
function getPage(){
    global $paged;

    if (is_int($paged)) return $paged;

    /** @var int|null $page */
    $page = get_query_var('page');

    if (is_int($page)) return $page;

    /** @var int|null $pagedFromQuery */
    $pagedFromQuery = get_query_var('paged');

    if (is_int($pagedFromQuery)) return $pagedFromQuery;

    return 0;
}
