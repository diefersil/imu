<?php
/**
 * @license LGPL-2.1-or-later
 *
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */

class GFExcel_VendorHTMLPurifier_DefinitionCache_Decorator extends GFExcel_VendorHTMLPurifier_DefinitionCache
{

    /**
     * Cache object we are decorating
     * @type GFExcel_VendorHTMLPurifier_DefinitionCache
     */
    public $cache;

    /**
     * The name of the decorator
     * @var string
     */
    public $name;

    public function __construct()
    {
    }

    /**
     * Lazy decorator function
     * @param GFExcel_VendorHTMLPurifier_DefinitionCache $cache Reference to cache object to decorate
     * @return GFExcel_VendorHTMLPurifier_DefinitionCache_Decorator
     */
    public function decorate(&$cache)
    {
        $decorator = $this->copy();
        // reference is necessary for mocks in PHP 4
        $decorator->cache =& $cache;
        $decorator->type = $cache->type;
        return $decorator;
    }

    /**
     * Cross-compatible clone substitute
     * @return GFExcel_VendorHTMLPurifier_DefinitionCache_Decorator
     */
    public function copy()
    {
        return new GFExcel_VendorHTMLPurifier_DefinitionCache_Decorator();
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Definition $def
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return mixed
     */
    public function add($def, $config)
    {
        return $this->cache->add($def, $config);
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Definition $def
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return mixed
     */
    public function set($def, $config)
    {
        return $this->cache->set($def, $config);
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Definition $def
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return mixed
     */
    public function replace($def, $config)
    {
        return $this->cache->replace($def, $config);
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return mixed
     */
    public function get($config)
    {
        return $this->cache->get($config);
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return mixed
     */
    public function remove($config)
    {
        return $this->cache->remove($config);
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return mixed
     */
    public function flush($config)
    {
        return $this->cache->flush($config);
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return mixed
     */
    public function cleanup($config)
    {
        return $this->cache->cleanup($config);
    }
}

// vim: et sw=4 sts=4
