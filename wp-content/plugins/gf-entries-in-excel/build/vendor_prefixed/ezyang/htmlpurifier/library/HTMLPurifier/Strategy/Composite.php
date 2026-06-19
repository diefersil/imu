<?php

/**
 * Composite strategy that runs multiple strategies on tokens.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
abstract class GFExcel_VendorHTMLPurifier_Strategy_Composite extends GFExcel_VendorHTMLPurifier_Strategy
{

    /**
     * List of strategies to run tokens through.
     * @type GFExcel_VendorHTMLPurifier_Strategy[]
     */
    protected $strategies = array();

    /**
     * @param GFExcel_VendorHTMLPurifier_Token[] $tokens
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @param GFExcel_VendorHTMLPurifier_Context $context
     * @return GFExcel_VendorHTMLPurifier_Token[]
     */
    public function execute($tokens, $config, $context)
    {
        foreach ($this->strategies as $strategy) {
            $tokens = $strategy->execute($tokens, $config, $context);
        }
        return $tokens;
    }
}

// vim: et sw=4 sts=4
