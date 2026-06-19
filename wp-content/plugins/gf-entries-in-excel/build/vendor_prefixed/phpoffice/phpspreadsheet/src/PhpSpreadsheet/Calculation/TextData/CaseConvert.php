<?php
/**
 * @license MIT
 *
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace GFExcel\Vendor\PhpOffice\PhpSpreadsheet\Calculation\TextData;

use GFExcel\Vendor\PhpOffice\PhpSpreadsheet\Calculation\Functions;
use GFExcel\Vendor\PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class CaseConvert
{
    /**
     * LOWERCASE.
     *
     * Converts a string value to upper case.
     *
     * @param mixed $mixedCaseValue The string value to convert to lower case
     */
    public static function lower($mixedCaseValue): string
    {
        $mixedCaseValue = Functions::flattenSingleValue($mixedCaseValue);
        $mixedCaseValue = Helpers::extractString($mixedCaseValue);

        return StringHelper::strToLower($mixedCaseValue);
    }

    /**
     * UPPERCASE.
     *
     * Converts a string value to upper case.
     *
     * @param mixed $mixedCaseValue The string value to convert to upper case
     */
    public static function upper($mixedCaseValue): string
    {
        $mixedCaseValue = Functions::flattenSingleValue($mixedCaseValue);
        $mixedCaseValue = Helpers::extractString($mixedCaseValue);

        return StringHelper::strToUpper($mixedCaseValue);
    }

    /**
     * PROPERCASE.
     *
     * Converts a string value to proper or title case.
     *
     * @param mixed $mixedCaseValue The string value to convert to title case
     */
    public static function proper($mixedCaseValue): string
    {
        $mixedCaseValue = Functions::flattenSingleValue($mixedCaseValue);
        $mixedCaseValue = Helpers::extractString($mixedCaseValue);

        return StringHelper::strToTitle($mixedCaseValue);
    }
}
