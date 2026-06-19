<?php
/**
 * @license proprietary?
 *
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace GFExcel\Container;

use GFExcel\Vendor\League\Container\ServiceProvider\BootableServiceProviderInterface;
use GFExcel\Vendor\League\Container\ServiceProvider\ServiceProviderInterface as LeagueServiceProviderInterface;

interface ServiceProviderInterface extends LeagueServiceProviderInterface, BootableServiceProviderInterface
{
}
