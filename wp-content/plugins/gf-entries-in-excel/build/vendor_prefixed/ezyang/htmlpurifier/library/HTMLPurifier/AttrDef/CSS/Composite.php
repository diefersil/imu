<?php

/**
 * Allows multiple validators to attempt to validate attribute.
 *
 * Composite is just what it sounds like: a composite of many validators.
 * This means that multiple GFExcel_VendorHTMLPurifier_AttrDef objects will have a whack
 * at the string.  If one of them passes, that's what is returned.  This is
 * especially useful for CSS values, which often are a choice between
 * an enumerated set of predefined values or a flexible data type.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_AttrDef_CSS_Composite extends GFExcel_VendorHTMLPurifier_AttrDef
{

    /**
     * List of objects that may process strings.
     * @type GFExcel_VendorHTMLPurifier_AttrDef[]
     * @todo Make protected
     */
    public $defs;

    /**
     * @param GFExcel_VendorHTMLPurifier_AttrDef[] $defs List of GFExcel_VendorHTMLPurifier_AttrDef objects
     */
    public function __construct($defs)
    {
        $this->defs = $defs;
    }

    /**
     * @param string $string
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @param GFExcel_VendorHTMLPurifier_Context $context
     * @return bool|string
     */
    public function validate($string, $config, $context)
    {
        foreach ($this->defs as $i => $def) {
            $result = $this->defs[$i]->validate($string, $config, $context);
            if ($result !== false) {
                return $result;
            }
        }
        return false;
    }
}

// vim: et sw=4 sts=4
