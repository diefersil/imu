<?php
/**
 * @license MIT
 *
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace GFExcel\Vendor\PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use GFExcel\Vendor\PhpOffice\PhpSpreadsheet\Calculation\Exception;
use GFExcel\Vendor\PhpOffice\PhpSpreadsheet\Calculation\Functions;

class Confidence
{
    /**
     * CONFIDENCE.
     *
     * Returns the confidence interval for a population mean
     *
     * @param mixed $alpha As a float
     * @param mixed $stdDev Standard Deviation as a float
     * @param mixed $size As an integer
     *
     * @return float|string
     */
    public static function CONFIDENCE($alpha, $stdDev, $size)
    {
        $alpha = Functions::flattenSingleValue($alpha);
        $stdDev = Functions::flattenSingleValue($stdDev);
        $size = Functions::flattenSingleValue($size);

        try {
            $alpha = StatisticalValidations::validateFloat($alpha);
            $stdDev = StatisticalValidations::validateFloat($stdDev);
            $size = StatisticalValidations::validateInt($size);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($alpha <= 0) || ($alpha >= 1) || ($stdDev <= 0) || ($size < 1)) {
            return Functions::NAN();
        }

        return Distributions\StandardNormal::inverse(1 - $alpha / 2) * $stdDev / sqrt($size);
    }
}
