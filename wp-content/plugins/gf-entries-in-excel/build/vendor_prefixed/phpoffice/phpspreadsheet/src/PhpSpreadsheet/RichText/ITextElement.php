<?php
/**
 * @license MIT
 *
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace GFExcel\Vendor\PhpOffice\PhpSpreadsheet\RichText;

interface ITextElement
{
    /**
     * Get text.
     *
     * @return string Text
     */
    public function getText();

    /**
     * Set text.
     *
     * @param string $text Text
     *
     * @return ITextElement
     */
    public function setText($text);

    /**
     * Get font.
     *
     * @return null|\GFExcel\Vendor\PhpOffice\PhpSpreadsheet\Style\Font
     */
    public function getFont();

    /**
     * Get hash code.
     *
     * @return string Hash code
     */
    public function getHashCode();
}
