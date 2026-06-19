<?php

/**
 * Implements safety checks for safe iframes.
 *
 * @warning This filter is *critical* for ensuring that %HTML.SafeIframe
 * works safely.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_URIFilter_SafeIframe extends GFExcel_VendorHTMLPurifier_URIFilter
{
    /**
     * @type string
     */
    public $name = 'SafeIframe';

    /**
     * @type bool
     */
    public $always_load = true;

    /**
     * @type string
     */
    protected $regexp = null;

    // XXX: The not so good bit about how this is all set up now is we
    // can't check HTML.SafeIframe in the 'prepare' step: we have to
    // defer till the actual filtering.
    /**
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return bool
     */
    public function prepare($config)
    {
        $this->regexp = $config->get('URI.SafeIframeRegexp');
        return true;
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_URI $uri
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @param GFExcel_VendorHTMLPurifier_Context $context
     * @return bool
     */
    public function filter(&$uri, $config, $context)
    {
        // check if filter not applicable
        if (!$config->get('HTML.SafeIframe')) {
            return true;
        }
        // check if the filter should actually trigger
        if (!$context->get('EmbeddedURI', true)) {
            return true;
        }
        $token = $context->get('CurrentToken', true);
        if (!($token && $token->name == 'iframe')) {
            return true;
        }
        // check if we actually have some whitelists enabled
        if ($this->regexp === null) {
            return false;
        }
        // actually check the whitelists
        return preg_match($this->regexp, $uri->toString());
    }
}

// vim: et sw=4 sts=4
