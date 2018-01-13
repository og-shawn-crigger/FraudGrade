<?php
/**
 * Parent Class to add Custom, Sortable Data to the "Manage"/"EDIT" Datatable in Wordpress.
 *
 * This is a abstract class, and must be extended from your class.  It handles registering the columns and is fairly simple to setup, since this is a abstract class all methods inside of it must
 * exist inside of your child class even if they do nothing.
 *
 * The child class should be loaded on the "load-edit.php" hook current screen should be "edit-{$post_type}"
 * example code will be provided for that.
 *<code>
 *   	$carosel_cols = new Carousel_Cols( 'image_carousel' );
 *   	add_action ( 'load-edit.php', array( $carosel_cols, 'init' ) );
 *</code>
 *
 * @abstract
 * @package  WP Admin UI Enchantments
 * @author   Shawn Crigger <ithippyshawn@gmail.com>
 * @version  1.2.0
 **/
abstract class SVIZION_Meta_Columns {

	/**
	 * Init method to add custom columns
	 *
	 * Best to activation the init method via a action, I can't remember which one I used to use thou!
	 *
	 * @param $post_type  string   custom post type name
	 * @param $type       string   'pages' for hierarchical post types or 'posts' for nonhierarchical
	 */
	public function init( $post_type = '', $type = 'posts' )
	{
//		if ( ! is_admin() OR ! post_type_exists( $post_type ) ) return;
		if ( ! function_exists( 'get_current_screen' ) ) return false;
		// not sure if my previous check is needed now!
		if ( "edit-{$post_type}" !== get_current_screen()->id ) return false;
//		$type = get_post_type_object( $post_type );
//		$type = ( $type->hierarchical == 1 ) ? 'pages' : 'posts';

		add_filter( 'request',                           array( &$this, 'sortable_columns_orderby' ) );
//		add_filter( 'posts_clauses',                     array( &$this, 'sortable_taxonomys' ), 20, 2 );
		add_action( 'restrict_manage_posts',             array( &$this, 'edit_page_filters') );
		add_action( "manage_{$type}_custom_column",      array( &$this, 'custom_columns'), 10, 2 );
		add_filter( "manage_{$post_type}_posts_columns", array( &$this, 'sortable_columns') );
		add_filter( "manage_edit-{$post_type}_sortable_columns", array( &$this, 'register_sortable_columns' ) );
		// custom post type hierarchical: true -> manage_page_custom_column
		// custom post type hierarchical: false -> manage_post_custom_column
	}

	// ------------------------------------------------------------------------

	/**
	 * Register what columns to display
	 * @abstract
	 * @link  http://codex.wordpress.org/Plugin_API/Action_Reference/manage_posts_custom_column
	 * @param string $column
	 * @param string $post_id
	 * @return void
	 */
	abstract function custom_columns( $column, $post_id );

	// ------------------------------------------------------------------------

	/**
	 * Add the columns to the manage page
	 * @abstract
	 * @link   http://codex.wordpress.org/Plugin_API/Filter_Reference/manage_$post_type_posts_columns
	 * @param  array $cols
	 * @return array
	 */
	abstract function sortable_columns( $cols );

	// ------------------------------------------------------------------------

	/**
	 * Adds what columns are sortable to the array.
	 * @abstract
	 * @link   http://codex.wordpress.org/Plugin_API/Filter_Reference/manage_edit-post_type_columns
	 * @param  array $columns
	 * @return array
	 */
	abstract function register_sortable_columns( $columns );

	// ------------------------------------------------------------------------

	/**
	 * Handle the actual db query changes in the sort.
	 * @abstract
	 * @link   http://codex.wordpress.org/Plugin_API/Filter_Reference/manage_edit-post_type_columns
	 * @param  array $vars
	 * @return array
	 */
	abstract function sortable_columns_orderby( $vars );

	// ------------------------------------------------------------------------

	/**
	 * Adds custom filters for limiting what is displayed in datatable.
	 * Called from "restrict_manage_posts" hook.
	 * @abstract
	 * @link   http://codex.wordpress.org/Plugin_API/Filter_Reference/restrict_manage_posts
	 * @return void
	 */
	abstract function edit_page_filters( );

	// ------------------------------------------------------------------------

	/**
	 * Allows changing the entire query in one location, used to sort taxonomys by joins and orderbys in one hook.
	 * Called from "post_clauses" hook
	 * @abstract
	 * @link  http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_clauses
	 * @param  array  $clauses   Query Variables
	 * @param  object $wp_query  WP_Query class object
	 * @return array
	 */
	abstract function sortable_taxonomys( $clauses, $wp_query );

	// ------------------------------------------------------------------------


}

/* End of file meta-columns.php */
/* Location: ./wp-content/plugins/wp-admin-ui/meta-columns.php */