<?php

namespace Breakdance\Data;

use function Breakdance\Render\getRenderedNodes;

/**
 * @param int $post_id
 * @return false|Tree
 */
function get_tree($post_id)
{

    /**
     * @var string|false
     */
    $tree_json_string = get_meta($post_id, 'breakdance_data', 'tree_json_string');

    if ($tree_json_string === false) {
        return false;
    }

    /** @var Tree|null */
    $tree = json_decode($tree_json_string, true);

    return is_valid_tree($tree) ? $tree : false;

}

/**
 * @psalm-assert-if-true Tree $tree
 * @param mixed $tree
 * @return boolean
 */
function is_valid_tree($tree)
{
    /*  does it have a root with id and data and children? then it, it's probably fine */

    if (!is_array($tree)) {
        return false;
    }

    if (!array_key_exists('root', $tree)) {
        return false;
    }

    if (!is_array($tree['root'])) {
        return false;
    }

    if (!array_key_exists('id', $tree['root'])) {
        return false;
    }

    if (!array_key_exists('data', $tree['root'])) {
        return false;
    }

    if (!array_key_exists('children', $tree['root'])) {
        return false;
    }

    return true;
}

/**
 * @param int $id
 * @return string
 */
function get_tree_as_html($id)
{
    $rendered = getRenderedNodes($id, true);

    return $rendered === false ? '' : $rendered['html'];
}
