<?php

namespace Wpai\JetEngine;

use Wpai\AddonAPI\PMXI_Addon_Data_Importer;

class CCT_Data_Importer extends PMXI_Addon_Data_Importer {

    public $default_fields = [
        '_ID',
        'cct_author_id',
        'cct_created',
        'cct_modified',
        'cct_slug',
        'cct_status'
    ];

    public function custom_type() {
        return $this->options['custom_type'];
    }

    public function cct() {
        return PMJI_CCT_Handler::getCct( $this->custom_type() );
    }

    public function item() {
        return $this->cct()->get_item_handler();
    }

    public function get_record( $post_id ) {
        $cct = $this->cct();

        return $cct->db->get_item( $post_id );
    }

    public function get_record_title( $articleData ) {
        $factory     = $this->item()->get_factory();
        $title_field = $factory->get_arg( 'related_post_type_title' );

        if ( $factory->get_arg( 'has_single' ) && isset( $title_field ) ) {
            if ( isset( $articleData[ $title_field ] ) ) {
                return $articleData[ $title_field ];
            }
        }

        // Try to guess the record title.
        return $articleData['title'] ?? $articleData['name'] ?? $articleData['ID'] ?? '(untitled cct)';
    }

    public function get_record_meta( $post_id ) {
        $record = $this->get_record( $post_id );

        return array_filter(
            $record,
            fn( $key ) => ! in_array( $key, $this->default_fields ),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function upsert( $articleData, $custom_type_details ) {
        if ( empty( $articleData['ID'] ) ) {
            $this->logger( sprintf( __( '<b>CREATING</b> `%s` `%s`', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ), $custom_type_details->labels->singular_name ) );
        } else {
            $this->logger( sprintf( __( '<b>UPDATING</b> `%s` `%s`', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ), $custom_type_details->labels->singular_name ) );
        }

        return $this->item()->update_item(
            array_merge(
                [
                    '_ID' => $articleData['ID'] ?? null,
                ],
                $articleData
            )
        );
    }

    public function update_content( $post_id, $content ) {
        // CCT does not support post_content field
    }

    public function update_meta( $post_id, $meta_key, $meta_value ) {
        $this->item()->update_item( [
            '_ID'     => $post_id,
            $meta_key => $meta_value
        ] );

        $this->logger( sprintf( __( 'Instead of deletion post with ID `%s`, set Custom Field `%s` to value `%s`', 'wp_all_import_plugin' ), $post_id, $meta_key, $meta_value ) );
    }

    public function combine_data( $data ) {
        extract( $data );

        $articleData = apply_filters(
            'wp_all_import_combine_article_data',
            [
                'cct_status'    => $post_status,
                'cct_author_id' => $post_author,
                'cct_created'   => $date,
	            'post_type'     => $this->custom_type(),
            ], // TODO: Are these fields correct?
            $this->custom_type(),
            $this->record->id,
            $i
        );

        $this->logger( sprintf( __( 'Combine all data for CCT `%s`...', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ) ) );

        return $articleData;
    }

    public function choose_data_to_update( $post_to_update, $post_to_update_id, $post_type, $taxonomies, &$articleData, $i ) {
        if ( ! $this->options['is_update_dates'] ) { // preserve date of already existing article when duplicate is found
            $articleData['cct_created']  = $post_to_update['cct_created'];
            $articleData['cct_modified'] = $post_to_update['cct_modified'];
            $this->logger( sprintf( __( 'Preserve date of already existing article for `%s`', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_status'] ) { // preserve status and trashed flag
            $articleData['cct_status'] = $post_to_update['cct_status'];
            $this->logger( sprintf( __( 'Preserve status of already existing article for `%s`', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_content'] ) {
            $this->logger( sprintf( __( 'JetEngine CCTs does not support post_content, preserving the content of already existing article is not possible for `%s`', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_title'] ) {
            $this->logger( sprintf( __( 'JetEngine CCTs does not support post_title, preserving the title of already existing article is not possible for `%s`', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_slug'] ) {
            $this->logger( sprintf( __( 'JetEngine CCTs does not support post_slug, preserving slug of already existing article is not possible for `%s`', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ) ) );
        }

        if ( ! $this->options['is_update_excerpt'] ) {
            $this->logger( sprintf( __( 'JetEngine CCTs does not support post_excerpt, preserving excerpt of already existing article is not possible for `%s`', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_menu_order'] ) {
            $this->logger( sprintf( __( 'JetEngine CCTs does not support menu order, preserving menu order of already existing article is not possible for `%s`', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_parent'] ) {
            $this->logger( sprintf( __( 'JetEngine CCTs does not support post_parent, preserving parent of already existing article is not possible for `%s`', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_post_type'] ) {
            $this->logger( sprintf( __( 'JetEngine CCTs does not support post_type, preserving post type of already existing article is not possible for `%s`', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_comment_status'] ) {
            $this->logger( sprintf( __( 'JetEngine CCTs does not support comment status, preserving comment status of already existing article is not possible for `%s`', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_ping_status'] ) {
            $this->logger( sprintf( __( 'JetEngine CCTs does not support ping status, preserving ping status of already existing article is not possible for `%s`', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_author'] ) {
            $articleData['cct_author_id'] = $post_to_update['cct_author_id'];
            $this->logger( sprintf( __( 'Preserve post author of already existing CCT for `%s`', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ) ) );
        }

        if ( ! wp_all_import_is_update_cf( '_wp_page_template', $this->options ) ) {
            $this->logger( sprintf( __( 'JetEngine CCTs does not support page template, preserving page template of already existing article is not possible for `%s`', 'wp_all_import_plugin' ), $this->get_record_title( $articleData ) ) );
        }
    }

    public function change_post_status_to_draft( $missing_post_id ) {
        $this->item()->update_item( [
            '_ID'        => $missing_post_id,
            'cct_status' => 'draft'
        ] );

        $this->logger( sprintf( __( 'Instead of deletion, change CCT with ID `%s` status to %s', 'wp_all_import_plugin' ), $missing_post_id, $this->options['status_of_removed'] ) );
    }

    public function change_post_status( $post_id, $new_status ) {
        $this->item()->update_item( [
            '_ID'        => $post_id,
            'cct_status' => $new_status
        ] );

        $this->logger( sprintf( __( 'Instead of deletion, change CCT with ID `%s` status to %s', 'wp_all_import_plugin' ), $post_id, $new_status ) );
    }

    public function delete_meta( $post_id, $meta_key ) {
        $this->item()->update_item( [
            '_ID'     => $post_id,
            $meta_key => null
        ] );
    }

    public function delete_records( $ids ) {
        do_action( 'pmxi_delete_post', $ids, $this->record );
        $handler = $this->item();

        foreach ( $ids as $id ) {
            $handler->delete_item( $id, false );
        }
    }

    public function delete_images( $post_id, $articleData, $image_bundle ) {
        $cct          = $this->cct();
        $media_fields = array_filter( $cct->fields, fn( $field ) => $field['type'] === 'media' );
        $keys         = array_column( $media_fields, 'key' );
        $values       = array_intersect_key( $articleData, array_flip( $keys ) );
        $missing_ids  = [];

        // Flatten the array
        array_walk_recursive( $values, function ( $id ) use ( &$missing_ids ) {
            $missing_ids[] = $id;
        } );

        $title = $this->get_record_title( $articleData );

        if ( $this->options['update_all_data'] == 'yes' or ( $this->options['update_all_data'] == 'no' and $this->options['is_update_attachments'] ) ) {
            $this->logger( sprintf( __( 'Deleting attachments for `%s`', 'wp_all_import_plugin' ), $title ) );

            if ( ! $this->options['is_search_existing_attach'] ) {
                foreach ( $missing_ids as $id ) {
                    wp_delete_attachment( $id, true );
                }
            }
        }

        // handle obsolete attachments (i.e. delete or keep) according to import settings
        if ( $this->options['update_all_data'] == 'yes' or ( $this->options['update_all_data'] == 'no' and $this->options['is_update_images'] and $this->options['update_images_logic'] == "full_update" ) ) {
            $this->logger( sprintf( __( 'Deleting images for `%s`', 'wp_all_import_plugin' ), $title ) );
            if ( ! empty( $images_bundle ) ) {
                foreach ( $images_bundle as $slug => $bundle_data ) {
                    $option_slug = ( $slug == 'pmxi_gallery_image' ) ? '' : $slug;
                    if ( count( $images_bundle ) > 1 && $slug == 'pmxi_gallery_image' ) {
                        continue;
                    }
                    $do_not_remove_images = ( $this->options[ $option_slug . 'download_images' ] == 'gallery' or $this->options[ $option_slug . 'do_not_remove_images' ] ) ? false : true;

                    if ( $do_not_remove_images ) {
                        foreach ( $missing_ids as $id ) {
                            wp_delete_attachment( $id, true );
                        }
                    }
                }
            }
        }

        return $missing_ids ?? [];
    }

    public function delete_image_custom_field( $post_id, $meta_key ) {
        $this->delete_meta( $post_id, $meta_key );
    }

    public function delete_record_not_present_in_file( $missing_post_id ) {
        // Trigger pre delete hook.
        do_action( 'pmxi_before_delete_post', $missing_post_id, $this->record );

        // Remove attachments
        if ( ! empty( $this->options['is_delete_attachments'] ) ) {
            $this->delete_images( $missing_post_id, [], [] );
        }

        // Remove images
        if ( ! empty( $this->options['is_delete_imgs'] ) ) {
            $this->delete_images( $missing_post_id, [], [] );
        }
    }

    public function get_missing_records( $ids, $missing_status, $missing_cf ) {
        $cct = $this->cct();

        $table_name = $cct->db->table();
        $query      = "SELECT _ID as post_id FROM " . $table_name;
        $query      .= " WHERE _ID NOT IN (" . implode( ",", $ids ) . ")";

        if ( ! empty( $missing_status ) || ! empty( $missing_cf ) ) {
            $query .= " AND (";
        }

        if ( ! empty( $missing_status ) ) {
            $query .= " cct_status != '" . $missing_status . "'";

            if ( ! empty( $missing_cf ) ) {
                $query .= " OR ";
            }
        }

        if ( ! empty( $missing_cf ) ) {
            $cf_conditions = [];
            foreach ( $missing_cf as $key => $cf ) {
                if ( ! empty( $cf['name'] ) ) {
                    $cf_conditions[] = "$key != '" . $cf['value'] . "'";
                }
            }
            $query .= implode( " OR ", $cf_conditions );
        }

        if ( ! empty( $missing_status ) || ! empty( $missing_cf ) ) {
            $query .= ")";
        }

        return $this->wpdb->get_results( $query, ARRAY_A );
    }

    public function move_missing_record_to_trash( $missing_post_id ) {
        $this->logger( sprintf( __( 'JetEngine CCTs does not support trashing items, keeping ID `%s` unchanged.', 'wp_all_import_plugin' ), $missing_post_id ) );
    }
}