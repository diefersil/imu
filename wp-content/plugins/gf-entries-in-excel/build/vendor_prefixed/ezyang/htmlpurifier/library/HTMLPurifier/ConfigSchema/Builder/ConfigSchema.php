<?php

/**
 * Converts GFExcel_VendorHTMLPurifier_ConfigSchema_Interchange to our runtime
 * representation used to perform checks on user configuration.
 *
 * @license LGPL-2.1-or-later
 * Modified by GravityKit using {@see https://github.com/BrianHenryIE/strauss}.
 */
class GFExcel_VendorHTMLPurifier_ConfigSchema_Builder_ConfigSchema
{

    /**
     * @param GFExcel_VendorHTMLPurifier_ConfigSchema_Interchange $interchange
     * @return GFExcel_VendorHTMLPurifier_ConfigSchema
     */
    public function build($interchange)
    {
        $schema = new GFExcel_VendorHTMLPurifier_ConfigSchema();
        foreach ($interchange->directives as $d) {
            $schema->add(
                $d->id->key,
                $d->default,
                $d->type,
                $d->typeAllowsNull
            );
            if ($d->allowed !== null) {
                $schema->addAllowedValues(
                    $d->id->key,
                    $d->allowed
                );
            }
            foreach ($d->aliases as $alias) {
                $schema->addAlias(
                    $alias->key,
                    $d->id->key
                );
            }
            if ($d->valueAliases !== null) {
                $schema->addValueAliases(
                    $d->id->key,
                    $d->valueAliases
                );
            }
        }
        $schema->postProcess();
        return $schema;
    }
}

// vim: et sw=4 sts=4
