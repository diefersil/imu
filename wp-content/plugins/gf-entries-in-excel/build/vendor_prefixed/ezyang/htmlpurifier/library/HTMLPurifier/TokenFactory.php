<?php

/**
 * Factory for token generation.
 *
 * @note Doing some benchmarking indicates that the new operator is much
 *       slower than the clone operator (even discounting the cost of the
 *       constructor).  This class is for that optimization.
 *       Other then that, there's not much point as we don't
 *       maintain parallel GFExcel_VendorHTMLPurifier_Token hierarchies (the main reason why
 *       you'd want to use an abstract factory).
 * @todo Port DirectLex to use this
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_TokenFactory
{
    // p stands for prototype

    /**
     * @type GFExcel_VendorHTMLPurifier_Token_Start
     */
    private $p_start;

    /**
     * @type GFExcel_VendorHTMLPurifier_Token_End
     */
    private $p_end;

    /**
     * @type GFExcel_VendorHTMLPurifier_Token_Empty
     */
    private $p_empty;

    /**
     * @type GFExcel_VendorHTMLPurifier_Token_Text
     */
    private $p_text;

    /**
     * @type GFExcel_VendorHTMLPurifier_Token_Comment
     */
    private $p_comment;

    /**
     * Generates blank prototypes for cloning.
     */
    public function __construct()
    {
        $this->p_start = new GFExcel_VendorHTMLPurifier_Token_Start('', array());
        $this->p_end = new GFExcel_VendorHTMLPurifier_Token_End('');
        $this->p_empty = new GFExcel_VendorHTMLPurifier_Token_Empty('', array());
        $this->p_text = new GFExcel_VendorHTMLPurifier_Token_Text('');
        $this->p_comment = new GFExcel_VendorHTMLPurifier_Token_Comment('');
    }

    /**
     * Creates a GFExcel_VendorHTMLPurifier_Token_Start.
     * @param string $name Tag name
     * @param array $attr Associative array of attributes
     * @return GFExcel_VendorHTMLPurifier_Token_Start Generated GFExcel_VendorHTMLPurifier_Token_Start
     */
    public function createStart($name, $attr = array())
    {
        $p = clone $this->p_start;
        $p->__construct($name, $attr);
        return $p;
    }

    /**
     * Creates a GFExcel_VendorHTMLPurifier_Token_End.
     * @param string $name Tag name
     * @return GFExcel_VendorHTMLPurifier_Token_End Generated GFExcel_VendorHTMLPurifier_Token_End
     */
    public function createEnd($name)
    {
        $p = clone $this->p_end;
        $p->__construct($name);
        return $p;
    }

    /**
     * Creates a GFExcel_VendorHTMLPurifier_Token_Empty.
     * @param string $name Tag name
     * @param array $attr Associative array of attributes
     * @return GFExcel_VendorHTMLPurifier_Token_Empty Generated GFExcel_VendorHTMLPurifier_Token_Empty
     */
    public function createEmpty($name, $attr = array())
    {
        $p = clone $this->p_empty;
        $p->__construct($name, $attr);
        return $p;
    }

    /**
     * Creates a GFExcel_VendorHTMLPurifier_Token_Text.
     * @param string $data Data of text token
     * @return GFExcel_VendorHTMLPurifier_Token_Text Generated GFExcel_VendorHTMLPurifier_Token_Text
     */
    public function createText($data)
    {
        $p = clone $this->p_text;
        $p->__construct($data);
        return $p;
    }

    /**
     * Creates a GFExcel_VendorHTMLPurifier_Token_Comment.
     * @param string $data Data of comment token
     * @return GFExcel_VendorHTMLPurifier_Token_Comment Generated GFExcel_VendorHTMLPurifier_Token_Comment
     */
    public function createComment($data)
    {
        $p = clone $this->p_comment;
        $p->__construct($data);
        return $p;
    }
}

// vim: et sw=4 sts=4
