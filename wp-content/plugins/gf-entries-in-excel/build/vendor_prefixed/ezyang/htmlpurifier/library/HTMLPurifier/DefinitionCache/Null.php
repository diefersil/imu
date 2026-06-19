<?php

/**
 * Null cache object to use when no caching is on.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_DefinitionCache_Null extends GFExcel_VendorHTMLPurifier_DefinitionCache
{

    /**
     * @param GFExcel_VendorHTMLPurifier_Definition $def
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return bool
     */
    public function add($def, $config)
    {
        return false;
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Definition $def
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return bool
     */
    public function set($def, $config)
    {
        return false;
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Definition $def
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return bool
     */
    public function replace($def, $config)
    {
        return false;
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return bool
     */
    public function remove($config)
    {
        return false;
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return bool
     */
    public function get($config)
    {
        return false;
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return bool
     */
    public function flush($config)
    {
        return false;
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return bool
     */
    public function cleanup($config)
    {
        return false;
    }
}

// vim: et sw=4 sts=4
