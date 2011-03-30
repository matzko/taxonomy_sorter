<?php

class WP_Taxonomy_Sort_Control
{
	public $tax_object = 0;

	public function __construct()
	{
		add_action( 'init', array( $this, 'event_init' ) );
		add_action( 'admin_init', array($this, 'event_admin_init' ), 99 );

		$this->tax_object = (int) get_option( 'wp_tax_sort_object' );
	}

	protected function _create_tax_object()
	{
		$id = wp_insert_post( array(
			'post_type' => '_tax_object',
			'post_status' => 'publish',
			'post_author' => 1,
			'post_title' => 'taxonomy ordering placeholder',
		) );

		if ( ! is_wp_error( $id ) ) {
			return (int) $id;
		}

		return 0;
	}

	public function event_admin_init()
	{
		$taxonomies = apply_filters( 'wp_taxonomy_sort_orderable_taxonomies', get_taxonomies( array( 'show_ui' => true ) ) );
		foreach( (array) $taxonomies as $tax_id ) {
			add_filter( 'manage_edit-' . $tax_id . '_columns', array($this, 'filter_manage_tax_columns') );
			add_filter( 'manage_' . $tax_id . '_custom_column', array($this, 'filter_manage_tax_custom_column' ), 10, 3 );
		}
	}

	public function event_init()
	{
		register_post_type('_tax_object', array(
			'label' => __('Taxonomy Sorter Object', 'taxonomy-sorter'),
			'singular_label' => __('Taxonomy Sorter Object', 'taxonomy-sorter'),
			'labels' => array(
				'label' => __('Taxonomy Sorter Object', 'taxonomy-sorter'),
				'name' => __('Taxonomy Sorter Objects', 'taxonomy-sorter'),
				'add_new' => __('Add New Taxonomy Sorter Object', 'taxonomy-sorter'),
				'singular_name' => __('Taxonomy Sorter Object', 'taxonomy-sorter'),
				'add_new_item' => __('Add New Taxonomy Sorter Object', 'taxonomy-sorter'),
				'edit_item' => __('Edit Taxonomy Sorter Object', 'taxonomy-sorter'),
				'new_item' => __('New Taxonomy Sorter Object', 'taxonomy-sorter'),
				'view_item' => __('View Taxonomy Sorter Object', 'taxonomy-sorter'),
				'search_items' => __('Search Taxonomy Sorter Objects', 'taxonomy-sorter'),
				'not_found' => __('No Taxonomy Sorter Objects found', 'taxonomy-sorter'),
				'not_found_in_trash' => __('No Taxonomy Sorter Objects found in Trash', 'taxonomy-sorter'),
			),
			'public' => false,
			'show_ui' => false,
			'capability_type' => 'post',
			'hierarchical' => false,
			'rewrite' => false,
			'query_var' => false,
		));

		if ( empty( $this->tax_object ) ) {
			$this->tax_object = $this->_create_tax_object();
			if ( ! empty( $this->tax_object ) ) {
				update_option( 'wp_tax_sort_object', $this->tax_object );
			}
		}
	}

	public function filter_manage_tax_columns( $columns = array() )
	{
		$columns[ 'tax_order' ] = __('Order', 'taxonomy-sorter');
		return $columns;
	}

	public function filter_manage_tax_custom_column( $markup = '', $column = '', $term_id = 0 )
	{
		$term_id = (int) $term_id;
		$screen = get_current_screen();

		if ( isset( $screen->taxonomy ) ) {
			$tax = get_taxonomy( $screen->taxonomy );
			if ( 
				'tax_order' == $column && 
				isset( $tax->cap->edit_terms ) &&
				current_user_can( $tax->cap->edit_terms )
			) {
				return 'my column content';
			}
		}
		return $markup;
	}
}
