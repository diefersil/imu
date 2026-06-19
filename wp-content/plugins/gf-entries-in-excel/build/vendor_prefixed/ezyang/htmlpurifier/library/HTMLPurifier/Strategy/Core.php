<?php

/**
 * Core strategy composed of the big four strategies.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_Strategy_Core extends GFExcel_VendorHTMLPurifier_Strategy_Composite
{
    public function __construct()
    {
        $this->strategies[] = new GFExcel_VendorHTMLPurifier_Strategy_RemoveForeignElements();
        $this->strategies[] = new GFExcel_VendorHTMLPurifier_Strategy_MakeWellFormed();
        $this->strategies[] = new GFExcel_VendorHTMLPurifier_Strategy_FixNesting();
        $this->strategies[] = new GFExcel_VendorHTMLPurifier_Strategy_ValidateAttributes();
    }
}

// vim: et sw=4 sts=4
