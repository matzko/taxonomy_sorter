<?php

class WP_Taxonomy_Sort_Control
{
	public function __construct()
	{
		add_action( 'init', array( $this, 'event_init' ) );
		add_action( 'admin_init', array($this, 'event_admin_init' ), 99 );
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
