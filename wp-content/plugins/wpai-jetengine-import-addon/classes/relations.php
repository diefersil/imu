<?php

namespace Wpai\JetEngine;

class PMJI_Relations {

    use \Wpai\AddonAPI\Singleton;

    /**
     * @param array $relation
     * @param string $field_key
     *
     * @return string
     */
    public function buildFieldKey( $relation, $field_key ) {
        return "jet_engine_rel_{$relation['id']}_{$relation['control_type']}_{$field_key}";
    }

    /**
     * @param $field
     *
     * @return bool
     */
    public function isRelationField( $field ) {
        return $field['group'] == '_jet_engine_relations' && ! empty( $field['args']['relation_id'] );
    }

    /**
     * @param int $current_object_id
     * @param string $type
     * @param string $subtype
     * @param $fields
     * @param $values
     *
     * @return void
     */
    public function import( int $current_object_id, string $type, string $subtype, $fields, $values ) {
        $relations = $this->getRelations( $type, $subtype );

        foreach ( $relations as $relation ) {
            $rel                 = jet_engine()->relations->get_active_relations( $relation['id'] );
            $meta                = [];
            $options_key         = $this->buildFieldKey( $relation, 'options' );
            $options             = $values[ $options_key ] ?? [];
            $keep_existing       = $options['keep_existing_relations'] ?? false;
            $is_parent_processed = $relation['control_type'] === 'parent';
            $is_single           = $is_parent_processed ? $rel->is_single_parent() : $rel->is_single_child();

            if ( $is_single ) {
                $key               = $this->buildFieldKey( $relation, 'related_object' );
                $related_object_id = $values[ $key ] ?? null;

                foreach ( $relation['args']['meta_fields'] as $field ) {
                    $key                    = $this->buildFieldKey( $relation, $field['name'] );
                    $meta[ $field['name'] ] = $values[ $key ];
                }

                $this->updateRelation( $rel, $current_object_id, $related_object_id, $meta, $is_parent_processed );
            } else {
                if ( ! $keep_existing ) {
                    $related_list = $is_parent_processed ? $rel->get_parents( $current_object_id ) : $rel->get_children( $current_object_id );

                    foreach ( $related_list as $item ) {
                        if ( $is_parent_processed ) {
                            $item_id    = $item['parent_object_id'];
                            $current_id = $item['child_object_id'];
                        } else {
                            $item_id    = $item['child_object_id'];
                            $current_id = $item['parent_object_id'];
                        }

                        $rel->delete_rows( $item_id, $current_id );
                    }
                }

                $meta_key = $this->buildFieldKey( $relation, 'meta_fields' );
                $items    = array_values( $values[ $meta_key ] ?? [] );

                foreach ( $items as $item ) {
                    $related_object_id = $item['related_object'];
                    $meta              = $item;
                    unset( $meta["related_object"] );

                    $this->updateRelation( $rel, $current_object_id, $related_object_id, $meta, $is_parent_processed );
                }
            }
        }
    }

    public function prepareRelation( $item ) {
        if ( ! empty( $item['is_legacy'] ) ) {
            $item['name'] = $item['args']['name'];
        } else {
            $id       = $item['id'];
            $relation = jet_engine()->relations->get_active_relations( $id );

            if ( ! $relation ) {
                return false;
            }

            $name         = $item['labels']['name'] ?? $relation->get_relation_name();
            $item['name'] = $name;
        }

        return $item;
    }

    /**
     * @param string $type
     * @param string|null $subtype
     *
     * @return array
     */
    public function getRelations( $type, $subtype = null ) {
        $items       = jet_engine()->relations->data->get_item_for_register();
        $object_type = $this->getRelationObjectType( $type, $subtype );

        $child_items = array_filter( $items, function ( $metabox ) use ( $object_type ) {
            return $object_type === $metabox['args']['parent_object'];
        } );

        $child_items = array_map( function ( $metabox ) {
            $metabox['control_type'] = 'child';

            return $metabox;
        }, $child_items );

        $parent_items = array_filter( $items, function ( $metabox ) use ( $object_type ) {
            return $object_type === $metabox['args']['child_object'];
        } );

        $parent_items = array_map( function ( $metabox ) {
            $metabox['control_type'] = 'parent';

            return $metabox;
        }, $parent_items );

        $items = array_merge( $parent_items, $child_items );

        return array_filter(
            array_map( [ $this, 'prepareRelation' ], $items )
        );
    }

    /**
     * @param string $type
     * @param string|null $subtype
     *
     * @return string
     */
    public function getRelationObjectType( $type, $subtype = null ) {
        $object_types = [
            'import_users'  => 'mix::users',
            'shop_customer' => 'mix::users',
            'taxonomies'    => 'terms::' . $subtype,
        ];

        if ( str_starts_with( $type, 'cct::' ) ) {
            return $type;
        }

        return $object_types[ $type ] ?? 'posts::' . $type;
    }

    /**
     * @param array $field
     * @param string $group_id
     * @param array $relation
     * @param boolean $is_single
     *
     * @return array
     */
    public function prepareField( $field, $group_id, $relation, $is_single ) {
        $field = PMJI_Fields::prepareField( $field, $group_id );

        if ( $is_single ) {
            $field['key'] = $this->buildFieldKey( $relation, $field['key'] );
        }

        return $field;
    }

    /**
     * @param string $type
     * @param string|null $subtype
     *
     * @return array
     */
    public function getRelationFields( $type, $subtype = null ) {
        $relations = $this->getRelations( $type, $subtype );
        $fields    = [];

        foreach ( $relations as $relation ) {
            $rel                  = jet_engine()->relations->get_active_relations( $relation['id'] );
            $group_id             = '_jet_engine_relations';
            $is_single            = $relation['control_type'] === 'parent' ? $rel->is_single_parent() : $rel->is_single_child();
            $meta_fields          = $this->getMetaFields( $relation, $group_id, $is_single );
            $from_object          = $this->getObjectData( $relation );
            $single_label         = jet_engine()->relations->types_helper->get_type_label( 'single', $from_object['object_type'], $from_object['object'] );
            $label                = ($relation['control_type'] === 'parent' ? sprintf( "Parent %s", $single_label ) : sprintf( "Child %s", $single_label )) . ' | '.($relation['name'] ?? '');
            $search_post_type     = $from_object['object_type'] === 'cct' ? $from_object['raw'] : $from_object['object'];
            $related_object_field = $this->getRelatedObjectField( $relation, $single_label, $is_single, $group_id, $search_post_type );

            $fields[] = [
                'key'   => $relation['name'],
                'type'  => 'separator',
                'group' => $group_id,
                'label' => $label
            ];

            if ( $is_single ) {
                $fields[] = $related_object_field;
                $fields   = array_merge( $fields, $meta_fields );
            } else {

                $fields[] = [
                    'label'     => 'Relation Details',
                    'key'       => $this->buildFieldKey( $relation, 'meta_fields' ),
                    'type'      => 'repeater',
                    'subfields' => [ $related_object_field, ...$meta_fields ],
                    'group'     => $group_id
                ];

                $fields[] = [
                    'key'   => $this->buildFieldKey( $relation, 'options' ),
                    'type'  => 'relation_options',
                    'group' => $group_id,
                    'label' => 'Relation Options',
                ];
            }

            $fields[] = [
                'key'   => $relation['name'],
                'type'  => 'separator-end',
                'group' => $group_id,
            ];
        }

        return $fields;
    }

    /**
     * @param mixed $relation
     * @param $single_label
     * @param bool $is_single
     * @param string $group_id
     * @param mixed $search_post_type
     *
     * @return array
     */
    public function getRelatedObjectField( $relation, $single_label, bool $is_single, string $group_id, $search_post_type ) {
        $related_object_key = $is_single ?
            $this->buildFieldKey( $relation, 'related_object' ) :
            'related_object';

        $parent_object = $relation['control_type'] === 'parent' ? $relation['args']['parent_object'] : $relation['args']['child_object'];
        $child_object  = $relation['control_type'] === 'parent' ? $relation['args']['child_object'] : $relation['args']['parent_object'];

        return [
            'label'    => sprintf( 'Select %s', $single_label ),
            'key'      => $related_object_key,
            'group'    => $group_id,
            'type'     => 'post',
            'multiple' => false,
            'choices'  => [],
            'args'     => [
                'relation_id'      => $relation['id'],
                'parent_object'    => $parent_object,
                'child_object'     => $child_object,
                'control_type'     => $relation['control_type'],
                'search_post_type' => [ $search_post_type ],
            ],
        ];
    }

    public function getObjectData( $relation ) {
        $object_name = $relation['control_type'] === 'parent' ? $relation['args']['parent_object'] : $relation['args']['child_object'];
        $object_data = jet_engine()->relations->types_helper->type_parts_by_name( $object_name );

        return [
            'raw'         => $object_name,
            'object_type' => $object_data[0],
            'object'      => $object_data[1],
        ];
    }

    /**
     * @param $relation
     * @param string $group_id
     * @param bool $is_single
     *
     * @return array|array[]
     */
    public function getMetaFields( $relation, string $group_id, bool $is_single ): array {
        $meta_fields = array_map(
            fn( $field ) => $this->prepareField( $field, $group_id, $relation, $is_single ),
            $relation['args']['meta_fields']
        );

        return $meta_fields;
    }

    /**
     * @param $rel
     * @param int $current_object_id
     * @param $related_object_id
     * @param array $meta
     * @param bool $is_parent_processed
     *
     * @return array|int[]
     */
    public function updateRelation( $rel, int $current_object_id, $related_object_id, array $meta, bool $is_parent_processed ): array {

        if ( $is_parent_processed ) {
            $parent_object_id = $related_object_id;
            $child_object_id  = $current_object_id;
            $rel->set_update_context( 'parent' );
        } else {
            $parent_object_id = $current_object_id;
            $child_object_id  = $related_object_id;
            $rel->set_update_context( 'child' );
        }

        if ( $parent_object_id && $child_object_id ) {
            $rel->update( $parent_object_id, $child_object_id );
            $rel->update_all_meta( $meta, $parent_object_id, $child_object_id );
        }

        return [ $parent_object_id, $child_object_id ];
    }
}