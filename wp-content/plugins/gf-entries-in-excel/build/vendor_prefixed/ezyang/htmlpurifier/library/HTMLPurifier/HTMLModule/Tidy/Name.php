<?php

/**
 * Name is deprecated, but allowed in strict doctypes, so onl
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_HTMLModule_Tidy_Name extends GFExcel_VendorHTMLPurifier_HTMLModule_Tidy
{
    /**
     * @type string
     */
    public $name = 'Tidy_Name';

    /**
     * @type string
     */
    public $defaultLevel = 'heavy';

    /**
     * @return array
     */
    public function makeFixes()
    {
        $r = array();
        // @name for img, a -----------------------------------------------
        // Technically, it's allowed even on strict, so we allow authors to use
        // it. However, it's deprecated in future versions of XHTML.
        $r['img@name'] =
        $r['a@name'] = new GFExcel_VendorHTMLPurifier_AttrTransform_Name();
        return $r;
    }
}

// vim: et sw=4 sts=4
