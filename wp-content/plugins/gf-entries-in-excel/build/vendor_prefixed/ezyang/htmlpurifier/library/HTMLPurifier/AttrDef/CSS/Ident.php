<?php

/**
 * Validates based on {ident} CSS grammar production
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_AttrDef_CSS_Ident extends GFExcel_VendorHTMLPurifier_AttrDef
{

    /**
     * @param string $string
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @param GFExcel_VendorHTMLPurifier_Context $context
     * @return bool|string
     */
    public function validate($string, $config, $context)
    {
        $string = trim($string);

        // early abort: '' and '0' (strings that convert to false) are invalid
        if (!$string) {
            return false;
        }

        $pattern = '/^(-?[A-Za-z_][A-Za-z_\-0-9]*)$/';
        if (!preg_match($pattern, $string)) {
            return false;
        }
        return $string;
    }
}

// vim: et sw=4 sts=4
