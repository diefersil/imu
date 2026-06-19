<?php

/**
 * Decorator that, depending on a token, switches between two definitions.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_AttrDef_Switch
{

    /**
     * @type string
     */
    protected $tag;

    /**
     * @type GFExcel_VendorHTMLPurifier_AttrDef
     */
    protected $withTag;

    /**
     * @type GFExcel_VendorHTMLPurifier_AttrDef
     */
    protected $withoutTag;

    /**
     * @param string $tag Tag name to switch upon
     * @param GFExcel_VendorHTMLPurifier_AttrDef $with_tag Call if token matches tag
     * @param GFExcel_VendorHTMLPurifier_AttrDef $without_tag Call if token doesn't match, or there is no token
     */
    public function __construct($tag, $with_tag, $without_tag)
    {
        $this->tag = $tag;
        $this->withTag = $with_tag;
        $this->withoutTag = $without_tag;
    }

    /**
     * @param string $string
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @param GFExcel_VendorHTMLPurifier_Context $context
     * @return bool|string
     */
    public function validate($string, $config, $context)
    {
        $token = $context->get('CurrentToken', true);
        if (!$token || $token->name !== $this->tag) {
            return $this->withoutTag->validate($string, $config, $context);
        } else {
            return $this->withTag->validate($string, $config, $context);
        }
    }
}

// vim: et sw=4 sts=4
