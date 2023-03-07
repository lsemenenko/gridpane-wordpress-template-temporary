<?php

namespace EssentialElements;

class MenuLink extends \EssentialElements\TextLink
{
    static function name()
    {
        return 'Menu Link';
    }

    static function slug()
    {
       return get_class();
    }

    static function nestingRule()
    {
        return ["type" => "final", "restrictedToBeADirectChildOf" => ['EssentialElements\MenuBuilder'] ];
    }

    static function designControls() {
        return [];
    }

    static function category()
    {
        return 'site';
    }

}
