<?php

namespace Wpai\JetEngine;

class PMJI_Post_Field extends \Wpai\AddonAPI\PMXI_Addon_Post_Field {

    public function getRelation( $id ) {
        $rel = jet_engine()->relations->get_active_relations( $id );

        if ( ! $rel ) {
            return null;
        }

        return PMJI_Relations::getInstance()->prepareRelation( $rel->get_args() );
    }

    public function beforeImport( $postId, $value, $data, $logger, $rawData ) {
        $values    = $this->parseDelimitedValues( $value );
        $ids       = [];
        $processed = [];

        $relation  = $this->getRelation( $this->args['relation_id'] ) ?? [];
        $cct_types = $this->getCctTypes();

        foreach ( $cct_types as $type ) {
            $handler = PMJI_CCT_Handler::getCct( $type );

            foreach ( $values as $ev ) {
                if ( in_array( $ev, $processed ) ) {
                    continue;
                }

                $search_by = $this->getSearchByFields( $ev, $handler, $relation, $type );

                if ( empty( $search_by ) ) {
                    continue;
                }

                $rows = $handler->db->query( [
                    '_cct_search' => [
                        'keyword' => $ev,
                        'fields'  => $search_by,
                    ]
                ] );

                if ( isset( $rows[0] ) ) {
                    $ids[]       = $rows[0]['_ID'];
                    $processed[] = $ev;
                }
            }
        }

        if ( $this->multiple && ! empty( $ids ) ) {
            return array_shift( $ids );
        }

        // Fall back to default behavior
        $id_or_ids = parent::beforeImport( $postId, $value, $data, $logger, $rawData );

        // If the relation is not multiple, return the first ID
        if ( ! $this->multiple ) {
			// Account for CCTs.
	        return $ids[0] ?? $id_or_ids;
        }

        return array_merge( $ids, $id_or_ids );
    }

    private function getSearchByFields( $ev, $handler, $relation, $type ) {
        $search_by = [];

        if ( ctype_digit( $ev ) ) {
            $search_by[] = '_ID';
        } else {
            $factory     = $handler->get_item_handler()->get_factory();
            $title_field = $relation['cct'][ 'cct::' . $type ]['title_field'] ?? $factory->get_arg( 'related_post_type_title' );

            if ( isset( $title_field ) ) {
                $search_by[] = $title_field;
            }
        }

        return $search_by;
    }

    public function getCctTypes() {
        $types = $this->args['search_post_type'];

        $cct_types = array_map(
            fn( $type ) => str_replace( 'cct::', '', $type ),
            array_filter(
                $types,
                fn( $type ) => str_starts_with( $type, 'cct::' )
            )
        );

        return $cct_types;
    }

}