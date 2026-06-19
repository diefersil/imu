<?php

/**
 * @file
 * Defines a function wrapper for HTML Purifier for quick use.
 * @note ''GFExcel_VendorHTMLPurifier()'' is NOT the same as ''new GFExcel_VendorHTMLPurifier()''
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */

/**
 * Purify HTML.
 * @param string $html String HTML to purify
 * @param mixed $config Configuration to use, can be any value accepted by
 *        GFExcel_VendorHTMLPurifier_Config::create()
 * @return string
 */
function GFExcel_VendorHTMLPurifier($html, $config = null)
{
    static $purifier = false;
    if (!$purifier) {
        $purifier = new GFExcel_VendorHTMLPurifier();
    }
    return $purifier->purify($html, $config);
}

// vim: et sw=4 sts=4
