<?php

/**
 * Dummy AttrDef that mimics another AttrDef, BUT it generates clones
 * with make.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_AttrDef_Clone extends GFExcel_VendorHTMLPurifier_AttrDef
{
    /**
     * What we're cloning.
     * @type GFExcel_VendorHTMLPurifier_AttrDef
     */
    protected $clone;

    /**
     * @param GFExcel_VendorHTMLPurifier_AttrDef $clone
     */
    public function __construct($clone)
    {
        $this->clone = $clone;
    }

    /**
     * @param string $v
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @param GFExcel_VendorHTMLPurifier_Context $context
     * @return bool|string
     */
    public function validate($v, $config, $context)
    {
        return $this->clone->validate($v, $config, $context);
    }

    /**
     * @param string $string
     * @return GFExcel_VendorHTMLPurifier_AttrDef
     */
    public function make($string)
    {
        return clone $this->clone;
    }
}

// vim: et sw=4 sts=4
