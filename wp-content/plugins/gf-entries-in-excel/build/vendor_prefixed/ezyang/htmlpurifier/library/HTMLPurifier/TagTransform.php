<?php

/**
 * Defines a mutation of an obsolete tag into a valid tag.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
abstract class GFExcel_VendorHTMLPurifier_TagTransform
{

    /**
     * Tag name to transform the tag to.
     * @type string
     */
    public $transform_to;

    /**
     * Transforms the obsolete tag into the valid tag.
     * @param GFExcel_VendorHTMLPurifier_Token_Tag $tag Tag to be transformed.
     * @param GFExcel_VendorHTMLPurifier_Config $config Mandatory GFExcel_VendorHTMLPurifier_Config object
     * @param GFExcel_VendorHTMLPurifier_Context $context Mandatory GFExcel_VendorHTMLPurifier_Context object
     */
    abstract public function transform($tag, $config, $context);

    /**
     * Prepends CSS properties to the style attribute, creating the
     * attribute if it doesn't exist.
     * @warning Copied over from AttrTransform, be sure to keep in sync
     * @param array $attr Attribute array to process (passed by reference)
     * @param string $css CSS to prepend
     */
    protected function prependCSS(&$attr, $css)
    {
        $attr['style'] = isset($attr['style']) ? $attr['style'] : '';
        $attr['style'] = $css . $attr['style'];
    }
}

// vim: et sw=4 sts=4
