<?php

namespace Wpai\JetEngine;

class PMJI_CCT_Handler {

    use \Wpai\AddonAPI\Singleton;

    static function getModule() {
        if ( ! class_exists( '\Jet_Engine\Modules\Custom_Content_Types\Module' ) ) {
            return null;
        }

        return \Jet_Engine\Modules\Custom_Content_Types\Module::instance();
    }

    static function getCct( $slug ) {
        $slug   = str_replace( 'cct:', '', $slug );
        $module = self::getModule();

        if ( ! $module ) {
            return null;
        }

        return $module->manager->get_content_types( $slug );
    }


    public static function isCCT( $type ) {
        return str_starts_with( $type, 'cct:' );
    }

    public function getVisibleSections( $sections ) {
        $sectionsToRemove = [ 'main', 'other', 'caption', 'featured' ];

        foreach ( $sectionsToRemove as $sectionToRemove ) {
            $key = array_search( $sectionToRemove, $sections );

            if ( $key !== false ) {
                unset( $sections[ $key ] );
            }
        }

        return $sections;
    }

    public function getHiddenChooseDataToUpdateOptions( $options ) {
        $cctOptionsToRemove = [
            'is_update_title',
            'is_update_slug',
            'is_update_content',
            'is_update_excerpt',
            'is_update_menu_order',
            'is_update_ping_status',
            'is_update_parent',
            'is_update_comment_status',
            'is_update_attachments',
            'is_update_post_type',
            'is_update_images',
            'is_update_custom_fields',
            'is_update_taxonomies'
        ];

        return array_merge( $options, $cctOptionsToRemove );
    }

    public function getHiddenDeleteMissingOptions( $options ) {
        $cctDeleteOptionsToRemove = [
            'is_send_removed_to_trash',
            'is_update_missing_cf'
        ];

        return array_merge( $options, $cctDeleteOptionsToRemove );
    }

    public function getStatusOfRemovedOptions( $options ) {
        $cctStatusOfRemovedOptions = [
            'draft'   => 'Draft',
            'publish' => 'Published'
        ];

        return $cctStatusOfRemovedOptions;
    }

    public function shouldFirePostHooks( $should_fire, $type ) {
        return false;
    }

    public function supportsTitle( $supports, $type ) {
        return false;
    }

    public function isAccordionClosed( string $type, string $subtype = null ) {
        return true;
    }

    public function getCustomImporter() {
        return CCT_Data_Importer::class;
    }

    public function getCustomTypes() {
        $module = self::getModule();

        if ( ! $module ) {
            return [];
        }

        $types       = $module->manager->get_content_types();
        $customTypes = [];

        foreach ( $types as $slug => $type ) {
            $name         = "Jet CCT - " . $type->args['name'];
            $prefixedSlug = 'cct:' . $slug;

            $customTypes[ $prefixedSlug ] = (object) [
                'name'         => $name,
                'label'        => $name,
                'labels'       => (object) [
                    'name'          => $name,
                    'singular_name' => $name
                ],
                'hierarchical' => false
            ];
        }

        return $customTypes;
    }
}