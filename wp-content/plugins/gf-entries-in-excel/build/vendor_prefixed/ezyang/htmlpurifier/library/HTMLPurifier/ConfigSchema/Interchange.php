<?php

/**
 * Generic schema interchange format that can be converted to a runtime
 * representation (GFExcel_VendorHTMLPurifier_ConfigSchema) or HTML documentation. Members
 * are completely validated.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_ConfigSchema_Interchange
{

    /**
     * Name of the application this schema is describing.
     * @type string
     */
    public $name;

    /**
     * Array of Directive ID => array(directive info)
     * @type GFExcel_VendorHTMLPurifier_ConfigSchema_Interchange_Directive[]
     */
    public $directives = array();

    /**
     * Adds a directive array to $directives
     * @param GFExcel_VendorHTMLPurifier_ConfigSchema_Interchange_Directive $directive
     * @throws GFExcel_VendorHTMLPurifier_ConfigSchema_Exception
     */
    public function addDirective($directive)
    {
        if (isset($this->directives[$i = $directive->id->toString()])) {
            throw new GFExcel_VendorHTMLPurifier_ConfigSchema_Exception("Cannot redefine directive '$i'");
        }
        $this->directives[$i] = $directive;
    }

    /**
     * Convenience function to perform standard validation. Throws exception
     * on failed validation.
     */
    public function validate()
    {
        $validator = new GFExcel_VendorHTMLPurifier_ConfigSchema_Validator();
        return $validator->validate($this);
    }
}

// vim: et sw=4 sts=4
