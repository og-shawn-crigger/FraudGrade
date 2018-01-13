<?php
/**
 * Checks user IP address against fraudgarde.com's IP review API and blocks visitors can block visitors
 * who come from Proxy's, TOR, Datacenters, or VPN networks.
 *
 * @since      1.0.0
 *
 * @package    FraudGrade
 * @subpackage FraudGrade/admin
 * @author     FraudGrade
 * @version  1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) :
	die;
endif;

/**
 * The admin-facing functionality of the plugin.
 *
 * Handles the data grid functionality extended from SC_Meta_Columns class
 *
 * @package    FraudGrade
 * @subpackage FraudGrade/admin/datagrid
 * @author     FraudGrade
 * @version  1.0.0
 */
class ipvl_FraudGrade_Datagrid extends SVIZION_Meta_Columns {

	public function init( $post_type = 'ip_check', $type = 'posts' )
	{
		//if ( ! post_type_exists( $post_type ) ) do_action( 'init' );
		parent::init( 'ip_check', $type );
		add_action('post_row_actions', array( $this, 'row_actions'), 0, 2 );
	}

	// ------------------------------------------------------------------------

	public function row_actions( $actions, $post )
	{
		if ( $post->post_type == 'ip_check' )
		{
			$url = site_url( "wp-admin/post.php?post={$post->ID}&amp;action=edit" );
		    //unset( $actions['edit'] );
		    $actions['edit'] = "<a href=\"{$url}\" aria-label=\"Edit '75.189.90.188'\">View</a>\n";
		    unset( $actions['inline hide-if-no-js'] );
		    //unset( $actions['trash'] );
		    unset( $actions['view'] );
		}
		return $actions;
	}

	// ------------------------------------------------------------------------

	/**
	 * Register what columns to display
	 * @link  http://codex.wordpress.org/Plugin_API/Action_Reference/manage_posts_custom_column
	 * @param string $column
	 * @param string $post_id
	 * @return void
	 */
	function custom_columns( $column, $post_id )
	{
		switch ( $column )
		{
			case 'title':

				break;
			case '_ipGrade':
				echo get_post_meta( $post_id, '_ipGrade', true );
				break;
			case '_ipGradeDetails':
				echo get_post_meta( $post_id, '_ipGradeDetails', true );
				break;
			case '_anonymizerDetected':
				$detected = (int) get_post_meta( $post_id, '_anonymizerDetected', true );
				echo ( 0 === $detected ) ? 'NO' : 'YES';
				break;
			case '_anonymizerDetails':
				echo get_post_meta( $post_id, '_anonymizerDetails', true );
				break;
			case '_isBlacklisted':
				$detected = (int) get_post_meta( $post_id, '_isBlacklisted', true );
				echo ( 0 === $detected ) ? 'NO' : 'YES';
				break;
			case '_isBlacklistedDetails':
				echo get_post_meta( $post_id, '_isBlacklistedDetails', true );
				break;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Add the columns to the manage page
	 * @link   http://codex.wordpress.org/Plugin_API/Filter_Reference/manage_$post_type_posts_columns
	 * @param  array $cols
	 * @return array
	 */
	function sortable_columns( $cols )
	{
		return array (
			'cb'    => '<input type="checkbox" />',
			'title' => __( 'IP Address' ),
			'_ipGrade' => 'Grade',
			'_ipGradeDetails' => 'Grade Details',
			'_anonymizerDetected' =>  'Anonymizer',
			'_anonymizerDetails' => 'Anonymizer Details',
			'_isBlacklisted' => 'Blacklisted',
			'_isBlacklistedDetails' => 'Blacklisted Details',
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Adds what columns are sortable to the array.
	 * @link   http://codex.wordpress.org/Plugin_API/Filter_Reference/manage_edit-post_type_columns
	 * @param  array $columns
	 * @return array
	 */
	function register_sortable_columns( $columns )
	{
		return array (
			'cb'    => '<input type="checkbox" />',
			'title' => __( 'IP Address' ),
			'_ipGrade' => '_ipGrade',
			'_ipGradeDetails' => '_ipGradeDetails',
			'_anonymizerDetected' =>  '_anonymizerDetected',
			'_anonymizerDetails' => '_anonymizerDetails',
			'_isBlacklisted' => '_isBlacklisted',
			'_isBlacklistedDetails' => '_isBlacklistedDetails',
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Handle the actual db query changes in the sort.
	 * @link   http://codex.wordpress.org/Plugin_API/Filter_Reference/manage_edit-post_type_columns
	 * @param  array $vars
	 * @return array
	 */
	function sortable_columns_orderby( $vars )
	{
		if ( ! isset( $vars['orderby'] ) ) return $vars;
		switch ( $vars['orderby'] )
		{
			case '_ipGrade':
		        $vars = array_merge( $vars, array(
		            'meta_key' => '_ipGrade',
		            'orderby' => 'meta_value',
//		            'orderby' => 'meta_value_num'
		        ) );
				break;
			case '_ipGradeDetails':
		        $vars = array_merge( $vars, array(
		            'meta_key' => '_ipGradeDetails',
		            'orderby' => 'meta_value'
		        ) );
				break;
			case '_anonymizerDetected':
		        $vars = array_merge( $vars, array(
		            'meta_key' => '_anonymizerDetected',
		            'orderby' => 'meta_value'
		        ) );
				break;
			case '_anonymizerDetails':
		        $vars = array_merge( $vars, array(
		            'meta_key' => '_anonymizerDetails',
		            'orderby' => 'meta_value'
		        ) );
				break;
			case '_isBlacklisted':
		        $vars = array_merge( $vars, array(
		            'meta_key' => '_isBlacklisted',
		            'orderby' => 'meta_value'
		        ) );
				break;
			case '_isBlacklistedDetails':
		        $vars = array_merge( $vars, array(
		            'meta_key' => '_isBlacklistedDetails',
		            'orderby' => 'meta_value'
		        ) );
				break;
		}

	    return $vars;
	}

	// ------------------------------------------------------------------------

	/**
	 * Adds custom filters for limiting what is displayed in datatable.
	 * Called from "restrict_manage_posts" hook.
	 * @link   http://codex.wordpress.org/Plugin_API/Filter_Reference/restrict_manage_posts
	 * @return void
	 */
	function edit_page_filters( )
	{

	}

	// ------------------------------------------------------------------------
	/**
	 * Allows changing the entire query in one location, used to sort taxonomys by joins and orderbys in one hook.
	 * Called from "post_clauses" hook
	 * @link  http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_clauses
	 * @param  array  $clauses   Query Variables
	 * @param  object $wp_query  WP_Query class object
	 * @return array
	 */
	public function sortable_taxonomys( $clauses, $wp_query )
	{
		return $wp_query;
	}

}