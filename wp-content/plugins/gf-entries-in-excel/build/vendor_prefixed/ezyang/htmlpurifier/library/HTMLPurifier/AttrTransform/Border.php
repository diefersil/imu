<?php

/**
 * Pre-transform that changes deprecated border attribute to CSS.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_AttrTransform_Border extends GFExcel_VendorHTMLPurifier_AttrTransform
{
    /**
     * @param array $attr
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @param GFExcel_VendorHTMLPurifier_Context $context
     * @return array
     */
    public function transform($attr, $config, $context)
    {
        if (!isset($attr['border'])) {
            return $attr;
        }
        $border_width = $this->confiscateAttr($attr, 'border');
        // some validation should happen here
        $this->prependCSS($attr, "border:{$border_width}px solid;");
        return $attr;
    }
}

// vim: et sw=4 sts=4
