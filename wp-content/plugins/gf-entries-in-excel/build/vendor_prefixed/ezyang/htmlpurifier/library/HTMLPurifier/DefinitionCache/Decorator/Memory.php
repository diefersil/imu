<?php

/**
 * Definition cache decorator class that saves all cache retrievals
 * to PHP's memory; good for unit tests or circumstances where
 * there are lots of configuration objects floating around.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_DefinitionCache_Decorator_Memory extends GFExcel_VendorHTMLPurifier_DefinitionCache_Decorator
{
    /**
     * @type array
     */
    protected $definitions;

    /**
     * @type string
     */
    public $name = 'Memory';

    /**
     * @return GFExcel_VendorHTMLPurifier_DefinitionCache_Decorator_Memory
     */
    public function copy()
    {
        return new GFExcel_VendorHTMLPurifier_DefinitionCache_Decorator_Memory();
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Definition $def
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return mixed
     */
    public function add($def, $config)
    {
        $status = parent::add($def, $config);
        if ($status) {
            $this->definitions[$this->generateKey($config)] = $def;
        }
        return $status;
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Definition $def
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return mixed
     */
    public function set($def, $config)
    {
        $status = parent::set($def, $config);
        if ($status) {
            $this->definitions[$this->generateKey($config)] = $def;
        }
        return $status;
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Definition $def
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return mixed
     */
    public function replace($def, $config)
    {
        $status = parent::replace($def, $config);
        if ($status) {
            $this->definitions[$this->generateKey($config)] = $def;
        }
        return $status;
    }

    /**
     * @param GFExcel_VendorHTMLPurifier_Config $config
     * @return mixed
     */
    public function get($config)
    {
        $key = $this->generateKey($config);
        if (isset($this->definitions[$key])) {
            return $this->definitions[$key];
        }
        $this->definitions[$key] = parent::get($config);
        return $this->definitions[$key];
    }
}

// vim: et sw=4 sts=4
