<?php
/**
 * @license MIT
 *
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace GFExcel\Vendor\PhpOffice\PhpSpreadsheet\Writer\Ods;

class Mimetype extends WriterPart
{
    /**
     * Write mimetype to plain text format.
     *
     * @return string XML Output
     */
    public function write(): string
    {
        return 'application/vnd.oasis.opendocument.spreadsheet';
    }
}
