<?php

class WP_Taxonomy_Sort_Control
{
	public $tax_object = 0;

	public function __construct()
	{
		add_action( 'init', array( $this, 'event_init' ) );
		add_action( 'admin_init', array($this, 'event_admin_init' ), 99 );

		add_filter( 'terms_clauses', array( $this, 'filter_terms_clauses' ), 99, 3 );

		$this->tax_object = (int) get_option( 'wp_tax_sort_object' );
	}

	protected function _create_tax_object()
	{
		$id = wp_insert_post( array(
			'post_type' => '_tax_object',
			'post_status' => 'draft',
			'post_author' => 1,
			'post_title' => 'taxonomy ordering placeholder',
		) );

		if ( ! is_wp_error( $id ) ) {
			return (int) $id;
		}

		return 0;
	}

	protected function _listen_for_ordering()
	{
		if ( 
			! empty( $_GET['move-term-up'] ) &&
			! empty( $_GET['move-nonce'] ) &&
			! empty( $_GET['tax'] ) &&
			wp_verify_nonce( $_GET['move-nonce'], 'move-term-nonce' ) &&
			current_user_can( get_taxonomy( $_GET['tax'] )->cap->edit_terms )
		) {
			WP_Taxonomy_Sorter::move_term_up( $_GET['move-term-up'], $_GET['tax'] );
			wp_redirect( remove_query_arg( array(
				'move-nonce',
				'move-term-up',
				'tax',
			) ) );
			exit;
		} elseif ( 
			! empty( $_GET['move-term-down'] ) &&
			! empty( $_GET['move-nonce'] ) &&
			! empty( $_GET['tax'] ) &&
			wp_verify_nonce( $_GET['move-nonce'], 'move-term-nonce' ) &&
			current_user_can( get_taxonomy( $_GET['tax'] )->cap->edit_terms )
		) {
			WP_Taxonomy_Sorter::move_term_down( $_GET['move-term-down'], $_GET['tax'] );
			wp_redirect( remove_query_arg( array(
				'move-nonce',
				'move-term-down',
				'tax',
			) ) );
			exit;
		}
	}

	public function event_admin_head()
	{
		?>
		<style type="text/css">
			.move-terms-wrap {
				width:64px;
			}

			.move-terms-wrap:after {
				clear:both;
				content:'.';
				display:block;
				height:0;
				visibility:hidden;
			}
			a.move-term-link {
				background:url(<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'client-files/images/arrow.png'; ?>) no-repeat 0 0;
				display:block;
				float:left;
				height:32px;
				overflow:hidden;
				text-align:left;
				text-indent:-999em;
				width:32px;
			}
			
			a.move-up-link {
				background-position:-32px 0;
			}
		</style>
		<?php
	}

	public function event_admin_init()
	{
		$taxonomies = apply_filters( 'wp_taxonomy_sort_orderable_taxonomies', get_taxonomies( array( 'show_ui' => true ) ) );
		foreach( (array) $taxonomies as $tax_id ) {
			add_filter( 'manage_edit-' . $tax_id . '_columns', array($this, 'filter_manage_tax_columns') );
			add_filter( 'manage_' . $tax_id . '_custom_column', array($this, 'filter_manage_tax_custom_column' ), 10, 3 );
		}

		add_action( 'admin_head', array( $this, 'event_admin_head' ) );

		$this->_listen_for_ordering();
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
				return sprintf( 
					'<div class="move-terms-wrap"><a href="%1$s" class="move-term-link move-up-link" title="%2$s">%3$s</a> <a href="%4$s" class="move-term-link move-down-link" title="%5$s">%6$s</a></div>',
					WP_Taxonomy_Sorter::move_up_link( $term_id, $screen->taxonomy ),
					esc_attr( __( 'Move Up', 'taxonomy-sorter' ) ),
					__( 'Move Up', 'taxonomy-sorter' ),
					WP_Taxonomy_Sorter::move_down_link( $term_id, $screen->taxonomy ),
					esc_attr( __( 'Move Down', 'taxonomy-sorter' ) ),
					__( 'Move Down', 'taxonomy-sorter' )
				);
			}
		}
		return $markup;
	}

	/**
	 * Filter get_terms() queries so that the results are ordered by the determined order, if available.
	 */
	public function filter_terms_clauses( $pieces = array(), $taxonomies = array(), $args = null )
	{
		global $wpdb;
		$is_ordered = wp_get_object_terms( $this->tax_object, $taxonomies, array( 'fields' => 'ids' ) );
		if ( ! empty( $is_ordered ) ) {
			$pieces['join'] .= " LEFT JOIN {$wpdb->term_relationships} AS ort ON tt.term_taxonomy_id = ort.term_taxonomy_id ";
			$pieces['where'] .= " AND ( ort.object_id = {$this->tax_object} OR ort.object_id IS NULL ) ";
			$orderby = array( 'ort.term_order', trim( str_replace( 'ORDER BY', '', $pieces['orderby'] ) ) );
			$pieces['orderby'] = ' ORDER BY ' . implode( ',', array_filter( $orderby ) );
		}
		return $pieces;
	}
}

class WP_Taxonomy_Sorter
{
	public static function get_term_order( $term_id = 0, $taxonomy = '' )
	{
		global $wpdb, $wp_taxonomy_sorter;

		$term_id = (int) $term_id;
		$object_id = (int) $wp_taxonomy_sorter->tax_object;
		$tax = mysql_real_escape_string( $taxonomy, $wpdb->dbh );

		if ( taxonomy_exists( $taxonomy ) ) {
			$query = "SELECT ort.term_order
				FROM {$wpdb->term_relationships} AS ort
				JOIN {$wpdb->term_taxonomy} AS ott
					ON ott.term_taxonomy_id = ort.term_taxonomy_id
				WHERE ort.object_id = {$object_id}
					AND ott.term_id = {$term_id} 
					AND ott.taxonomy = '{$tax}'
				LIMIT 1";
			return (int) $wpdb->get_var( $query );
		}

		return 0;
	}

	public static function move_down_link( $term_id = 0, $taxonomy = '' )
	{
		$term_id = (int) $term_id;

		if ( taxonomy_exists( $taxonomy ) ) {	
			return add_query_arg( array(
				'move-term-down' => $term_id,
				'tax' => $taxonomy,
				'move-nonce' => wp_create_nonce( 'move-term-nonce' ),
			) );
		} else {
			return '';
		}
	}

	public static function move_up_link( $term_id = 0, $taxonomy = '' )
	{
		$term_id = (int) $term_id;

		if ( taxonomy_exists( $taxonomy ) ) {	
			return add_query_arg( array(
				'move-term-up' => $term_id,
				'tax' => $taxonomy,
				'move-nonce' => wp_create_nonce( 'move-term-nonce' ),
			) );
		} else {
			return '';
		}
	}

	public static function move_term_down( $term_id = 0, $taxonomy = '' )
	{
		global $wp_taxonomy_sorter;
		$term_id = (int) $term_id;
		if ( taxonomy_exists( $taxonomy ) ) {
			$terms = wp_get_object_terms( 
				$wp_taxonomy_sorter->tax_object, 
				$taxonomy, 
				array( 
					'fields' => 'ids',
					'orderby' => 'term_order',
				) 
			);

			$all_terms = get_terms( $taxonomy, array( 'fields' => 'ids', 'hide_empty' => false ) );
			$all_terms = is_wp_error( $all_terms ) ? array() : array_map( 'intval', $all_terms );

			$terms = is_wp_error( $terms ) ? array() : array_map( 'intval', $terms );

			$extras = array_diff( $all_terms, $terms );
			$terms = array_merge( $terms, $extras );
			
			$term_key = array_search( $term_id, $terms );

			$ordered_terms = array();
			if ( false !== $term_key ) {
				if ( ( count( $terms ) - 1 ) == $term_key ) {
					$ordered_terms = $terms;
				} elseif ( ( count( $terms ) - 1 ) > $term_key ) {
					$first = array_slice( $terms, 0, $term_key );
					$last = array_slice( $terms, $term_key );

					$move_down = array_shift( $last );
					
					array_push( $first, array_shift( $last ), $move_down );
					$ordered_terms = array_merge( $first, $last );
				}
			} else {
				$ordered_terms = $terms;
			}

			if ( ! empty( $ordered_terms ) ) {
				self::set_terms_order( $taxonomy, $ordered_terms );
			}
		}
	}

	public static function move_term_up( $term_id = 0, $taxonomy = '' )
	{
		global $wp_taxonomy_sorter;
		$term_id = (int) $term_id;
		if ( taxonomy_exists( $taxonomy ) ) {	
			$terms = wp_get_object_terms( 
				$wp_taxonomy_sorter->tax_object, 
				$taxonomy, 
				array( 
					'fields' => 'ids',
					'orderby' => 'term_order',
				) 
			);

			$all_terms = get_terms( $taxonomy, array( 'fields' => 'ids', 'hide_empty' => false ) );
			$all_terms = is_wp_error( $all_terms ) ? array() : array_map( 'intval', $all_terms );

			$terms = is_wp_error( $terms ) ? array() : array_map( 'intval', $terms );

			$extras = array_diff( $all_terms, $terms );
			$terms = array_merge( $terms, $extras );
			
			$term_key = array_search( $term_id, $terms );

			$ordered_terms = array();
			if ( false !== $term_key ) {
				if ( 0 === $term_key ) {
					$ordered_terms = $terms;
				} elseif ( 0 < $term_key ) {
					$first = array_slice( $terms, 0, $term_key );
					$last = array_slice( $terms, $term_key );
					
					$move_down = array_pop( $first );
					
					array_push( $first, array_shift( $last ), $move_down );
					$ordered_terms = array_merge( $first, $last );
				}
			} else {
				$ordered_terms = $terms;
			}

			if ( ! empty( $ordered_terms ) ) {
				self::set_terms_order( $taxonomy, $ordered_terms );
			}
		}
	}

	/**
	 * Assign the order of a given set of terms for a taxonomy.
	 *
	 * @param string $taxonomy The taxonomy of the terms.
	 * @param array $ordered_term_ids The IDs of the terms to order.
	 */
	public static function set_terms_order( $taxonomy = '', $ordered_term_ids = array() )
	{
		global $wp_taxonomies, $wp_taxonomy_sorter;

		if ( taxonomy_exists( $taxonomy ) ) {
			$ids = array_filter( array_map( 'intval', $ordered_term_ids ) );
			if ( ! empty( $ids ) ) {
				$current_value = isset( $wp_taxonomies[$taxonomy]->sort ) ? $wp_taxonomies[$taxonomy]->sort : null; 
				$wp_taxonomies[$taxonomy]->sort = true;
				wp_set_object_terms( $wp_taxonomy_sorter->tax_object, $ids, $taxonomy ); 
				$wp_taxonomies[$taxonomy]->sort = $current_value;
			}
		}
	}
}
