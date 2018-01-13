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
 * The HTML view file for the IP Cache details page, displays all the saved data in a meta box.
 *
 * @package    FraudGrade
 * @subpackage FraudGrade/admin/view
 * @author     FraudGrade
 * @version  1.0.0
 */
		// make sure the form request comes from WordPress
		wp_nonce_field( basename( __FILE__ ), 'ip_check_meta_box_nonce' );
		$anonymizerDetected   = (int) get_post_meta( $post->ID, '_anonymizerDetected', true );
		$isBlacklisted        = (int) get_post_meta( $post->ID, '_isBlacklisted', true );
		$anonymizerDetails    = get_post_meta( $post->ID, '_anonymizerDetails', true );
		$isBlacklistedDetails = get_post_meta( $post->ID, '_isBlacklistedDetails', true );
		$ipGrade              = get_post_meta( $post->ID, '_ipGrade', true );
		$ipGradeDetails       = get_post_meta( $post->ID, '_ipGradeDetails', true );
		$vpnDetected          = get_post_meta( $post->ID, '_vpnDetected', true );
		$proxyDetected        = get_post_meta( $post->ID, '_proxyDetected', true );
		$torDetected          = get_post_meta( $post->ID, '_torDetected', true );
		$city                 = get_post_meta( $post->ID, '_city', true );
		$postalCode           = get_post_meta( $post->ID, '_postalCode', true );
		$state                = get_post_meta( $post->ID, '_state', true );
		$stateIsoCode         = get_post_meta( $post->ID, '_stateIsoCode', true );
		$country              = get_post_meta( $post->ID, '_country', true );
		$countryIsoCode       = get_post_meta( $post->ID, '_countryIsoCode', true );
		$latitude             = get_post_meta( $post->ID, '_latitude', true );
		$longitude            = get_post_meta( $post->ID, '_longitude', true );
		$accuracyRadius       = get_post_meta( $post->ID, '_accuracyRadius', true );
		$connectionType       = get_post_meta( $post->ID, '_connectionType', true );
		$asn                  = get_post_meta( $post->ID, '_asn', true );
		$asnOrganization      = get_post_meta( $post->ID, '_asnOrganization', true );
		$organization         = get_post_meta( $post->ID, '_organization', true );
		$isp                  = get_post_meta( $post->ID, '_isp', true );

//dump( self::$language);die();
		?>
		<div class='inside'>
			<table class="form-table"><tbody>
				<tr><th scope="row">
					<label for="_ip_grade">IP Grade</label>
				</th>
				<td>
					<input disabled="disabled" type="text" name="_ipGrade" class="large-text" value="<?php echo $ipGrade; ?>" />
					<p class="description" id="hint-ipGrade"><?php echo self::$language['hint_ipGrade'];?></p>
				</td>
				</tr>
				<tr><th scope="row">
					<label for="_ipGradeDetails">IP Grade Details</label>
				</th>
				<td>
					<textarea disabled="disabled" class="large-text" name="_ipGradeDetails"><?php echo $ipGradeDetails; ?></textarea>
					<p class="description" id="hint-ipGradeDetails"><?php echo self::$language['hint_ipGradeDetails'];?>
				</td></tr>

				<th scope="row">
					<label for="_anonymizerDetected">Anonymizer Detected</label>
				</th>
				<td>
					<input disabled="disabled" type="radio" name="_anonymizerDetected" value="0" <?php checked( $anonymizerDetected, '0' ); ?> /> No
					&nbsp;
					<input disabled="disabled" type="radio" name="_anonymizerDetected" value="1" <?php checked( $anonymizerDetected, '1' ); ?> /> Yes
					<p class="description" id="hint-anonymizerDetected"><?php echo self::$language['hint_anonymizerDetected'];?></p>
				</td></tr>
				<tr><th scope="row">
					<label for="_anonymizerDetails">Anonymizer Details</label>
				</th>
				<td>
					<textarea disabled="disabled" class="large-text" name="_anonymizerDetails"><?php echo $anonymizerDetails; ?></textarea>
					<p class="description" id="hint-anonymizerDetails"><?php echo self::$language['hint_anonymizerDetails'];?></p>
				</td></tr>
				<tr><th scope="row">
					<label for="_isBlacklisted">Blacklisted</label>
				</th>
				<td>
					<input disabled="disabled" type="radio" name="_isBlacklisted" value="0" <?php checked( $isBlacklisted, '0' ); ?> /> No
					&nbsp;
					<input disabled="disabled" type="radio" name="_isBlacklisted" value="1" <?php checked( $isBlacklisted, '1' ); ?> /> Yes
					<p class="description" id="hint-isBlackedlist"><?php echo self::$language['hint_isBlacklisted'];?>
				</td></tr>
				<tr><th scope="row">
					<label for="_isBlacklistedDetails">Blacklisted Details</label>
				</th>
				<td>
					<textarea disabled="disabled" class="large-text" name="_isBlacklistedDetails"><?php echo $isBlacklistedDetails; ?></textarea>
					<p class="description" id="hint-isBlackedDetails"><?php echo self::$language['hint_isBlacklistedDetails'];?>
				</td></tr>
				<tr><th scope="row">
					<label for="_city">City</label>
				</th>
				<td>
					<input disabled="disabled" type="text" name="_city" class="large-text" value="<?php echo $city; ?>" />
					<p class="description" id="hint-city"><?php echo self::$language['hint_city'];?></p>
				</td>
				</tr>
				<tr><th scope="row">
					<label for="_postalCode">Postal Code</label>
				</th>
				<td>
					<input disabled="disabled" type="text" name="_postalCode" class="large-text" value="<?php echo $postalCode; ?>" />
					<p class="description" id="hint-ipGrade"><?php echo self::$language['hint_postalCode'];?></p>
				</td>
				</tr>
				<tr><th scope="row">
					<label for="_state">State</label>
				</th>
				<td>
					<input disabled="disabled" type="text" name="_state" class="large-text" value="<?php echo $state; ?>" />
					<p class="description" id="hint-state"><?php echo self::$language['hint_state'];?></p>
				</td>
				</tr>
				<tr><th scope="row">
					<label for="_stateIsoCode">State ISO Code</label>
				</th>
				<td>
					<input disabled="disabled" type="text" name="_state" class="large-text" value="<?php echo $stateIsoCode; ?>" />
					<p class="description" id="hint-stateIsoCode"><?php echo self::$language['hint_stateIsoCode'];?></p>
				</td>
				</tr>
<!--
				<tr><th scope="row">
					<label for="_country">Country</label>
				</th>
				<td>
					<input disabled="disabled" type="text" name="_country" class="large-text" value="<?php echo $country; ?>" />
					<p class="description" id="hint-country"><?php echo self::$language['hint_country'];?></p>
				</td>
				</tr>
				<tr><th scope="row">
					<label for="_country">Country ISO Code</label>
				</th>
				<td>
					<input disabled="disabled" type="text" name="_country" class="large-text" value="<?php echo $countryIsoCode; ?>" />
					<p class="description" id="hint-countryIsoCode"><?php echo self::$language['hint_countryIsoCode'];?></p>
				</td>
-->
				</tr>
				<tr><th scope="row">
					<label for="_latitude">Latitude</label>
				</th>
				<td>
					<input disabled="disabled" type="text" name="_latitude" class="large-text" value="<?php echo $latitude; ?>" />
					<p class="description" id="hint-latitude"><?php echo self::$language['hint_latitude'];?></p>
				</td>
				</tr>
				<tr><th scope="row">
					<label for="_longitude">Longitude</label>
				</th>
				<td>
					<input disabled="disabled" type="text" name="_longitude" class="large-text" value="<?php echo $longitude; ?>" />
					<p class="description" id="hint-longitude"><?php echo self::$language['hint_longitude'];?></p>
				</td>
				</tr>
				<tr><th scope="row">
					<label for="_accuracyRadius">Accuracy Radius</label>
				</th>
				<td>
					<input disabled="disabled" type="text" name="_accuracyRadius" class="large-text" value="<?php echo $accuracyRadius; ?>" />
					<p class="description" id="hint-accuracyRadius"><?php echo self::$language['hint_accuracyRadius'];?></p>
				</td>
				</tr>
				<tr><th scope="row">
					<label for="_isp">ISP</label>
				</th>
				<td>
					<input disabled="disabled" type="text" name="_isp" class="large-text" value="<?php echo $isp; ?>" />
					<p class="description" id="hint-isp"><?php echo self::$language['hint_isp'];?></p>
				</td>
				</tr>


				<tr><th scope="row">
					<label for="_connectionType">Connection Type</label>
				</th>
				<td>
					<input disabled="disabled" type="text" name="_connectionType" class="large-text" value="<?php echo $connectionType; ?>" />
					<p class="description" id="hint-connectionType"><?php echo self::$language['hint_connectionType'];?></p>
				</td>
				</tr>
				<tr><th scope="row">
					<label for="_organization">Organization</label>
				</th>
				<td>
					<input disabled="disabled" type="text" name="_organization" class="large-text" value="<?php echo $organization; ?>" />
					<p class="description" id="hint-organization"><?php echo self::$language['hint_organization'];?></p>
				</td>
				</tr>

				<tr><th scope="row">
					<label for="_asn">ASN</label>
				</th>
				<td>
					<input disabled="disabled" type="text" name="_asn" class="large-text" value="<?php echo $asn; ?>" />
					<p class="description" id="hint-asn"><?php echo self::$language['hint_asn'];?></p>
				</td>
				</tr>

				<tr><th scope="row">
					<label for="_asnOrganization">ASN Organization</label>
				</th>
				<td>
					<input disabled="disabled" type="text" name="_asnOrganization" class="large-text" value="<?php echo $asnOrganization; ?>" />
					<p class="description" id="hint-asnOrganization"><?php echo self::$language['hint_asnOrganization'];?></p>
				</td>
				</tr>
			</tbody></table>
		</div>
