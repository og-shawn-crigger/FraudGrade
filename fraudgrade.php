<?php
/**
 * The plugin bootstrap file
 *
 * This plugin integrates directly with FraudGrade's Fraud Prevention and Detection Services to mitigate
 * most types of Online Fraud. To get started: activate the FraudGrade plugin and then browse to the
 * Settings page to setup your integration.
 *
 * @link
 * @since             1.0.0
 * @package           FraudGrade
 *
 * @wordpress-plugin
 * Plugin Name:       FraudGrade
 * Plugin URI:
 * Description:       This plugin integrates directly with FraudGrade's Fraud Prevention and Detection Services to mitigate most types of Online Fraud. To get started: activate the FraudGrade plugin and then browse to the Settings page to setup your integration.
 * Version:           1.0.0
 * Author:            FraudGrade
 * Author URI:
 * License:           GPLv3 or later
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       ip_check
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) :
	die;
endif;

/**
 * The main object of the IP Check plugin, creates the sub-objects and handles all the business logic.
 *
 * @package    IP_Check
 * @author     FraudGrade
 * @version  1.0.0
 */
class ipvl_FraudGrade {

	/**
	 * Holds the plugin settings
	 * @static
	 * @access     private
	 * @var        array
	 */
	static private $settings = FALSE;

	/**
	 * Turns on debug mode
	 * @static
	 * @access     private
	 * @var        boolean
	 */
	static private $debug = FALSE;

	// ------------------------------------------------------------------------

	/**
	 * Requires the libraries and does the dirty work.
	 */
	public function __construct()
	{
		add_action ('ipc_clear_cache', array( $this, 'clear_cache') );
		require( __DIR__ . '/libraries/global.php');
		$app = new ipvl_FraudGrade_Globals();

		if ( is_admin() )
		{
			require( __DIR__ . '/libraries/admin.php');
			$admin = new ipvl_FraudGrade_Admin();
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Determines if debug in debug mode.
	 * @return     boolean  True if debug, False otherwise.
	 */
	static public function is_debug()
	{
		return (bool) self::$debug;
	}

	// ------------------------------------------------------------------------

	/**
	 * Gets the settings.
	 * @static
	 * @return     array  The settings.
	 */
	static public function get_settings()
	{
		if ( ! self::$settings )
		{
			self::$settings = get_option( 'ipc_settings' );
		}
		return self::$settings;
	}

	// ------------------------------------------------------------------------

	/**
	 * Clears the cache and updates the total blocked/visitors stats. Run by WP CRON job.
	 * @see         wp_schedule_event
	 */
	public function clear_cache()
	{
		$cached = get_posts( array( 'post_type' => 'ip_check') );
		foreach ( $cached as $item )
		{
		    wp_delete_post( $item->ID, true); // Set to False if you want to send them to Trash.
		}

		// update alltime stats and stats between cache refreshes.
		$blocks = (int) get_option( '_ipc_total_blocks' );
		$visits = (int) get_option( '_ipc_total_nonblocks' );
		$alltime_blocks = (int) get_option( '_ipc_alltime_blocks' );
		$alltime_visits = (int) get_option( '_ipc_alltime_visits' );
		update_option( '_ipc_total_blocks', 0 );
		update_option( '_ipc_total_nonblocks', 0 );
	}

	// ------------------------------------------------------------------------
}// end ipvl_FraudGrade()

/**
 * Creates IP_Check object instance
 */
$GLOBALS['ipvl_FraudGrade'] = new ipvl_FraudGrade();

// ------------------------------------------------------------------------

/**
 * Saves default settings in database and creates default cron job.
 */
function ipvl_activate()
{

	$settings = array(
		'ipc_text_apikey'       => '',
	    'ipc_checkbox_field_1'  => 1,
	    'ipc_checkbox_field_2'  => 1,
	    'ipc_checkbox_field_3'  => 1,
	    'ipc_checkbox_field_4'  => 1,
	    'ipc_checkbox_field_5'  => 1,
	    'ipc_cache_time'        => '3',
	    'ipc_redirect'          => '',
	    'ipc_whitelisted'       => '',
	    'country_code'          => '',
	    'ipc_whitelisted_pages' => array(),
	    'ipc_search_engines'    => array(
			'baidu'      => 1,
			'bing'       => 1,
			'blekko'     => 1,
			'duckduckgo' => 1,
			'exalead'    => 1,
			'facebook'   => 1,
			'gigablast'  => 1,
			'google'     => 1,
			'sogou'      => 1,
			'yahoo'      => 1,
			'yandex'     => 1,
		),
	);
	update_option( 'ipc_settings', $settings );
	update_option( '_ipc_whitelisted', 0 );
	update_option( '_ipc_total_blocks', 0 );
	update_option( '_ipc_total_nonblocks', 0 );
	update_option( '_ipc_alltime_blocks', 0 );
	update_option( '_ipc_alltime_visits', 1 );

	ipvl_FraudGrade::get_settings();
	add_filter( 'cron_schedules', array( 'ipvl_FraudGrade_Admin', 'schedule_cron_recurrence' ) );
    if( ! wp_next_scheduled( 'ipc_clear_cache' ) )
    {
        wp_schedule_event( time(), 'ipc_cachetimer', 'ipc_clear_cache' );
    }
}

// ------------------------------------------------------------------------

/**
 * Removes settings and cron job from database.
 */
function ipvl_deactivate()
{
	delete_option( 'ipc_settings' );
	add_filter( 'cron_schedules', array( 'ipvl_FraudGrade_Admin', 'deschedule_cron_recurrence' ) );
	if( wp_next_scheduled( 'ipc_clear_cache' ) )
	{
    	wp_clear_scheduled_hook(  'ipc_clear_cache' );
    }
}

// ------------------------------------------------------------------------

/**
 * Registers plugin activation/deactivation methods
 */
register_activation_hook( __FILE__, 'ipvl_activate' );
register_deactivation_hook( __FILE__, 'ipvl_deactivate' );