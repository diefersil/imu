<?php

/**
 * Primitive email validation class based on the regexp found at
 * http://www.regular-expressions.info/email.html
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_AttrDef_URI_Email_SimpleCheck extends GFExcel_VendorHTMLPurifier_AttrDef_URI_Email
{

    /**
     * @param string $string
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @param GFExcel_VendorHTMLPurifier_Context $context
     * @return bool|string
     */
    public function validate($string, $config, $context)
    {
        // no support for named mailboxes i.e. "Bob <bob@example.com>"
        // that needs more percent encoding to be done
        if ($string == '') {
            return false;
        }
        $string = trim($string);
        $result = preg_match('/^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $string);
        return $result ? $string : false;
    }
}

// vim: et sw=4 sts=4
