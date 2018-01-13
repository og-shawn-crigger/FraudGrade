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
 * Handles settings, viewing of the data, and any other admin functionality.
 *
 * @package    FraudGrade
 * @subpackage FraudGrade/admin
 * @author     FraudGrade
 * @version  1.0.0
 */
class ipvl_FraudGrade_Admin {

	/**
	 * Holds directory to view files
	 * @static
	 * @access     private
	 * @var        string
	 */
	static $view_dir = NULL;

	/**
	 * Holds language entries
	 * @static
	 * @access     private
	 * @var        array
	 */
	static $language = NULL;

	/**
	 * Holds the plugin settings
	 * @static
	 * @access     private
	 * @var        array
	 */
	static private $settings = NULL;

	/**
	 * Holds data grid object
	 * @access     private
	 * @var        IP_Check_Datagrid
	 */
	private $_data_grid = NULL;
	// ------------------------------------------------------------------------

	/**
	 * Sets up the class and action hooks.
	 */
	public function __construct()
	{
		add_action( 'admin_menu',              array( &$this, 'add_admin_menu' ) );
		add_action( 'admin_init',              array( &$this, 'settings_init' ) );
		add_action( 'admin_head',              array( &$this, 'hide_all_slugs' ) );
		add_action( 'do_meta_boxes',           array( &$this, 'remove_custom_field_meta_box' ) );
        add_action( 'contextual_help',         array( __CLASS__ , 'add_screen_object_help') , 10, 3 );
		add_action( 'wp_dashboard_setup',      array( __CLASS__, 'add_dashboard_widgets' ) );
		add_action( 'admin_enqueue_scripts',   array( &$this, 'enqueue_javascript_assets' ) );
		add_action( 'add_meta_boxes_ip_check', array( &$this, 'add_meta_boxes' ) );

		if ( get_transient(  'ipc_set_cron_timer' ) )
		{
			add_filter( 'cron_schedules', array( $this, 'schedule_cron_recurrence' ) );
		}

    	if ( NULL === self::$view_dir )
    	{
	    	self::$view_dir = __DIR__;
	    	self::$view_dir = trailingslashit( self::$view_dir );
	    	self::$view_dir = str_replace( 'libraries/', 'views/', self::$view_dir );
	    }

	    // load language file
	    $lang_dir = str_replace( 'views/', 'lang/', self::$view_dir );
	    require( $lang_dir . 'language.php' );
	    self::$language = $ipvl_FraudGrade_lang;
		self::$settings = ipvl_FraudGrade::get_settings();

		if ( trim( self::$settings['ipc_text_apikey'] ) === '' )
		{
			add_action( 'admin_notices', array( &$this, 'display_missing_apikey_notice' ) );
		}

		require( __DIR__ . '/meta-columns.php' );
		require( __DIR__ . '/datagrid.php' );
		$this->_dataGrid = new ipvl_FraudGrade_Datagrid();
		add_action( 'load-edit.php', array( $this->_dataGrid, 'init' ) );
	}

	// ------------------------------------------------------------------------

	/**
	 * Enqueues javascript and css assets
	 *
	 * @param      string  $hook   page hook
	 */
	public function enqueue_javascript_assets( $hook )
	{
		$ver        = '1.0.0';
		$plugin_url = plugins_url( 'assets', __FILE__ );
		$plugin_url = str_replace('libraries/', '', $plugin_url );

		wp_enqueue_style(  'ipc-flags',   $plugin_url . '/css/flags16.css' );
		// enqueue highcharts library on dashboard page
		if ( 'index.php' === $hook OR 'ip_check_page_ip_check' === $hook )
		{
//		    wp_register_script( 'ipc-highchart', $plugin_url . '/js/highcharts.js', array( 'jquery' ), NULL, false );
//			wp_enqueue_script(  'ipc-highchart' );
		    wp_register_script( 'ipc-flotcharts', $plugin_url . '/js/jquery.flot.combo.min.js', array( 'jquery' ), NULL, false );
			wp_enqueue_script(  'ipc-flotcharts' );
		}

		// enqueue select2 library on settings page
		if ( 'ip_check_page_ip_check' != $hook ) return;
	    wp_register_script( 'ipc-select2',  $plugin_url . '/js/select2.min.js', array( 'jquery' ), NULL, false );
		wp_enqueue_script(  'ipc-select2' );
	    wp_register_script( 'ipc-editjs',  $plugin_url . '/js/edit.js', array( 'jquery', 'ipc-select2' ), NULL, false );
		wp_enqueue_script(  'ipc-editjs' );
		wp_enqueue_style(  'ipc-select2',   $plugin_url . '/css/select2.min.css' );

	}

	// ------------------------------------------------------------------------

	/**
	 * Adds dashboard widgets.
	 */
	static public function add_dashboard_widgets()
	{
        //Register the widget...
        wp_add_dashboard_widget(
            'ip_check_chart',
            __( 'FraudGrade IP Reviews', 'ip_check' ),
            array( __CLASS__,'widget')
        );
	}

	// ------------------------------------------------------------------------

    /**
     * Load the widget code
     * @static
     * @return      void
     */
    public static function widget()
    {
		$blocks = (int) get_option( '_ipc_alltime_blocks' );
		$visits = (int) get_option( '_ipc_alltime_visits' );
		$json   = array(
			array(
				'label' => self::$language['visitorText'],
				'data'  => $visits,
				'color' => '#012f61',
			),
			array(
				'label' => self::$language['blockedText'],
				'data'  => $blocks++,
				'color' => '#7ac8ff',
			),
		);

		wp_register_script( 'ip_check_chart', NULL );
		wp_localize_script( 'ip_check_chart', 'ipc_chart_data', $json );
		wp_enqueue_script(  'ip_check_chart' );
        require_once( self::$view_dir . 'widget.php' );
    }

	// ------------------------------------------------------------------------

    /**
     * Hides various elements on the edit.php screen for this plugin only
     * @uses   get_current_screen()
     * @param string $screen_id
     */
    function hide_all_slugs( )
    {
    	$screen_id = 'ip_check';
        if ( ! function_exists( 'get_current_screen' ) ) return;
        if ( $screen_id !== get_current_screen()->id ) return;
        echo  "<style type=\"text/css\"> #slugdiv, #edit-slug-box { display: none; }</style>\n";
        echo "<script>";
        echo "jQuery(document).ready(function(){
        	jQuery(\"a.page-title-action, #edit_timestamp, div.misc-pub-visibility, a.preview.button\").hide();
        	jQuery(\"#title\").attr('disabled','disabled');
        });";
        echo "</script>";
    }

	// ------------------------------------------------------------------------

	/**
	 * Adds settings page to admin menu.
	 */
	public function add_admin_menu()
	{
		add_submenu_page(
			'edit.php?post_type=ip_check',
			'IP Review Settings',
			'IP Review Settings',
			'manage_options',
			'ip_check',
			array( $this, 'ipc_options_page' )
		);

		remove_submenu_page( 'edit.php?post_type=ip_check', 'post-new.php?post_type=ip_check' );
	}

	// ------------------------------------------------------------------------

	/**
	 * Creates plugin settings
	 */
	public function settings_init()
	{

		register_setting( 'ip_check', 'ipc_settings', array( $this, 'sanitize_settings' ) );
		register_setting( 'ip_check_settings_searchengine', 'ipc_settings', array( $this, 'sanitize_settings' ) );

		add_settings_section(
			'ip_check_settings_section',
			__( '', 'ip_check' ),
			'__return_false',
			'ip_check'
		);

		add_settings_field(
			'ipc_text_apikey',
			__( self::$language['label_apiKey'], 'ip_check' ),
			array( $this, 'ipc_text_apikey_render' ),
			'ip_check',
			'ip_check_settings_section'
		);

		add_settings_field(
			'ipc_checkbox_field_1',
			__( self::$language['label_blockProxy'], 'ip_check' ),
			array( $this, 'ipc_checkbox_proxy_render' ),
			'ip_check',
			'ip_check_settings_section'
		);

		add_settings_field(
			'ipc_checkbox_field_2',
			__( self::$language['label_blockTor'], 'ip_check' ),
			array( $this, 'ipc_checkbox_tor_render' ),
			'ip_check',
			'ip_check_settings_section'
		);

		add_settings_field(
			'ipc_checkbox_field_3',
			__( self::$language['label_blockVPN'], 'ip_check' ),
			array( $this, 'ipc_checkbox_vpn_render' ),
			'ip_check',
			'ip_check_settings_section'
		);

		add_settings_field(
			'ipc_checkbox_field_4',
			__( self::$language['label_blockData'], 'ip_check' ),
			array( $this, 'ipc_checkbox_datacenter_render' ),
			'ip_check',
			'ip_check_settings_section'
		);

		add_settings_field(
			'ipc_checkbox_field_5',
			__( self::$language['label_blockBlacklisted'], 'ip_check' ),
			array( $this, 'ipc_checkbox_black_listed_render' ),
			'ip_check',
			'ip_check_settings_section'
		);

		add_settings_field(
			'ipc_cache_time',
			__( self::$language['label_cacheTime'], 'ip_check' ),
			array( $this, 'ipc_cachetime_render' ),
			'ip_check',
			'ip_check_settings_section'
		);

		add_settings_field(
			'ipc_redirect_render',
			__( self::$language['label_redirect'], 'ip_check' ),
			array( $this, 'ipc_redirect_render' ),
			'ip_check',
			'ip_check_settings_section'
		);

		add_settings_field(
			'ipc_select_field_8',
			__( self::$language['label_country'], 'ip_check' ),
			array( $this, 'ipc_countrycode_render' ),
			'ip_check',
			'ip_check_settings_section'
		);

		add_settings_field(
			'ipc_whitelist',
			__( self::$language['label_whitelist'], 'ip_check' ),
			array( $this, 'ipc_whitelist_render' ),
			'ip_check',
			'ip_check_settings_section'
		);

		add_settings_field(
			'ipc_whitelisted_pages',
			__( self::$language['label_whitelist_pages'], 'ip_check' ),
			array( $this, 'ipc_whitelist_pages_render' ),
			'ip_check',
			'ip_check_settings_section'
		);

		add_settings_section(
			'ip_check_settings_searchengine',
			__( '', 'ip_check' ),
			array( $this, 'ipc_settings_section_callback' ),
			'ip_check_settings_searchengine'
		);

		$seo = array( 'baidu', 'bing', 'blekko', 'duckduckgo', 'exalead', 'facebook', 'gigablast', 'google', 'sogou', 'yahoo', 'yandex' );

		foreach ($seo as $engine)
		{
			add_settings_field(
				"ipc_search_engine_{$engine}",
				__( self::$language["label_blacklist_{$engine}"], 'ip_check' ),
				array( $this, 'ipc_checkbox_render' ),
				'ip_check_settings_searchengine',
				'ip_check_settings_searchengine',
				array(
					'engine'    => $engine,
					'label_for' => "ipc_search_engine_{$engine}"
				)
			);

		}
	}

	// ------------------------------------------------------------------------

	public function ipc_checkbox_render( $args = array() )
	{
		$engine  = $args['engine'];
		$options = self::$settings;
		$checked = '';
		if ( isset( $options['ipc_search_engines'][$engine] ) )
		{
			$checked = checked( $options['ipc_search_engines'][$engine], 1, FALSE );
		}
		echo "<input id='ipc_search_engine_{$engine}' type='checkbox' name='ipc_settings[ipc_search_engines][{$engine}]' {$checked} value='1'>\n";
		echo '<p class="description" id="hint-'.$engine.'">'.self::$language['hint_'.$engine]. '</p>';
	}

	// ------------------------------------------------------------------------

	public function ipc_whitelist_pages_render()
	{
		$value = isset( self::$settings['ipc_whitelisted_pages'] ) ? self::$settings['ipc_whitelisted_pages'] : array();
		$pages = get_pages();

		if ( is_string( $value ) && is_numeric( $value ) )
		{
			$value = array( (int) $value );
		}

		echo '<select multiple id="wl_pages" class="regular-text" name="ipc_settings[ipc_whitelisted_pages][]">';

		$selected = '';
		if ( in_array( '#homepage', $value ) )
		{
			$selected = 'selected="selected" ';
		}
		echo "<option {$selected} value='#homepage'>Homepage</option>\n";

		foreach ($pages as $page)
		{
			$selected = '';
			if ( in_array( $page->ID, $value ) )
			{
				$selected = 'selected="selected" ';
			}
			echo "<option value=\"{$page->ID}\" {$selected}>{$page->post_title}</option>\n";
		}

		echo "</select>\n";
		echo '<p class="description" id="hint-wl_pages">'.self::$language['hint_whitelist_pages']. '</p>';
	}

	// ------------------------------------------------------------------------

	public function ipc_whitelist_render()
	{
		$options = isset( self::$settings['ipc_whitelist'] ) ? self::$settings['ipc_whitelist'] : '';
		echo "<select multiple class='regular-text' id='whiteList' name='ipc_settings[ipc_whitelist][]'>\n";

		foreach ($options as $ip)
		{
			echo "<option value='{$ip}' selected='selected' >{$ip}</option>\n";
		}

		echo '</select>';
		echo '<p class="description" id="hint-whitelist">'.self::$language['hint_whiteList']. '</p>';
	}

	// ------------------------------------------------------------------------

	/**
	 * Renders setting field for api key
	 */
	public function ipc_text_apikey_render()
	{
		$options = self::$settings;
		$value   = isset( $options['ipc_text_apikey'] ) ? $options['ipc_text_apikey'] : '';
		echo "<input class='regular-text' type='text' name='ipc_settings[ipc_text_apikey]' value='{$value}'>";
		echo '<p class="description" id="hint-apiKey">'.self::$language['hint_apiKey']. '</p>';
	}

	// ------------------------------------------------------------------------

	/**
	 * Renders setting field checkbox
	 */
	public function ipc_checkbox_proxy_render()
	{
		$options = self::$settings;
		$checked = '';
		if ( isset( $options['ipc_checkbox_field_1'] ) )
		{
			$checked = checked( $options['ipc_checkbox_field_1'], 1, FALSE );
		}
		echo "<input type='checkbox' name='ipc_settings[ipc_checkbox_field_1]' {$checked} value='1'>\n";
		echo '<p class="description" id="hint-blockProxy">'.self::$language['hint_blockProxy']. '</p>';
	}

	// ------------------------------------------------------------------------

	/**
	 * Renders setting field checkbox
	 */
	public function ipc_checkbox_tor_render()
	{
		$options = self::$settings;
		$checked = '';
		if ( isset( $options['ipc_checkbox_field_2'] ) )
		{
			$checked = checked( $options['ipc_checkbox_field_2'], 1, FALSE );
		}
		echo "<input type='checkbox' name='ipc_settings[ipc_checkbox_field_2]' {$checked} value='1'>\n";
		echo '<p class="description" id="hint-blockTor">'.self::$language['hint_blockTor']. '</p>';
	}

	// ------------------------------------------------------------------------

	/**
	 * Renders setting field checkbox
	 */
	public function ipc_checkbox_vpn_render()
	{
		$options = self::$settings;
		$checked = '';
		if ( isset( $options['ipc_checkbox_field_3'] ) )
		{
			$checked = checked( $options['ipc_checkbox_field_3'], 1, FALSE );
		}
		echo "<input type='checkbox' name='ipc_settings[ipc_checkbox_field_3]' {$checked} value='1'>\n";
		echo '<p class="description" id="hint-blockVPN">'.self::$language['hint_blockVPN']. '</p>';
	}

	// ------------------------------------------------------------------------

	/**
	 * Renders setting field checkbox
	 */
	public function ipc_checkbox_datacenter_render()
	{
		$options = self::$settings;
		$checked = '';
		if ( isset( $options['ipc_checkbox_field_4'] ) )
		{
			$checked = checked( $options['ipc_checkbox_field_4'], 1, FALSE );
		}
		echo "<input type='checkbox' name='ipc_settings[ipc_checkbox_field_4]' {$checked} value='1'>\n";
		echo '<p class="description" id="hint-blockData">'.self::$language['hint_blockData']. '</p>';
	}

	// ------------------------------------------------------------------------

	/**
	 * Renders setting field checkbox
	 */
	public function ipc_checkbox_black_listed_render()
	{

		$options = self::$settings;
		$checked = '';
		if ( isset( $options['ipc_checkbox_field_5'] ) )
		{
			$checked = checked( $options['ipc_checkbox_field_5'], 1, FALSE );
		}
		echo "<input type='checkbox' name='ipc_settings[ipc_checkbox_field_5]' {$checked} value='1'>\n";
		echo '<p class="description" id="hint-blockBlack">'.self::$language['hint_blockBlack']. '</p>';
	}

	// ------------------------------------------------------------------------

	/**
	 * Creates setting field for cache time
	 */
	public function ipc_cachetime_render()
	{
		$options = self::$settings;
		$times   = array( 0, 1, 3, 5, 7, 14, 30 );
		echo "<select name='ipc_settings[ipc_cache_time]'>\n";
		foreach ($times as $time)
		{
			$selected = selected( $options['ipc_cache_time'], $time, FALSE );
			$label = $time . ' day';
			if ( $time > 1 )
			{
				$label .= 's';
			}
			if ( 0 === $time )
			{
				$label = 'Disabled';
			}
			echo "<option value='{$time}' {$selected}>{$label}</option>\n";
		}
		echo '</select>';
		echo '<p class="description" id="hint-cacheTime">'.self::$language['hint_cacheTime']. '</p>';
	}

	// ------------------------------------------------------------------------

	/**
	 * Renders setting field checkbox
	 */
	public function ipc_countrycode_render()
	{
		$options   = self::$settings;
 		$countries = array( 'US' => 'United States', 'UM' => 'United States Minor Outlying Islands','AF' => 'Afghanistan', 'AX' => 'Åland Islands', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AS' => 'American Samoa', 'AD' => 'Andorra', 'AO' => 'Angola', 'AI' => 'Anguilla', 'AQ' => 'Antarctica', 'AG' => 'Antigua and Barbuda', 'AR' => 'Argentina', 'AM' => 'Armenia', 'AW' => 'Aruba', 'AU' => 'Australia', 'AT' => 'Austria', 'AZ' => 'Azerbaijan', 'BS' => 'Bahamas', 'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BB' => 'Barbados', 'BY' => 'Belarus', 'BE' => 'Belgium', 'BZ' => 'Belize', 'BJ' => 'Benin', 'BM' => 'Bermuda', 'BT' => 'Bhutan', 'BO' => 'Bolivia', 'BA' => 'Bosnia and Herzegovina', 'BW' => 'Botswana', 'BV' => 'Bouvet Island', 'BR' => 'Brazil', 'IO' => 'British Indian Ocean Territory', 'BN' => 'Brunei Darussalam', 'BG' => 'Bulgaria', 'BF' => 'Burkina Faso', 'BI' => 'Burundi', 'KH' => 'Cambodia', 'CM' => 'Cameroon', 'CA' => 'Canada', 'CV' => 'Cape Verde', 'KY' => 'Cayman Islands', 'CF' => 'Central African Republic', 'TD' => 'Chad', 'CL' => 'Chile', 'CN' => 'China', 'CX' => 'Christmas Island', 'CC' => 'Cocos (Keeling) Islands', 'CO' => 'Colombia', 'KM' => 'Comoros', 'CG' => 'Congo', 'CD' => 'Congo, The Democratic Republic of The', 'CK' => 'Cook Islands', 'CR' => 'Costa Rica', 'CI' => 'Cote Divoire', 'HR' => 'Croatia', 'CU' => 'Cuba', 'CY' => 'Cyprus', 'CZ' => 'Czechia', 'DK' => 'Denmark', 'DJ' => 'Djibouti', 'DM' => 'Dominica', 'DO' => 'Dominican Republic', 'EC' => 'Ecuador', 'EG' => 'Egypt', 'SV' => 'El Salvador', 'GQ' => 'Equatorial Guinea', 'ER' => 'Eritrea', 'EE' => 'Estonia', 'ET' => 'Ethiopia', 'FK' => 'Falkland Islands (Malvinas)', 'FO' => 'Faroe Islands', 'FJ' => 'Fiji', 'FI' => 'Finland', 'FR' => 'France', 'GF' => 'French Guiana', 'PF' => 'French Polynesia', 'TF' => 'French Southern Territories', 'GA' => 'Gabon', 'GM' => 'Gambia', 'GE' => 'Georgia', 'DE' => 'Germany', 'GH' => 'Ghana', 'GI' => 'Gibraltar', 'GR' => 'Greece', 'GL' => 'Greenland', 'GD' => 'Grenada', 'GP' => 'Guadeloupe', 'GU' => 'Guam', 'GT' => 'Guatemala', 'GG' => 'Guernsey', 'GN' => 'Guinea', 'GW' => 'Guinea-bissau', 'GY' => 'Guyana', 'HT' => 'Haiti', 'HM' => 'Heard Island and Mcdonald Islands', 'VA' => 'Holy See (Vatican City State)', 'HN' => 'Honduras', 'HK' => 'Hong Kong', 'HU' => 'Hungary', 'IS' => 'Iceland', 'IN' => 'India', 'ID' => 'Indonesia', 'IR' => 'Iran, Islamic Republic of', 'IQ' => 'Iraq', 'IE' => 'Ireland', 'IM' => 'Isle of Man', 'IL' => 'Israel', 'IT' => 'Italy', 'JM' => 'Jamaica', 'JP' => 'Japan', 'JE' => 'Jersey', 'JO' => 'Jordan', 'KZ' => 'Kazakhstan', 'KE' => 'Kenya', 'KI' => 'Kiribati', 'KP' => 'Korea, Democratic People\'s Republic of', 'KR' => 'Korea, Republic of', 'KW' => 'Kuwait', 'KG' => 'Kyrgyzstan', 'LA' => 'Lao People\'s Democratic Republic', 'LV' => 'Latvia', 'LB' => 'Lebanon', 'LS' => 'Lesotho', 'LR' => 'Liberia', 'LY' => 'Libyan Arab Jamahiriya', 'LI' => 'Liechtenstein', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MO' => 'Macao', 'MK' => 'Macedonia, The Former Yugoslav Republic of', 'MG' => 'Madagascar', 'MW' => 'Malawi', 'MY' => 'Malaysia', 'MV' => 'Maldives', 'ML' => 'Mali', 'MT' => 'Malta', 'MH' => 'Marshall Islands', 'MQ' => 'Martinique', 'MR' => 'Mauritania', 'MU' => 'Mauritius', 'YT' => 'Mayotte', 'MX' => 'Mexico', 'FM' => 'Micronesia, Federated States of', 'MD' => 'Moldova, Republic of', 'MC' => 'Monaco', 'MN' => 'Mongolia', 'ME' => 'Montenegro', 'MS' => 'Montserrat', 'MA' => 'Morocco', 'MZ' => 'Mozambique', 'MM' => 'Myanmar', 'NA' => 'Namibia', 'NR' => 'Nauru', 'NP' => 'Nepal', 'NL' => 'Netherlands', 'AN' => 'Netherlands Antilles', 'NC' => 'New Caledonia', 'NZ' => 'New Zealand', 'NI' => 'Nicaragua', 'NE' => 'Niger', 'NG' => 'Nigeria', 'NU' => 'Niue', 'NF' => 'Norfolk Island', 'MP' => 'Northern Mariana Islands', 'NO' => 'Norway', 'OM' => 'Oman', 'PK' => 'Pakistan', 'PW' => 'Palau', 'PS' => 'Palestinian Territory, Occupied', 'PA' => 'Panama', 'PG' => 'Papua New Guinea', 'PY' => 'Paraguay', 'PE' => 'Peru', 'PH' => 'Philippines', 'PN' => 'Pitcairn', 'PL' => 'Poland', 'PT' => 'Portugal', 'PR' => 'Puerto Rico', 'QA' => 'Qatar', 'RE' => 'Reunion', 'RO' => 'Romania', 'RU' => 'Russian Federation', 'RW' => 'Rwanda', 'SH' => 'Saint Helena', 'KN' => 'Saint Kitts and Nevis', 'LC' => 'Saint Lucia', 'PM' => 'Saint Pierre and Miquelon', 'VC' => 'Saint Vincent and The Grenadines', 'WS' => 'Samoa', 'SM' => 'San Marino', 'ST' => 'Sao Tome and Principe', 'SA' => 'Saudi Arabia', 'SN' => 'Senegal', 'RS' => 'Serbia', 'SC' => 'Seychelles', 'SL' => 'Sierra Leone', 'SG' => 'Singapore', 'SK' => 'Slovakia', 'SI' => 'Slovenia', 'SB' => 'Solomon Islands', 'SO' => 'Somalia', 'ZA' => 'South Africa', 'GS' => 'South Georgia and The South Sandwich Islands', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SD' => 'Sudan', 'SR' => 'Suriname', 'SJ' => 'Svalbard and Jan Mayen', 'SZ' => 'Swaziland', 'SE' => 'Sweden', 'CH' => 'Switzerland', 'SY' => 'Syrian Arab Republic', 'TW' => 'Taiwan, Province of China', 'TJ' => 'Tajikistan', 'TZ' => 'Tanzania, United Republic of', 'TH' => 'Thailand', 'TL' => 'Timor-leste', 'TG' => 'Togo', 'TK' => 'Tokelau', 'TO' => 'Tonga', 'TT' => 'Trinidad and Tobago', 'TN' => 'Tunisia', 'TR' => 'Turkey', 'TM' => 'Turkmenistan', 'TC' => 'Turks and Caicos Islands', 'TV' => 'Tuvalu', 'UG' => 'Uganda', 'UA' => 'Ukraine', 'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom', 'UY' => 'Uruguay', 'UZ' => 'Uzbekistan', 'VU' => 'Vanuatu', 'VE' => 'Venezuela', 'VN' => 'Viet Nam', 'VG' => 'Virgin Islands, British', 'VI' => 'Virgin Islands, U.S.', 'WF' => 'Wallis and Futuna', 'EH' => 'Western Sahara', 'YE' => 'Yemen', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe' );
 		ksort( $countries );
		echo "<select class='f16 large-text' multiple id='country_code' name='ipc_settings[country_code][]'>\n";
		foreach ($countries as $key => $value)
		{
			$selected = '';
			if ( in_array( $key, $options['country_code'] ) )
			{
				$selected = ' selected="true" ';
			}
			echo "<option value='{$key}' {$selected}>{$value}</option>\n";
		}
		echo "</select>\n";
		echo '<p class="description" id="hint-country">'.self::$language['hint_country']. '</p>';
	}

	// ------------------------------------------------------------------------

	/**
	 * Renders setting field checkbox
	 */
	public function ipc_redirect_render()
	{
		$options = self::$settings;
		$value   = isset( $options['ipc_redirect'] ) ? $options['ipc_redirect'] : '';
		$args = array(
		 	'hierarchical' => 0,
			'post_type'    => 'ip_check',
		);
		$pages = get_pages();
		if ( is_numeric( $value ) )
		{
			$value = (int) $value;
		}

		echo '<select id="redirect_page" class="regular-text" name="ipc_settings[ipc_redirect]">';
		foreach ($pages as $page)
		{
			$selected = '';
			if ( $value === $page->ID )
			{
				$selected = 'selected="selected" ';
			}
			echo "<option value=\"{$page->ID}\" {$selected}>{$page->post_title}</option>\n";
		}

		if ( is_string( $value ) )
		{
			$value = $options['ipc_redirect'];
			echo "<option value=\"{$value}\" selected=\"selected\">{$value}</option>\n";
		}

		echo "</select>\n";
		echo '<p class="description" id="hint-redirect">'.self::$language['hint_redirect']. '</p>';
	}

	// ------------------------------------------------------------------------

	/**
	 * Currently does nothing, used to output a section description but seemed like a waste of space.
	 */
	public function ipc_settings_section_callback()
	{
		echo self::$language['hint_whitelist_pages'];
	}

	// ------------------------------------------------------------------------

	/**
	 * Renders whitelist options page.
	 */
	public function ipc_whitelist_page()
	{
		 // check user capabilities
		 if ( ! current_user_can( 'manage_options' ) ) return;
		 if ( isset( $_GET['settings-updated'] ) )
		 {
		 	add_settings_error( 'ip_whitelist_messages', 'ip_whitelist_message', __( 'Settings Saved', 'ip_check' ), 'updated' );
		 }
		// show error/update messages
		settings_errors( 'ip_whitelist_messages' );
		echo '<div id="poststuff">';
		echo '<div class="wrap">';
		echo '<div class="column-2">';
		echo '<div id="ip_check_settings_metabox" class="postbox"> ' .
			'<button type="button" class="handlediv" aria-expanded="true">' .
			'<span class="screen-reader-text">Toggle panel: Debug Information</span>' .
			'<span class="toggle-indicator" aria-hidden="true"></span>' .
			'</button><h2 class="hndle ui-sortable-handle">' .
			'<span>FraudGrade Settings</span></h2>' .
			'<div class="inside">';

		echo '<form action="options.php" method="post">';
		settings_fields( 'ipc_whitelist' );
		do_settings_sections( 'ipc_whitelist' );
		submit_button();
		echo '</form>';
		echo '</div></div></div>';

	}

	// ------------------------------------------------------------------------

	/**
	 * Main output of the options page.
	 */
	public function ipc_options_page()
	{

		 // check user capabilities
		 if ( ! current_user_can( 'manage_options' ) ) return;

		 // add error/update messages
		// check if the user have submitted the settings
		 // wordpress will add the "settings-updated" $_GET parameter to the url
		 if ( isset( $_GET['settings-updated'] ) )
		 {
		 	// add settings saved message with the class of "updated"
		 	add_settings_error( 'ip_check_messages', 'ip_check_message', __( 'Settings Saved', 'ip_check' ), 'updated' );
		 }

		// show error/update messages
		settings_errors( 'ip_check_messages' );
		echo '<div id="poststuff">';
		echo '<div class="wrap">';
		echo '<div class="column-2">';
		echo '<div id="ip_check_settings_metabox" class="postbox"> ' .
			'<button type="button" class="handlediv" aria-expanded="true">' .
			'<span class="screen-reader-text">Toggle panel: FraudGrade Settings</span>' .
			'<span class="toggle-indicator" aria-hidden="true"></span>' .
			'</button><h2 class="hndle ui-sortable-handle">' .
			'<span>FraudGrade Settings</span></h2>' .
			'<div class="inside">';

		echo '<form action="options.php" method="post">';
		settings_fields( 'ip_check' );
		do_settings_sections( 'ip_check' );
		submit_button();
		echo '</div></div>';

		echo '<div id="ip_check_ip_check_settings_searchengine_metabox" class="postbox"> ' .
			'<button type="button" class="handlediv" aria-expanded="true">' .
			'<span class="screen-reader-text">Toggle panel: Whitelist Robots/Crawlers</span>' .
			'<span class="toggle-indicator" aria-hidden="true"></span>' .
			'</button><h2 class="hndle ui-sortable-handle">' .
			'<span>Whitelist Robots/Crawlers</span></h2>' .
			'<div class="inside">';
		settings_fields( 'ip_check_settings_searchengine' );
		do_settings_sections( 'ip_check_settings_searchengine' );
		submit_button();
		echo '</form>';
		echo '</div></div></div>';


		echo '<div class="column-2">';
		echo '<div id="ip_check_chart_metabox" class="postbox"> ' .
			'<button type="button" class="handlediv" aria-expanded="true">' .
			'<span class="screen-reader-text">Toggle panel: Debug Information</span>' .
			'<span class="toggle-indicator" aria-hidden="true"></span>' .
			'</button><h2 class="hndle ui-sortable-handle">' .
			'<span>FraudGrade Settings</span></h2>' .
			'<div class="inside">';


		$blocks = (int) get_option( '_ipc_alltime_blocks' );
		$visits = (int) get_option( '_ipc_alltime_visits' );
		$json   = array(
			array(
				'label' => self::$language['visitorText'],
				'data'  => $visits,
				'color' => '#012f61',
			),
			array(
				'label' => self::$language['blockedText'],
				'data'  => $blocks++,
				'color' => '#7ac8ff',
			),
		);

		wp_register_script( 'ip_check_chart', NULL );
		wp_localize_script( 'ip_check_chart', 'ipc_chart_data', $json );
		wp_enqueue_script(  'ip_check_chart' );
		require_once( self::$view_dir . 'widget.php' );
		echo '</div>';
		echo '</div></div></div>';
	}

	// ------------------------------------------------------------------------

	/**
	 * Creates WP CRON job to clear the cache
	 *
	 * @param      array  $settings  The settings
	 * @return     array
	 */
	public function sanitize_settings( $settings )
	{
		add_filter( 'cron_schedules', array( $this, 'schedule_cron_recurrence' ) );
		if( wp_next_scheduled( 'ipc_clear_cache' ) )
		{
	    	wp_clear_scheduled_hook(  'ipc_clear_cache' );
	    }
		$cache          = (int) self::$settings['ipc_cache_time'];
		$cache          = $cache * DAY_IN_SECONDS;

		wp_schedule_event( time() + $cache, 'ipc_cachetimer', 'ipc_clear_cache' );
		set_transient(  'ipc_set_cron_timer', 1, 60 * 60 );
		return $settings;
	}
	// ------------------------------------------------------------------------

	/**
	 * Custom Cron Recurrences
	 * @static
	 * @param      array  $schedules  The schedules
	 * @return     array
	 */
	static public function schedule_cron_recurrence( $schedules )
	{
		self::$settings = ipvl_FraudGrade::get_settings();
		$cache          = (int) self::$settings['ipc_cache_time'];
		$cache          = $cache * 3600; //DAY_IN_SECONDS;
		$schedules['ipc_cachetimer'] = array(
			'display'  => __( 'Cached Time', 'textdomain' ),
			'interval' => $cache,
		);

		return $schedules;
	}

	// ------------------------------------------------------------------------

	/**
	 * Remove Custom Cron Recurrences
	 *
	 * @param      array  $schedules  The schedules
	 * @return     array
	 */
	public function deschedule_cron_recurrence( $schedules )
	{
		if ( isset( $schedules['ipc_cachetimer'] ) ) unset( $schedules['ipc_cachetimer'] );
		return $schedules;
	}

	// ------------------------------------------------------------------------

	/**
	 * Removes unused meta boxes from this plugins edit screens
	 */
	function remove_custom_field_meta_box()
	{
	    remove_meta_box( 'ip_check', 'slugdiv', 'advanced');
	}

	// ------------------------------------------------------------------------

	/**
	 * Add meta box
	 *
	 * @param post $post The post object
	 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/add_meta_boxes
	 */
	function add_meta_boxes( $post )
	{
		add_meta_box(
			'ip_check_map_box',
			__( 'Location', 'ip_check' ),
			array( $this, 'build_map_box' ),
			'ip_check',
			'normal',
			'low'
		);

		add_meta_box(
			'ip_check_meta_box',
			__( 'IP Validation Details', 'ip_check' ),
			array( $this, 'build_meta_box' ),
			'ip_check',
			'normal',
			'low'
		);

		if ( FALSE === ipvl_FraudGrade::is_debug() ) return;

		add_meta_box(
			'ip_check_debug_meta_box',
			__( 'Debug Information', 'ip_check' ),
			array( $this, 'build_debug_box' ),
			'ip_check',
			'normal',
			'low'
		);
	}

	// ------------------------------------------------------------------------

	public function build_map_box( $post )
	{
		$meta = get_post_meta( $post->ID );

		echo "<iframe width=\"100%\" height='250px' src=\"https://maps.google.com/maps?q={$meta['_latitude'][0]},{$meta['_longitude'][0]}&z=5&output=embed&attribution_source=FraudGrade&attribution_web_url=https://www.FraudGrade.com/\"></iframe>";
	}
	// ------------------------------------------------------------------------

	/**
	 * Shows the statistics.
	 */
	public function show_stats()
	{
		$blocks = (int) get_option( '_ipc_total_blocks' );
		$visits = (int) get_option( '_ipc_total_nonblocks' );
		$total_blocks = (int) get_option( '_ipc_alltime_blocks' );
		$total_visits = (int) get_option( '_ipc_alltime_visits' );
		echo '<div class="inside">\n';
		echo 'Total Cached Blocked IPs : ' . $blocks . '</br>';
		echo 'Total Cached NonBlocked IPs : ' . $visits . '<br/>';
		echo 'Total Blocked IPs : ' . $total_blocks . '</br>';
		echo 'Total NonBlocked IPs : ' . $total_visits . '<br/>';
		echo '</div>';

	}

	// ------------------------------------------------------------------------

	/**
	 * Outputs debug variables on edit page.
	 * @param      object $post
	 */
	public function build_debug_box( $post )
	{
		if ( FALSE === ipvl_FraudGrade::is_debug() ) return;

		$meta = get_post_meta( $post->ID );
		echo "		<div class='inside'>\n";
		ipvl_dump( $meta );
		echo "      </div>";
		$this->show_stats();
	}

	// ------------------------------------------------------------------------

	/**
	 * Build custom field meta box
	 *
	 * @param post $post The post object
	 */
	public function build_meta_box( $post )
	{
		require_once( self::$view_dir.'meta-block.php' );
	}
	// ------------------------------------------------------------------------

    /**
     * Creates a Help tab on Each admin Page with all the available Screen options.
     * @static
     *
     * @param      array    $contextual_help  The contextual help
     * @param      string   $screen_id        The screen identifier
     * @param      object   $screen           The screen
     *
     * @return     array
     */
    static public function add_screen_object_help( $contextual_help, $screen_id, $screen )
    {
        // The add_help_tab function for screen was introduced in WordPress 3.3.
        if ( ! method_exists( $screen, 'add_help_tab' ) ) return $contextual_help;
        if ( FALSE === ipvl_FraudGrade::is_debug() ) return $contextual_help;
        global $hook_suffix;

        // List screen properties
        $variables = '<ul style="width:50%;float:left;"> <strong>Screen variables </strong>' .
            sprintf( '<li> Screen id : %s</li>', $screen_id ) .
            sprintf( '<li> Screen base : %s</li>', $screen->base ) .
            sprintf( '<li>Parent base : %s</li>', $screen->parent_base ) .
            sprintf( '<li> Parent file : %s</li>', $screen->parent_file ) .
            sprintf( '<li> Hook suffix : %s</li>', $hook_suffix ) .
            '</ul>';
        // Append global $hook_suffix to the hook stems
        $hooks = array(
            "load-$hook_suffix",
            "admin_print_styles-$hook_suffix",
            "admin_print_scripts-$hook_suffix",
            "admin_head-$hook_suffix",
            "admin_footer-$hook_suffix"
        );

        // If add_meta_boxes or add_meta_boxes_{screen_id} is used, list these too

        if ( did_action( 'add_meta_boxes_' . $screen_id ) )
            $hooks[] = 'add_meta_boxes_' . $screen_id;
        if ( did_action( 'add_meta_boxes' ) )
            $hooks[] = 'add_meta_boxes';

        // Get List HTML for the hooks
        $hooks = '<ul style="width:50%;float:left;"> <strong>Hooks </strong> <li>' . implode( '</li><li>', $hooks ) . '</li></ul>';

        // Combine $variables list with $hooks list.
        $help_content = $variables . $hooks;
        // Add help panel
        $screen->add_help_tab( Array(
            'id'      => 'wptuts-screen-help',
            'title'   => 'Screen Information',
            'content' => $help_content,
        ));

        return $contextual_help;
    }

    // ------------------------------------------------------------------------

	/**
	 * Display error message if API key is missing
	 */
	public function display_missing_apikey_notice()
	{
		echo '<div class="notice notice-error is-dismissible"><p>';
		echo self::$language['notice_missing_apikey'];
		echo '</p></div>';
	}

    // ------------------------------------------------------------------------

}
