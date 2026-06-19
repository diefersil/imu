<?php
/**
 * @license MIT
 *
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
declare(strict_types=1);

namespace GFExcel\Vendor\ZipStream\Option;

use GFExcel\Vendor\MyCLabs\Enum\Enum;

/**
 * Methods enum
 *
 * @method static STORE(): Method
 * @method static DEFLATE(): Method
 * @psalm-immutable
 */
class Method extends Enum
{
    const STORE = 0x00;
    const DEFLATE = 0x08;
}
