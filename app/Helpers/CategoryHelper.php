<?php

namespace App\Helpers;

class CategoryHelper
{

    /**
     * @param array $categories
     * @param array $cat
     * @return void
     */
    public static function unsetParents(&$categories, $cat)
    {
        if ($cat->parent()->count()) {
            if (($key = array_search($cat->parent->id, $categories)) !== false) {
                unset($categories[$key]);
            }

            self::unsetParents($categories, $cat->parent);
        }
    }

    /**
     * @param array $categories
     * @param array $cat
     * @return void
     */
    public static function addChildren(&$categories, $cat)
    {
        if ($cat->children()->count()) {
            foreach ($cat->children as $child) {
                $categories[] = $child->id;
                self::addChildren($categories, $child);
            }
        }
    }
}
