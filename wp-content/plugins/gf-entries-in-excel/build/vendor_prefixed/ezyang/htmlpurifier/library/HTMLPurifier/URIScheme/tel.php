<?php

/**
 * Validates tel (for phone numbers).
 *
 * The relevant specifications for this protocol are RFC 3966 and RFC 5341,
 * but this class takes a much simpler approach: we normalize phone
 * numbers so that they only include (possibly) a leading plus,
 * and then any number of digits and x'es.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */

class GFExcel_VendorHTMLPurifier_URIScheme_tel extends GFExcel_VendorHTMLPurifier_URIScheme
{
    /**
     * @type bool
     */
    public $browsable = false;

    /**
     * @type bool
     */
    public $may_omit_host = true;

    /**
     * @param GFExcel_VendorHTMLPurifier_URI $uri
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @param GFExcel_VendorHTMLPurifier_Context $context
     * @return bool
     */
    public function doValidate(&$uri, $config, $context)
    {
        $uri->userinfo = null;
        $uri->host     = null;
        $uri->port     = null;

        // Delete all non-numeric characters, commas, and non-x characters
        // from phone number, EXCEPT for a leading plus sign.
        $uri->path = preg_replace('/(?!^\+)[^\dx,]/', '',
                     // Normalize e(x)tension to lower-case
                     str_replace('X', 'x', rawurldecode($uri->path)));

        return true;
    }
}

// vim: et sw=4 sts=4
