<?php

/**
 * @file
 * Emulation layer for code that used kses(), substituting in HTML Purifier.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */

require_once dirname(__FILE__) . '/GFExcel_VendorHTMLPurifier.auto.php';

function kses($string, $allowed_html, $allowed_protocols = null)
{
    $config = GFExcel_VendorHTMLPurifier_Config::createDefault();
    $allowed_elements = array();
    $allowed_attributes = array();
    foreach ($allowed_html as $element => $attributes) {
        $allowed_elements[$element] = true;
        foreach ($attributes as $attribute => $x) {
            $allowed_attributes["$element.$attribute"] = true;
        }
    }
    $config->set('HTML.AllowedElements', $allowed_elements);
    $config->set('HTML.AllowedAttributes', $allowed_attributes);
    if ($allowed_protocols !== null) {
        $config->set('URI.AllowedSchemes', $allowed_protocols);
    }
    $purifier = new GFExcel_VendorHTMLPurifier($config);
    return $purifier->purify($string);
}

// vim: et sw=4 sts=4
