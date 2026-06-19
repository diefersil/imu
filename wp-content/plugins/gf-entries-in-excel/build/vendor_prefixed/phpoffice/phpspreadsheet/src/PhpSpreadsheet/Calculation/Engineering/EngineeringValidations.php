<?php
/**
 * @license MIT
 *
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace GFExcel\Vendor\PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use GFExcel\Vendor\PhpOffice\PhpSpreadsheet\Calculation\Exception;
use GFExcel\Vendor\PhpOffice\PhpSpreadsheet\Calculation\Functions;

class EngineeringValidations
{
    /**
     * @param mixed $value
     */
    public static function validateFloat($value): float
    {
        if (!is_numeric($value)) {
            throw new Exception(Functions::VALUE());
        }

        return (float) $value;
    }

    /**
     * @param mixed $value
     */
    public static function validateInt($value): int
    {
        if (!is_numeric($value)) {
            throw new Exception(Functions::VALUE());
        }

        return (int) floor((float) $value);
    }
}
