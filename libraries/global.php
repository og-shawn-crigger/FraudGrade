<?php
/**
 * Checks user IP address against fraudgarde.com's IP review API and blocks visitors can block visitors
 * who come from Proxy's, TOR, Datacenters, or VPN networks.
 *
 * @since      1.0.0
 *
 * @package    FraudGrade
 * @subpackage FraudGrade/globals
 * @author     FraudGrade
 * @version  1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) :
	die;
endif;

/**
 * The front-facing functionality of the plugin.
 *
 * Handles the actual API call, deteriming if the visitor should be blocked, saving the statisics, etc.
 *
 * @package    FraudGrade
 * @subpackage FraudGrade/globals
 * @author     FraudGrade
 * @version  1.0.0
 */
class ipvl_FraudGrade_Globals {

	/**
	 * Holds the current users IP address
	 * @static
	 * @access     private
	 * @var        string
	 */
	static private $user_ip = NULL;

	/**
	 * Holds the plugin settings
	 * @static
	 * @access     private
	 * @var        array
	 */
	static private $settings = NULL;

	/**
	 * Turns on debug mode
	 * @static
	 * @access     private
	 * @var        boolean
	 */
	static private $debug = FALSE;

	/**
	 * API URL will be run thru springf to replace placemarks with correct values before API call
	 * @access     private
	 * @var        string
	 */
	private $_api_url = 'https://fraudgrade.com/api/ipReview/?key=%s&ip=%s';

	static private $ranges = array();

	// ------------------------------------------------------------------------

	/**
	 * Setups up plugin settings required for front end and back end.
	 */
	public function __construct()
	{
		add_action( 'init', array( $this, 'register_cpt' ), 0 );
		self::$user_ip  = self::get_the_user_ip();
		self::$settings = ipvl_FraudGrade::get_settings();

		// verify a redirect location has been set
		//if ( ! isset( self::$settings['ipc_redirect'] ) OR '' === self::$settings['ipc_redirect'] ) return false;
		// verify the API key has been set
		if ( ! isset( self::$settings['ipc_text_apikey'] ) OR '' === self::$settings['ipc_text_apikey'] ) return false;
		// exit if admin page
		if ( is_admin() ) return;

		$this->get_ip_ranges();

		// check if user is whitelisted before doing anything fancy
		if ( isset( self::$settings['ipc_whitelist'] ) && is_array( self::$settings['ipc_whitelist']  ) )
		{
			if ( in_array( self::$user_ip, self::$settings['ipc_whitelist'] ) ) return FALSE;
			$value = (int) get_option( '_ipc_whitelisted' );
			++$value;
			update_option( '_ipc_whitelisted', $value );
		}

		// check if user is whitelisted before doing anything fancy
//		if ( isset( self::$settings['ipc_whitelist'] ) && is_array( self::$settings['ipc_whitelist']  ) )

		{
			$search_engine = FALSE;
			foreach ( self::$ranges as $engine => $range )
			{
				if ( ! isset( self::$settings['ipc_search_engines'][$engine] ) OR 0 === self::$settings['ipc_search_engines'][$engine] ) continue;
				foreach ( $range as $bad_ip )
				{
					if ( self::ip_in_range( self::$user_ip, $bad_ip ) )
					{
						$search_engine = TRUE;
						$this->update_visited_stats();
						break;
					}
				}
			}
		}

		if ( FALSE === $search_engine )
		{
			add_action( 'wp_head', array( $this, 'plugin_loop' ) );
		}
	}

	// ------------------------------------------------------------------------

	public function update_blocked_stats()
	{
		$value = (int) get_option( '_ipc_total_blocks' );
		++$value;
		update_option( '_ipc_total_blocks', $value );
		$value = (int) get_option( '_ipc_alltime_blocks' );
		++$value;
		update_option( '_ipc_alltime_blocks', $value );
	}

	public function update_visited_stats()
	{
		$value = (int) get_option( '_ipc_total_nonblocks' );
		++$value;
		update_option( '_ipc_total_nonblocks', $value );
		$value = (int) get_option( '_ipc_alltime_visits' );
		++$value;
		update_option( '_ipc_alltime_visits', $value );
	}

	// ------------------------------------------------------------------------

	public function plugin_loop()
	{
		$is_blocked     = FALSE;
		if ( is_admin() ) return;
		$json       = $this->fetch_api_result( self::$user_ip );
		$is_blocked = $this->is_blocked();

		if ( $is_blocked === TRUE )
		{
			$this->update_blocked_stats();
			self::do_redirect();
		} else {
			$this->update_visited_stats();
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Normalizes array result from get_post_meta by removing the arrays inside of the main array
	 * @static
	 * @param      mixed  $value  The value
	 * @return     mixed
	 */
	static public function normalize_array($value)
	{
		if ( is_array( $value ) ) return $value[0];
		return $value;
	}

	// ------------------------------------------------------------------------

	/**
	 * Determines if visitor blocked.
	 *
	 * @return     boolean  True if blocked, False otherwise.
	 */
	public function is_blocked()
	{
		global $wp_query;
		$settings = self::$settings;

		// check if page is whitelisted, if so abort
		if ( isset( $settings['ipc_whitelisted_pages'] ) && is_array( $settings['ipc_whitelisted_pages'] ) )
		{
			if ( in_array( '#homepage', $settings['ipc_whitelisted_pages'] ) && ( is_home() OR is_front_page() ) )
			{
				$this->update_visited_stats();
				return FALSE;
			}

			$post_id  = (int) $wp_query->post->ID;
			if ( in_array( $post_id, $settings['ipc_whitelisted_pages'] ) )
			{
				$this->update_visited_stats();
				return FALSE;
			}
		}

		$test     = $this->fetch_db_result( self::$user_ip );
		$results  = array();

		if ( is_object( $test ) && isset( $test->ID ) )
		{
			// fetch the post meta data and normalize it
			$test = get_post_meta( $test->ID );
			$test = array_map( array( __CLASS__, 'normalize_array' ), $test );
			if ( is_array( $settings['country_code'] ) )
			{
				$results['country'] = (int) ( in_array( $test['_countryIsoCode'], $settings['country_code'] ) );
			}

			if ( isset( $settings['ipc_checkbox_field_5'] ) && (int) $settings['ipc_checkbox_field_5'] === 1 )
			{
				$results['blocked'] = (int) ( (int) $test['_blocked'] === 1 );
			}
			if ( isset( $settings['ipc_checkbox_field_4'] ) && (int) $settings['ipc_checkbox_field_4'] === 1 )
			{
				$results['data'] = (int) ( (int) $test['_dataCenterDetected'] === 1 );
			}
			if ( isset( $settings['ipc_checkbox_field_3'] ) && (int) $settings['ipc_checkbox_field_3'] === 1 )
			{
				$results['vpn'] = (int) ( (int) $test['_vpnDetected'] === 1 );
			}
			if ( isset( $settings['ipc_checkbox_field_2'] ) && (int) $settings['ipc_checkbox_field_2'] === 1 )
			{
				$results['tor'] = (int) ( (int) $test['_torDetected'] === 1 );
			}
			if ( isset( $settings['ipc_checkbox_field_1'] ) && (int) $settings['ipc_checkbox_field_1'] === 1 )
			{
				$results['proxy'] = (int) ( (int) $test['_proxyDetected'] === 1 );
			}

			if ( in_array( 1, $results ) ) return TRUE;
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Creates javascript redirect to the redirection location, will check the current page and if it is the
	 * same page as the redirection location will exit.
	 *
	 * @return     boolean
	 */
	static public function do_redirect()
	{
		global $wp_query;
		if ( ! isset( self::$settings['ipc_redirect'] ) OR '' === self::$settings['ipc_redirect'] )
		{
			wp_die('Blocked');
		}
		$redirect = self::$settings['ipc_redirect'];
		$post_id  = (int) $wp_query->post->ID;
		if ( is_numeric( $redirect ) )
		{
			$redirect = (int) $redirect;
			// already on this page so exit
			if ( $post_id === $redirect ) return false;
			$redirect     = get_the_permalink( $redirect );
		} else {
			if ( is_integer( $post_id ) )
			{
				$id = url_to_postid( site_url( $redirect ) );
				if ( $id === $post_id ) return false;
			}
			$slug = $wp_query->post->post_name;
			// already on this page so exit
			if ( $slug === $redirect ) return false;
		}

		echo "\n<script>\nwindow.location = \"{$redirect}\";\n</script>\n";
		die();
	}
	// ------------------------------------------------------------------------

	/**
	 *  Register Custom Post Type
	 */
	public function register_cpt()
	{
		$labels = array(
			'name'                  => _x( 'Cached IPs', 'Post Type General Name', 'ip_check' ),
			'singular_name'         => _x( 'IP', 'Post Type Singular Name', 'ip_check' ),
			'menu_name'             => __( 'FraudGrade', 'ip_check' ),
			'name_admin_bar'        => __( 'FraudGrade', 'ip_check' ),
			'archives'              => __( 'Item Archives', 'ip_check' ),
			'attributes'            => __( 'Item Attributes', 'ip_check' ),
			'parent_item_colon'     => __( 'Parent Item:', 'ip_check' ),
			'all_items'             => __( 'IP Review Cache', 'ip_check' ),
			'add_new_item'          => __( 'Add New Item', 'ip_check' ),
			'add_new'               => __( 'Add New', 'ip_check' ),
			'new_item'              => __( 'New IP address', 'ip_check' ),
			'edit_item'             => __( 'View IP address', 'ip_check' ),
			'update_item'           => __( 'Update IP address', 'ip_check' ),
			'view_item'             => __( 'View IP address', 'ip_check' ),
			'view_items'            => __( 'View IP addresses', 'ip_check' ),
			'search_items'          => __( 'Search IP addresses', 'ip_check' ),
			'not_found'             => __( 'Not found', 'ip_check' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'ip_check' ),
			'featured_image'        => __( 'Featured Image', 'ip_check' ),
			'set_featured_image'    => __( 'Set featured image', 'ip_check' ),
			'remove_featured_image' => __( 'Remove featured image', 'ip_check' ),
			'use_featured_image'    => __( 'Use as featured image', 'ip_check' ),
			'insert_into_item'      => __( 'Insert into item', 'ip_check' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', 'ip_check' ),
			'items_list'            => __( 'Items list', 'ip_check' ),
			'items_list_navigation' => __( 'Items list navigation', 'ip_check' ),
			'filter_items_list'     => __( 'Filter items list', 'ip_check' ),
		);
		$args = array(
			'label'                 => __( 'IP', 'ip_check' ),
			'description'           => __( 'Post Type Description', 'ip_check' ),
			'labels'                => $labels,
			'supports'              => array( 'title' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => true,
		    'rewrite'               => false,
			'capability_type'       => 'page',
			'show_in_rest'          => false,
			'menu_icon'             => 'data:image/svg+xml;base64,' . base64_encode('<svg width="20" height="20" viewBox="0 0 1792 1792" fill="white" xmlns="http://www.w3.org/2000/svg"><path d="M1440 893q0-161-87-295l-754 753q137 89 297 89 111 0 211.5-43.5t173.5-116.5 116-174.5 43-212.5zm-999 299l755-754q-135-91-300-91-148 0-273 73t-198 199-73 274q0 162 89 299zm1223-299q0 157-61 300t-163.5 246-245 164-298.5 61-298.5-61-245-164-163.5-246-61-300 61-299.5 163.5-245.5 245-164 298.5-61 298.5 61 245 164 163.5 245.5 61 299.5z"/></svg>'),
		);
		register_post_type( 'ip_check', $args );
	}

	// ------------------------------------------------------------------------

	/**
	 * Saves API query results in database
	 *
	 * @param      object  $data   The data
	 * @return     boolean
	 */
	public function save_query_result( $data )
	{
		// Data is not correct format
		if ( ! is_object( $data ) OR ! isset( $data->ipReview ) ) return FALSE;
		// Cache is disabled do not save data
		if ( (int) ipvl_FraudGrade::get_settings()['ipc_cache_time'] === 0) return FALSE;
		global $user_ID, $wpdb;

		$data = $data->ipReview;
		$user_ID = (int) $user_ID;
		if ( 0 === $user_ID )
		{
			$user_ID = 1;
		}

		$page_id = 0;

		 $sql ="
		 		SELECT {$wpdb->posts}.ID, {$wpdb->posts}.post_title
		 			FROM {$wpdb->posts}
		 		WHERE {$wpdb->posts}.post_title = '{$data->ip}'
		 			AND {$wpdb->posts}.post_type = 'ip_check'
		 			AND {$wpdb->posts}.post_status = 'publish'
		 		LIMIT 1";

		$exists = $wpdb->get_results($sql, OBJECT);
		if ( is_array( $exists ) && array_key_exists(0, $exists) ) $exists = $exists[0];
		if ( isset( $exists ) && is_object( $exists ) && isset( $exists->ID ) )
		{
			$page_id = $exists->ID;
		}

		$blocked = 0;
		if ( (int) $data->isBlacklisted > 0 )
		{
			$blocked = 1;
		}

		// @todo: test how this data is returned in the api
		$data->proxyDetected = 0;
		$data->vpnDetected   = 0;
		$data->torDetected   = 0;
		$data->dataCenterDetected = 0;
		if ( strlen( $data->anonymizerDetails ) > 0 )
		{
			$key = $data->anonymizerDetails;
			$data->{$key} = 1;
		}
		$new_post = array(
			'ID'            => $page_id,
			'post_title'    => $data->ip,
			'post_content'  => '',
			'post_status'   => 'publish',
			'post_date'     => date('Y-m-d H:i:s'),
			'post_author'   => $user_ID,
			'post_type'     => 'ip_check',
			'post_category' => array(0),
			'meta_input'    => array(
	            '_anonymizerDetected'   => (int) $data->anonymizerDetected,
	            '_anonymizerDetails'    => (string) $data->anonymizerDetails,
	            '_isBlacklisted'        => (int) $data->isBlacklisted,
	            '_isBlacklistedDetails' => (string) $data->isBlacklistedDetails,
	            '_ipGrade'              => (string) $data->ipGrade,
	            '_ipGradeDetails'       => (string) $data->ipGradeDetails,
				'_proxyDetected'        => (int) $data->proxyDetected,
				'_vpnDetected'          => (int) $data->vpnDetected,
				'_torDetected'          => (int) $data->torDetected,
				'_dataCenterDetected'   => (int) $data->dataCenterDetected,
				'_blocked'              => $blocked,
				'_latitude'             => $data->latitude,
				'_longitude'            => $data->longitude,
				'_city'                 => $data->city,
				'_postalCode'           => $data->postalCode,
				'_state'                => $data->state,
				'_stateIsoCode'         => $data->stateIsoCode,
				'_country'              => $data->country,
				'_countryIsoCode'       => $data->countryIsoCode,
				'_accuracyRadius'       => $data->accuracyRadius,
				'_isp'                  => $data->isp,
				'_connectionType'       => $data->connectionType,
				'_organization'         => $data->organization,
				'_asn'                  => $data->asn,
				'_asnOrganization'      => $data->asnOrganization,
			),
		);
		global $wp_rewrite;
		$wp_rewrite = new wp_rewrite;
		$post_id = wp_insert_post($new_post);
	}

	// ------------------------------------------------------------------------

	/**
	 * Fetchs and returns the api results from the database if they exist.
	 *
	 * @param      string  $ip     current user ip
	 * @return     object
	 */
	public function fetch_db_result( $ip = '' )
	{
		global $wpdb;
		$sql ="
		 		SELECT {$wpdb->posts}.ID, {$wpdb->posts}.post_title
		 			FROM {$wpdb->posts}
		 		WHERE {$wpdb->posts}.post_title = '{$ip}'
		 			AND {$wpdb->posts}.post_type = 'ip_check'
		 			AND {$wpdb->posts}.post_status = 'publish'
		 		LIMIT 1";

		$post = $wpdb->get_results($sql, OBJECT);
		if ( is_array( $post ) && array_key_exists(0, $post) ) $post = $post[0];
		return $post;
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns JSON result from API
	 *
	 * @param      string  $ip     current visitor ip address
	 * return      object
	 */
	public function fetch_api_result( $ip = '' )
	{
		// if cache is active check database for IP address and return database record instead of API
		if ( (int) ipvl_FraudGrade::get_settings()['ipc_cache_time'] > 0)
		{
			$data = $this->fetch_db_result( self::$user_ip );
			// user is cached
			if ( is_object( $data ) && isset( $data->ID ) && $data->post_title === $ip )
			{
				// grab post meta and normalize array
				$meta = get_post_meta( $data->ID );
				foreach ($meta as $key => &$value)
				{
					if ( is_array( $value ) ) $value = $value[0];
				}
				$data = (array) $data;
				$data = $data + $meta;

				return $data;
			}
		}

		$key   = self::$settings['ipc_text_apikey'];
		if ( '' === $key ) return FALSE;
		$url   = sprintf( $this->_api_url, $key, $ip );

		$json  = wp_remote_get( $url );

		if ( is_array( $json ) ) {
		  $json = $json['body']; // use the content
		}
		$json = json_decode( $json );

		$this->save_query_result( $json );
		return $json;
	}

	// ------------------------------------------------------------------------

	/**
	 * Reads files containing search engine ip ranges into array
	 *
	 * @return     array  The ip ranges.
	 */
	public function get_ip_ranges()
	{
		$files  = array( 'baidu', 'bing', 'blekko', 'duckduckgo', 'exalead', 'facebook', 'gigablast', 'google', 'sogou', 'yahoo', 'yandex' );
		$dir    = str_replace( '/libraries', '/ranges//', __DIR__ );
		$ranges = array();
		foreach ($files as $file)
		{
			$ranges[$file] = file( $dir . $file . '.txt' , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		}
/*
		$count = count($ranges);
		for ($i=0; $i < $count; $i++) {
			if ( 0 === $i ) continue;
			$ranges[0] = $ranges[0] + $ranges[$i];
			unset($ranges[$i]);
		}
*/
//		$new = array_values( $ranges );
//		$new = array_values( $new );
		self::$ranges = $ranges;
		return $ranges;
	}

	// ------------------------------------------------------------------------

	/**
	 * Check if a given ip is in a network
	 * @static
	 * @param  string $ip    IP to check in IPV4 format eg. 127.0.0.1
	 * @param  string $range IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
	 * @return boolean true if the ip is in this range / false if not.
	 */
	static public function ip_in_range( $ip, $range )
	{
		if ( strpos( $range, '/' ) == false )
		{
			$range .= '/32';
		}
		// $range is in IP/CIDR format eg 127.0.0.1/24
		list( $range, $netmask ) = explode( '/', $range, 2 );
		$range_decimal           = ip2long( $range );
		$ip_decimal              = ip2long( $ip );
		$wildcard_decimal        = pow( 2, ( 32 - $netmask ) ) - 1;
		$netmask_decimal         = ~ $wildcard_decimal;
		return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns the users IP address
	 * @link       https://www.chriswiegman.com/2014/05/getting-correct-ip-address-php/
	 * @return     string  The user ip.
	 */
	static public function get_the_user_ip()
	{
		//@todo: remove my IP after debugging is done
		if ( ipvl_FraudGrade::is_debug() === TRUE ) return '128.199.66.186';
//		if ( ipvl_FraudGrade::is_debug() === TRUE ) return '103.6.76.0';

		$headers = $_SERVER;
		//Just get the headers if we can or else use the SERVER global.
		if ( function_exists( 'apache_request_headers' ) )
		{
			$headers = apache_request_headers();
		}

		//Get the forwarded IP if it exists.
		if ( array_key_exists( 'X-Forwarded-For', $headers ) && filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
		{
			$the_ip = $headers['X-Forwarded-For'];
		} elseif ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ) && filter_var( $headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
		{
			$the_ip = $headers['HTTP_X_FORWARDED_FOR'];
		} else {
			$the_ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
		}

		return $the_ip;
	}

	// ------------------------------------------------------------------------
}



if ( ! function_exists('ipvl_dump')) :

	/**
	* Outputs the given variables with formatting and location. Huge props
	* out to Phil Sturgeon for this one (http://philsturgeon.co.uk/blog/2010/09/power-dump-php-applications).
	* To use, pass in any number of variables as arguments.
	*
	* @return void
	*/
	function ipvl_dump()
	{
		list($callee) = debug_backtrace();
		$arguments = func_get_args();
		$total_arguments = count($arguments);

		echo '<fieldset style="background: #fefefe !important; border:2px red solid; padding:5px">';
		echo '<legend style="background:lightgrey; padding:5px;">'.$callee['file'].' @ line: '.$callee['line'].'</legend><pre>';

		$i = 0;
		foreach ($arguments as $argument)
		{
			echo '<br/><strong>Debug #'.(++$i).' of '.$total_arguments.'</strong>: ';
			if ( (is_array($argument) || is_object($argument)) && count($argument))
			{
				print_r($argument);
			} else {
				var_dump($argument);
			}
		}

		echo '</pre>' . PHP_EOL;
		echo '</fieldset>' . PHP_EOL;
	}

endif;

