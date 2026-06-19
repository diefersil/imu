<?php

namespace Wpai\JetEngine;

use Jet_Engine\Modules\Maps_Listings\Module;

class PMJI_Map_Field extends \Wpai\AddonAPI\PMXI_Addon_Map_Field {

    public function getApiKey( $use_custom = false, $custom_key = null ) {
        $default_key = parent::getApiKey( $use_custom, $custom_key );

        if ( $use_custom || ! empty( $default_key ) ) {
            return $default_key;
        }

        $api_key = Module::instance()->settings->get( 'geocoding_key' );
        return $api_key ?? null;
    }

    private function generateHash( $fieldName ) {
        return md5( $fieldName );
    }

    private function importFields( $post_id, $type, $hash, $lat, $lng ) {
        $addon = PMJI_JetEngine_Addon::class;

        $addon::importField( $post_id, $type, $hash . '_hash', $hash );
        $addon::importField( $post_id, $type, $hash . '_lat', $lat );
        $addon::importField( $post_id, $type, $hash . '_lng', $lng );
    }

    public function beforeImport( $postId, $value, $data, $logger, $rawData ) {
        $return_value = parent::beforeImport( $postId, $value, $data, $logger, $rawData );

        if ( ! $return_value ) {
            return $return_value;
        }

        $hash     = $this->generateHash( $this->key );
        $format   = $this->args['map_value_format'] ?? '';
        $geometry = $return_value['geometry']['location'];

        // TODO: Is there a better place to put this?
        $this->importFields(
            $postId,
            $this->view->type,
            $hash,
            $geometry['lat'],
            $geometry['lng']
        );

        if ( $format === 'location_address' ) {
            return $this->formatValue( $return_value, 'address' );
        }

        if ( $format === 'location_array' ) {
            return json_encode( $this->formatValue( $return_value, 'array' ) );
        }

        return $this->formatValue( $return_value, 'string' );
    }
}