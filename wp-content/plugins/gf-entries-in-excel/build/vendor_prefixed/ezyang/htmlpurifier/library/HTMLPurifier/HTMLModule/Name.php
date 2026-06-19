<?php
/**
 * @license LGPL-2.1-or-later
 *
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */

class GFExcel_VendorHTMLPurifier_HTMLModule_Name extends GFExcel_VendorHTMLPurifier_HTMLModule
{
    /**
     * @type string
     */
    public $name = 'Name';

    /**
     * @param GFExcel_VendorHTMLPurifier_Config $config
     */
    public function setup($config)
    {
        $elements = array('a', 'applet', 'form', 'frame', 'iframe', 'img', 'map');
        foreach ($elements as $name) {
            $element = $this->addBlankElement($name);
            $element->attr['name'] = 'CDATA';
            if (!$config->get('HTML.Attr.Name.UseCDATA')) {
                $element->attr_transform_post[] = new GFExcel_VendorHTMLPurifier_AttrTransform_NameSync();
            }
        }
    }
}

// vim: et sw=4 sts=4
