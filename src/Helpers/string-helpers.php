<?php

use Symfony\Component\String\Slugger\AsciiSlugger;

if (! function_exists('slug')) {
    /**
     * Make a string a slug.
     *
     * @param string $text
     * @return string
     */
    function slug(string $text): string
    {
        $slugger = new AsciiSlugger();
        return strtolower($slugger->slug($text));
    }
}

if (! function_exists('camelToSlug')) {
    /**
     * Convert Camel Case to Slug.
     *
     * @param string $text
     * @return string
     */
    function camelToSlug(string $text): string
    {
        $text = preg_replace('/(?<=\\w)(?=[A-Z])/',"-$1", $text);
        return strtolower($text);
    }
}

if (! function_exists('slugToCamel')) {
    /**
     * Convert Slug to Camel Case.
     *
     * @param string $text
     * @return string
     */
    function slugToCamel(string $text): string
    {
        return ucfirst(preg_replace_callback('/[-_](.)/', function ($matches) {
            return strtoupper($matches[1]);
        }, $text));
    }
}
