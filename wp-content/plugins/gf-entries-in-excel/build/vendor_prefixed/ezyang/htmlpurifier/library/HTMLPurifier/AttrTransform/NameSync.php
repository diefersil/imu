<?php

/**
 * Post-transform that performs validation to the name attribute; if
 * it is present with an equivalent id attribute, it is passed through;
 * otherwise validation is performed.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_AttrTransform_NameSync extends GFExcel_VendorHTMLPurifier_AttrTransform
{

    /**
     * @type GFExcel_VendorHTMLPurifier_AttrDef_HTML_ID
     */
    public $idDef;

    public function __construct()
    {
        $this->idDef = new GFExcel_VendorHTMLPurifier_AttrDef_HTML_ID();
    }

    /**
     * @param array $attr
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @param GFExcel_VendorHTMLPurifier_Context $context
     * @return array
     */
    public function transform($attr, $config, $context)
    {
        if (!isset($attr['name'])) {
            return $attr;
        }
        $name = $attr['name'];
        if (isset($attr['id']) && $attr['id'] === $name) {
            return $attr;
        }
        $result = $this->idDef->validate($name, $config, $context);
        if ($result === false) {
            unset($attr['name']);
        } else {
            $attr['name'] = $result;
        }
        return $attr;
    }
}

// vim: et sw=4 sts=4
