<?php

namespace Wpai\JetEngine;

class PMJI_Fields {

	/**
	 * Convert JetEngine field types into WP All Import field types
	 */
	public static $mapping = [
		'datetime-local' => 'datetime',
		'switcher'       => 'toggle',
		'wysiwyg'        => 'textarea',
		'posts'          => 'post',
		'tab'            => 'separator',
		'sql-date'       => 'datetime',
		'accordion'      => 'separator',
		'endpoint'       => 'separator-end',
	];

	/**
	 * Field types that support multiple values
	 */
	public static $multiple = [
		'checkbox'
	];

	/**
	 * Field types to skip because they are not supported
	 */
	public static $unsupported = [
		'html',
		'hidden'
	];

	public static function getObjectType( $type ) {
		$object_types = [
			'import_users'  => 'user',
			'shop_customer' => 'user',
			'taxonomies'    => 'taxonomy',
			'comments'      => 'comment',
		];

		return $object_types[ $type ] ?? 'post';
	}

	public static function getMetaboxes( $post_type, $subtype ) {
		$meta_boxes  = jet_engine()->meta_boxes->data->get_items();
		$object_type = self::getObjectType( $post_type );
		$meta_boxes  = array_filter(
			$meta_boxes,
			fn( $metabox ) => in_array( $post_type, $metabox['args']['allowed_post_type'] ) || ( $object_type === 'user' && 'user' === $metabox['args']['object_type'] ) || ( $object_type === 'taxonomy' && in_array( $subtype, $metabox['args']['allowed_tax'] ) )
		);
		$callable    = self::class . '::prepareMetabox';

		return array_values( array_map( $callable, $meta_boxes ) );
	}

	public static function cctFields( $slug ) {
		$cct   = PMJI_CCT_Handler::getCct( $slug );
		$group = $cct->args['slug'];

		return array_map( fn( $field ) => self::prepareField( $field, $group ), $cct->fields );
	}

	/**
	 * Turns an array of jetengine fields into an array of fields that can be used by the plugin
	 *
	 * @param string $post_type
	 *
	 * @return array
	 */
	public static function fields( $post_type, $subtype ) {
		if ( str_starts_with( $post_type, 'cct:' ) ) {
			return static::cctFields( $post_type );
		}

		$fields     = jet_engine()->cpt->get_meta_fields_for_object( $post_type );
		$callable   = self::class . '::prepareField';
		$fields     = array_values( array_map( $callable, $fields ) );
		$tax_fields = jet_engine()->taxonomies->get_meta_fields_for_object( $subtype ) ?? [];
		$tax_fields = array_values( array_map( fn( $field ) => $callable( $field, 'tax-type'), $tax_fields ) );
		$fields = array_merge($fields, $tax_fields);
		$meta_boxes = self::getMetaboxes( $post_type, $subtype );

		foreach ( $meta_boxes as $metabox ) {
			$meta_fields = array_map( fn( $field ) => self::prepareField( $field, $metabox['id'] ), $metabox['meta_fields'] );
			$fields      = array_merge( $fields, $meta_fields );
		}

		$fields = array_filter( $fields, fn( $field ) => ! in_array( $field['type'], self::$unsupported ) );

		$instance  = PMJI_Relations::getInstance();
		$relations = $instance->getRelationFields( $post_type, $subtype );

		if ( count( $relations ) > 0 ) {
			$fields = array_merge( $fields, $relations );
		}

		return $fields;
	}

	/**
	 * @param string $post_type
	 *
	 * @return array
	 */
	public static function groups( $post_type, $subtype ) {
		if ( str_starts_with( $post_type, 'cct:' ) ) {
			$group = PMJI_CCT_Handler::getCct( $post_type );

			return [
				[
					'id'    => $group->args['slug'],
					'label' => 'Jet CCT - ' . $group->args['name']
				]
			];
		}

		$post_type_obj = get_post_type_object( $post_type );

		// Only require a post type object when not importing to users or taxonomies.
		if ( 'post' === self::getObjectType( $post_type ) ) {
			if ( ! $post_type_obj ) {
				return [];
			}
			$post_type_label = $post_type_obj->labels->singular_name;
		} else {
			$post_type_label = 'taxonomies' === $post_type ? $subtype : $post_type;
		}

		$post_type_fields = \jet_engine()->cpt->get_meta_fields_for_object( $post_type );
		$tax_fields = jet_engine()->taxonomies->get_meta_fields_for_object( $subtype ) ?? [];

		$meta_boxes = self::getMetaboxes( $post_type, $subtype );

		$callable = self::class . '::prepareGroup';
		$groups   = array_map( $callable, $meta_boxes );

		// Add post type fields group
		if ( count( $post_type_fields ) > 0 ) {
			array_unshift( $groups, [
				'id'    => 'post-type',
				'label' => __( sprintf( '%s (Post Type)', $post_type_label ), 'wp_all_import_jetengine_add_on' )
			] );
		}

		// Add tax fields group.
		if( count( $tax_fields ) > 0 ){
			array_unshift( $groups, [
				'id'    => 'tax-type',
				'label' => __( sprintf( '%s (Taxonomy)', $post_type_label ), 'wp_all_import_jetengine_add_on' )
			] );
		}

		// Add relations group
		$instance  = PMJI_Relations::getInstance();
		$relations = $instance->getRelations( $post_type, $subtype );

		if ( count( $relations ) > 0 ) {
			$groups[] = [
				'id'    => '_jet_engine_relations',
				'label' => __( 'Relations', 'wp_all_import_jetengine_add_on' )
			];
		}

		return $groups;
	}

	/**
	 * @param $field
	 * @param string $group
	 *
	 * @return array
	 */
	public static function prepareField( $field, string $group = 'post-type' ) {
		// Apply glossary
		$options = apply_filters( 'jet-engine/meta-fields/field-options', $field['options'] ?? [], $field );

		if(is_object($options)){
			$options = $options();
		}

		$choices = is_array( $options ) ? array_map( fn( $key, $choice ) => [
			'label' => $choice['value'] ?? $choice['label'],
			'value' => $choice['key'] ?? $key,
		], array_keys($options), $options ) : [ [ 'label' => '', 'value' => '' ] ];

		$type     = self::$mapping[ $field['type'] ] ?? $field['type'];
		$multiple = in_array( $type, self::$multiple ) || ( $field['is_multiple'] ?? false );

		$subfields = $field['repeater-fields'] ?? [];
		$subfields = array_map( fn( $subfield ) => self::prepareField( $subfield, $group ), $subfields );

		// Handle non-field types
		$object_type     = $field['object_type'] ?? 'field';
		$non_field_types = [ 'accordion', 'tab', 'endpoint' ];

		if ( in_array( $object_type, $non_field_types ) ) {
			$type = $object_type === 'endpoint' ? 'separator-end' : 'separator';
		}

		// CCT Overrides
		if ( $object_type === 'service_field' ) {
			if ( $field['name'] === 'cct_author_id' ) {
				$type = 'user';
			}

			if ( $field['name'] === 'cct_status' ) {
				$type = 'select';

				$choices = [
					[
						'label' => 'Publish',
						'value' => 'publish'
					],
					[
						'label' => 'Draft',
						'value' => 'draft'
					]
				];
			}
		}

		return [
			'label'     => $field['title'],
			'key'       => $field['name'],
			'type'      => $type,
			'default'   => $field['default_val'] ?? null,
			'group'     => $group,
			'multiple'  => $multiple,
			'choices'   => $choices,
			'subfields' => $subfields,
			'args'      => array_filter(
				[
					'is_array'         => $field['is_array'] ?? null,
					'is_timestamp'     => $field['is_timestamp'] ?? null,
					'search_post_type' => $field['search_post_type'] ?? null,
					'value_format'     => $field['value_format'] ?? null,
					'map_value_format' => $field['map_value_format'] ?? null,
				],
				fn( $value ) => $value !== null
			)
		];
	}

	/**
	 * @param $group
	 *
	 * @return array
	 */
	public static function prepareGroup( $group ) {
		return [
			'id'    => $group['id'],
			'label' => $group['name']
		];
	}

	/**
	 * @param $item
	 *
	 * @return array
	 */
	public static function prepareMetabox( $item ) {
		$item['args']        = maybe_unserialize( $item['args'] );
		$item['name']        = $item['args']['name'] ?? '(No name)';
		$item['meta_fields'] = maybe_unserialize( $item['meta_fields'] );

		return $item;
	}
}
