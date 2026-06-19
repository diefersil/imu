<?php

/**
 * Concrete text token class.
 *
 * Text tokens comprise of regular parsed character data (PCDATA) and raw
 * character data (from the CDATA sections). Internally, their
 * data is parsed with all entities expanded. Surprisingly, the text token
 * does have a "tag name" called #PCDATA, which is how the DTD represents it
 * in permissible child nodes.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_Node_Text extends GFExcel_VendorHTMLPurifier_Node
{

    /**
     * PCDATA tag name compatible with DTD, see
     * GFExcel_VendorHTMLPurifier_ChildDef_Custom for details.
     * @type string
     */
    public $name = '#PCDATA';

    /**
     * @type string
     */
    public $data;
    /**< Parsed character data of text. */

    /**
     * @type bool
     */
    public $is_whitespace;

    /**< Bool indicating if node is whitespace. */

    /**
     * Constructor, accepts data and determines if it is whitespace.
     * @param string $data String parsed character data.
     * @param int $line
     * @param int $col
     */
    public function __construct($data, $is_whitespace, $line = null, $col = null)
    {
        $this->data = $data;
        $this->is_whitespace = $is_whitespace;
        $this->line = $line;
        $this->col = $col;
    }

    public function toTokenPair() {
        return array(new GFExcel_VendorHTMLPurifier_Token_Text($this->data, $this->line, $this->col), null);
    }
}

// vim: et sw=4 sts=4
