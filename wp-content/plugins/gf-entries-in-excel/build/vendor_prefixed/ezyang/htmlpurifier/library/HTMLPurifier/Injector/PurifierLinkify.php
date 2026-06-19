<?php

/**
 * Injector that converts configuration directive syntax %Namespace.Directive
 * to links
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_Injector_PurifierLinkify extends GFExcel_VendorHTMLPurifier_Injector
{
    /**
     * @type string
     */
    public $name = 'PurifierLinkify';

    /**
     * @type string
     */
    public $docURL;

    /**
     * @type array
     */
    public $needed = array('a' => array('href'));

    /**
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @param GFExcel_VendorHTMLPurifier_Context $context
     * @return string
     */
    public function prepare($config, $context)
    {
        $this->docURL = $config->get('AutoFormat.PurifierLinkify.DocURL');
        return parent::prepare($config, $context);
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Token $token
     */
    public function handleText(&$token)
    {
        if (!$this->allowsElement('a')) {
            return;
        }
        if (strpos($token->data, '%') === false) {
            return;
        }

        $bits = preg_split('#%([a-z0-9]+\.[a-z0-9]+)#Si', $token->data, -1, PREG_SPLIT_DELIM_CAPTURE);
        $token = array();

        // $i = index
        // $c = count
        // $l = is link
        for ($i = 0, $c = count($bits), $l = false; $i < $c; $i++, $l = !$l) {
            if (!$l) {
                if ($bits[$i] === '') {
                    continue;
                }
                $token[] = new GFExcel_VendorHTMLPurifier_Token_Text($bits[$i]);
            } else {
                $token[] = new GFExcel_VendorHTMLPurifier_Token_Start(
                    'a',
                    array('href' => str_replace('%s', $bits[$i], $this->docURL))
                );
                $token[] = new GFExcel_VendorHTMLPurifier_Token_Text('%' . $bits[$i]);
                $token[] = new GFExcel_VendorHTMLPurifier_Token_End('a');
            }
        }
    }
}

// vim: et sw=4 sts=4
