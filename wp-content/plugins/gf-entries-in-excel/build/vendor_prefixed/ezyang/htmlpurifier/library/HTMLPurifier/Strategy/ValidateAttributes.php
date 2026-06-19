<?php

/**
 * Validate all attributes in the tokens.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */

class GFExcel_VendorHTMLPurifier_Strategy_ValidateAttributes extends GFExcel_VendorHTMLPurifier_Strategy
{

    /**
     * @param GFExcel_VendorHTMLPurifier_Token[] $tokens
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @param GFExcel_VendorHTMLPurifier_Context $context
     * @return GFExcel_VendorHTMLPurifier_Token[]
     */
    public function execute($tokens, $config, $context)
    {
        // setup validator
        $validator = new GFExcel_VendorHTMLPurifier_AttrValidator();

        $token = false;
        $context->register('CurrentToken', $token);

        foreach ($tokens as $key => $token) {

            // only process tokens that have attributes,
            //   namely start and empty tags
            if (!$token instanceof GFExcel_VendorHTMLPurifier_Token_Start && !$token instanceof GFExcel_VendorHTMLPurifier_Token_Empty) {
                continue;
            }

            // skip tokens that are armored
            if (!empty($token->armor['ValidateAttributes'])) {
                continue;
            }

            // note that we have no facilities here for removing tokens
            $validator->validateToken($token, $config, $context);
        }
        $context->destroy('CurrentToken');
        return $tokens;
    }
}

// vim: et sw=4 sts=4
