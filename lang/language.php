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
 * Ållows changing various tooltip, form label, etc text by editing variables in this file.
 *
 * @package    FraudGrade
 * @subpackage FraudGrade/language
 * @author     FraudGrade
 * @version  1.0.0
 */

// ------------------------------------------------------------------------
// Chart Javascript text
// ------------------------------------------------------------------------
$ipvl_FraudGrade_lang['graphText']   = 'Visitors';
$ipvl_FraudGrade_lang['visitorText'] = 'Visitors';
$ipvl_FraudGrade_lang['blockedText'] = 'Blocked Visitors';

// ------------------------------------------------------------------------
// Form hints when viewing a IP address in admin area.
// ------------------------------------------------------------------------
$ipvl_FraudGrade_lang['hint_anonymizerDetected']   = '';
$ipvl_FraudGrade_lang['hint_anonymizerDetails']    = '';
$ipvl_FraudGrade_lang['hint_isBlacklisted']        = '';
$ipvl_FraudGrade_lang['hint_isBlacklistedDetails'] = '';
$ipvl_FraudGrade_lang['hint_ipGrade']              = '';
$ipvl_FraudGrade_lang['hint_ipGradeDetails']       = '';

// ------------------------------------------------------------------------
// Random settings page
// ------------------------------------------------------------------------

$ipvl_FraudGrade_lang['settings_page_title']   = 'FraudGrade Settings';
$ipvl_FraudGrade_lang['notice_missing_apikey'] = 'Click <a href="/wp-admin/edit.php?post_type=ip_check&page=ip_check">here</a> to enter your FraudGrade API Key or the plugin will not function.<br/>';

// ------------------------------------------------------------------------
// Form labels for settings page
// ------------------------------------------------------------------------
//
$ipvl_FraudGrade_lang['label_whitelist']  = 'Whitelisted IPs';
$ipvl_FraudGrade_lang['label_apiKey']     = 'API Key';
$ipvl_FraudGrade_lang['label_blockProxy'] = 'Block Proxy';
$ipvl_FraudGrade_lang['label_blockVPN']   = 'Block VPN';
$ipvl_FraudGrade_lang['label_blockTor']   = 'Block TOR';
$ipvl_FraudGrade_lang['label_blockData']  = 'Block Datacenter';
$ipvl_FraudGrade_lang['label_blockBlacklisted']  = 'Block Blacklisted';
$ipvl_FraudGrade_lang['label_cacheTime']  = 'Cache Length';
$ipvl_FraudGrade_lang['label_redirect']   = 'Redirect Location';
$ipvl_FraudGrade_lang['label_country']    = 'Blocked Countries';
$ipvl_FraudGrade_lang['label_whitelist_pages'] = 'Whitelisted Pages';
$ipvl_FraudGrade_lang['hint_whitelist_pages'] = 'Please select the Robots/Crawlers to whitelist and prevent access being blocked';

// ------------------------------------------------------------------------
// Form hints for settings page
// ------------------------------------------------------------------------
$ipvl_FraudGrade_lang['hint_apiKey']     = 'Signup for a FREE apikey at <a href="https://www.fraudgrade.com/" target="_blank">www.FraudGrade.com</a>';
$ipvl_FraudGrade_lang['hint_blockProxy'] = 'Enabling this option will block all Proxy connections';
$ipvl_FraudGrade_lang['hint_blockTor']   = 'Enabling this option will block all Tor connections';
$ipvl_FraudGrade_lang['hint_blockVPN']   = 'Enabling this option will block all VPN connections';
$ipvl_FraudGrade_lang['hint_blockData']  = 'Enabling this option will block all Datacenter connections';
$ipvl_FraudGrade_lang['hint_blockBlack'] = 'Enabling this option will block all Blacklisted IPs';
$ipvl_FraudGrade_lang['hint_cacheTime']  = 'The amount of time a IP Validation response will be considered fresh';
$ipvl_FraudGrade_lang['hint_redirect']   = 'The URL where blocked visitors will be redirected. <br/>Please select a page or type the redirect url then press enter';
$ipvl_FraudGrade_lang['hint_country']    = 'Visitors from selected countries will be blocked';

// ------------------------------------------------------------------------
// Form labels and hints for the view IP address page
// ------------------------------------------------------------------------

$ipvl_FraudGrade_lang['hint_whiteList']           = 'Whitelisted IPs will not be checked against FraudGrade. <br/>Please type the IP address and press enter';
$ipvl_FraudGrade_lang['hint_proxyDetected']       = '';
$ipvl_FraudGrade_lang['hint_vpnDetected']         = '';
$ipvl_FraudGrade_lang['hint_torDetected']         = '';
$ipvl_FraudGrade_lang['hint_dataCenterDetected']  = '';
$ipvl_FraudGrade_lang['label_proxyDetected']      = 'Proxy Detected';
$ipvl_FraudGrade_lang['label_vpnDetected']        = 'VPN Detected';
$ipvl_FraudGrade_lang['label_torDetected']        = 'TOR Detected';
$ipvl_FraudGrade_lang['label_dataCenterDetected'] = 'Data Center Detected';
$ipvl_FraudGrade_lang['hint_city']           = '';
$ipvl_FraudGrade_lang['hint_postalCode']     = '';
$ipvl_FraudGrade_lang['hint_state']          = '';
$ipvl_FraudGrade_lang['hint_stateIsoCode']   = '';
$ipvl_FraudGrade_lang['hint_countryIsoCode'] = '';
$ipvl_FraudGrade_lang['hint_latitude']       = '';
$ipvl_FraudGrade_lang['hint_longitude']      = '';
$ipvl_FraudGrade_lang['hint_accuracyRadius'] = '';
$ipvl_FraudGrade_lang['hint_connectionType'] = '';
$ipvl_FraudGrade_lang['hint_isp']            = '';
$ipvl_FraudGrade_lang['hint_asn']            = '';
$ipvl_FraudGrade_lang['hint_asnOrganization'] = '';
$ipvl_FraudGrade_lang['hint_organization']    = '';

$ipvl_FraudGrade_lang['label_blacklist_baidu'] = 'Baidu';
$ipvl_FraudGrade_lang['hint_baidu'] = '';
$ipvl_FraudGrade_lang['label_blacklist_bing'] = 'Bing';
$ipvl_FraudGrade_lang['hint_bing'] = '';
$ipvl_FraudGrade_lang['label_blacklist_blekko'] = 'Blekko';
$ipvl_FraudGrade_lang['hint_blekko'] = '';
$ipvl_FraudGrade_lang['label_blacklist_duckduckgo'] = 'Duckduckgo';
$ipvl_FraudGrade_lang['hint_duckduckgo'] = '';
$ipvl_FraudGrade_lang['label_blacklist_exalead'] = 'Exalead';
$ipvl_FraudGrade_lang['hint_exalead'] = '';
$ipvl_FraudGrade_lang['label_blacklist_facebook'] = 'Facebook';
$ipvl_FraudGrade_lang['hint_facebook'] = '';
$ipvl_FraudGrade_lang['label_blacklist_gigablast'] = 'Gigablast';
$ipvl_FraudGrade_lang['hint_gigablast'] = '';
$ipvl_FraudGrade_lang['label_blacklist_google'] = 'Google';
$ipvl_FraudGrade_lang['hint_google'] = '';
$ipvl_FraudGrade_lang['label_blacklist_sogou'] = 'Sogou';
$ipvl_FraudGrade_lang['hint_sogou'] = '';
$ipvl_FraudGrade_lang['label_blacklist_yahoo'] = 'Yahoo';
$ipvl_FraudGrade_lang['hint_yahoo'] = '';
$ipvl_FraudGrade_lang['label_blacklist_yandex'] = 'Yandex';
$ipvl_FraudGrade_lang['hint_yandex'] = '';
