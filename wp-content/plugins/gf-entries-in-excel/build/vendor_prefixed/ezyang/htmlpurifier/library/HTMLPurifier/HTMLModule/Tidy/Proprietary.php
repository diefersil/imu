<?php
/**
 * @license LGPL-2.1-or-later
 *
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */

class GFExcel_VendorHTMLPurifier_HTMLModule_Tidy_Proprietary extends GFExcel_VendorHTMLPurifier_HTMLModule_Tidy
{

    /**
     * @type string
     */
    public $name = 'Tidy_Proprietary';

    /**
     * @type string
     */
    public $defaultLevel = 'light';

    /**
     * @return array
     */
    public function makeFixes()
    {
        $r = array();
        $r['table@background'] = new GFExcel_VendorHTMLPurifier_AttrTransform_Background();
        $r['td@background']    = new GFExcel_VendorHTMLPurifier_AttrTransform_Background();
        $r['th@background']    = new GFExcel_VendorHTMLPurifier_AttrTransform_Background();
        $r['tr@background']    = new GFExcel_VendorHTMLPurifier_AttrTransform_Background();
        $r['thead@background'] = new GFExcel_VendorHTMLPurifier_AttrTransform_Background();
        $r['tfoot@background'] = new GFExcel_VendorHTMLPurifier_AttrTransform_Background();
        $r['tbody@background'] = new GFExcel_VendorHTMLPurifier_AttrTransform_Background();
        $r['table@height']     = new GFExcel_VendorHTMLPurifier_AttrTransform_Length('height');
        return $r;
    }
}

// vim: et sw=4 sts=4
