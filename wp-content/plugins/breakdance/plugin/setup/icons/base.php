<?php

namespace Breakdance\Setup;

/**
 * @return string
 */
function get_stock_icons()
{
    $filename = 'stock-icons.dat';

    /** @var string */
    $icons = unserialize(base64_decode(trim(file_get_contents(dirname(__FILE__) . '/' . $filename))));
    return $icons;
}


// TODO: make a way to run this from the WP CLI
function export_icons()
{

    /** @var string */
    $icons = \Breakdance\Data\get_global_option('icons');
    $data = base64_encode(serialize($icons));

    $filename = '/icons-' . substr(md5((string) time()), 0, 12) . '.dat';

    $bytes = file_put_contents(dirname(__FILE__) . $filename, $data);

    echo "$bytes bytes written to $filename.<br />";
    echo "Ensure stock-icons-license.md is up to date.";
}
// export_icons();
// die();
