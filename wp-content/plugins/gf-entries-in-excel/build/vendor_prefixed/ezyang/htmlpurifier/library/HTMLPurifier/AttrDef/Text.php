<?php

/**
 * Validates arbitrary text according to the HTML spec.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_AttrDef_Text extends GFExcel_VendorHTMLPurifier_AttrDef
{

    /**
     * @param string $string
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @param GFExcel_VendorHTMLPurifier_Context $context
     * @return bool|string
     */
    public function validate($string, $config, $context)
    {
        return $this->parseCDATA($string);
    }
}

// vim: et sw=4 sts=4
