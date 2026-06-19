<?php

namespace Wpai\JetEngine;

use \Wpai\AddonAPI\Updatable;
use \Wpai\AddonAPI\PMXI_Addon_Base;

class PMJI_JetEngine_Addon extends PMXI_Addon_Base {
    use Updatable;

    public $slug = 'jetengine';
    public $version = PMJI_VERSION;

    public $edition = 'paid';

    public $rootDir = PMJI_ROOT_DIR;

    public $fields = [
        'post' => PMJI_Post_Field::class,
        'relation_options' => PMJI_Relation_Options_Field::class,
        'map' => PMJI_Map_Field::class
    ];

    public $casts = [
        'checkbox' => AsCheckbox::class,
        'repeater' => AsRepeater::class,
        'media'    => AsMedia::class,
        'gallery'  => AsGallery::class,
    ];

    private $cctHandler;
    private $relations;

    public function __construct() {
        parent::__construct();
        $this->cctHandler = PMJI_CCT_Handler::getInstance();
        $this->relations = PMJI_Relations::getInstance();
    }

    public function name(): string {
        return __( 'JetEngine Add-On', 'wp_all_import_jetengine_add_on' );
    }

    public function description(): string {
        return __( 'Import data from JetEngine', 'wp_all_import_jetengine_add_on' );
    }

    public function getEddName() {
        return 'JetEngine Import Add-On Pro';
    }

    public function canRun() {
        if ( ! class_exists( 'Jet_Engine' ) ) {
            return $this->getMissingDependencyError( 'JetEngine', 'https://crocoblock.com/plugins/jetengine' );
        }

        return true;
    }

    public function availableForTypes() {
        return [ '-comments' ];
    }

    public function getCustomTypes() {
        return $this->cctHandler->getCustomTypes();
    }

    public function getVisibleSections( $sections, $type ) {
        if ( $this->cctHandler->isCCT( $type ) ) {
            return $this->cctHandler->getVisibleSections( $sections );
        }

        return $sections;
    }

    public function getHiddenChooseDataToUpdateOptions( $options, $type ) {
        if ( $this->cctHandler->isCCT( $type ) ) {
            return $this->cctHandler->getHiddenChooseDataToUpdateOptions( $options );
        }

        return $options;
    }

    public function getHiddenDeleteMissingOptions( $options, $type ) {
        if ( $this->cctHandler->isCCT( $type ) ) {
            return $this->cctHandler->getHiddenDeleteMissingOptions( $options );
        }

        return $options;
    }

    public function getStatusOfRemovedOptions( $options, $type ) {
        if ( $this->cctHandler->isCCT( $type ) ) {
            return $this->cctHandler->getStatusOfRemovedOptions( $options );
        }

        return $options;
    }

    public function shouldFirePostHooks( $should_fire, $type ) {
        if ( $this->cctHandler->isCCT( $type ) ) {
            return false;
        }

        return $should_fire;
    }

    public function supportsTitle( $supports, $type ) {
        if ( $this->cctHandler->isCCT( $type ) ) {
            return false;
        }

        return $supports;
    }

    public function isAccordionClosed( string $type, string $subtype = null ) {
        if ( $this->cctHandler->isCCT( $type ) ) {
            return true;
        }

        return false;
    }

    public function getCustomImporter( $options ) {
        if ( $this->cctHandler->isCCT( $options['custom_type'] ) ) {
            return $this->cctHandler->getCustomImporter();
        }

        return null;
    }

    public static function fields( string $type, string $subtype = null ) {
        return PMJI_Fields::fields( $type, $subtype );
    }

    public static function groups( string $type, string $subtype = null ) {
        return PMJI_Fields::groups( $type, $subtype );
    }

    public static function importCCTField(
        int $id,
        string $type,
        string $name,
        $value
    ) {
        $cct     = PMJI_CCT_Handler::getCct( $type );
        $handler = $cct->get_item_handler();

        // Reset found items cache
        $handler->get_factory()->db->_found_items = [];

        $handler->update_item( [
            '_ID' => $id,
            $name => $value
        ] );
    }

    public static function importField(
        int $id,
        string $type,
        string $name,
        $value
    ) {
        $fns = [
            'import_users'  => 'update_user_meta',
            'shop_customer' => 'update_user_meta',
            'taxonomies'    => 'update_term_meta',
        ];

        $fn = $fns[ $type ] ?? 'update_post_meta';

        call_user_func( $fn, $id, $name, $value );
    }

    public static function import(
        int $id,
        array $fields,
        array $values,
        \PMXI_Import_Record $record,
        array $post,
        $logger
    ) {
        $type    = $record->options['custom_type'];
        $subtype = $record->options['taxonomy_type'];

        $relations = PMJI_Relations::getInstance();
        $relations->import( $id, $type, $subtype, $fields, $values );

        foreach ( $fields as $field ) {
            $name  = $field['key'];
            $value = $values[ $name ] ?? null;

            if ( $relations->isRelationField( $field ) ) {
                continue;
            }

            if ( PMJI_CCT_Handler::isCCT( $type ) ) {
                self::importCCTField( $id, $type, $name, $value );
            } else {
                self::importField( $id, $type, $name, $value );
            }

            call_user_func( $logger, "- Importing field `$name`" );
        }
    }
}
