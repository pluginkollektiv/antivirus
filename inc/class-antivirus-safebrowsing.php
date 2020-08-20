<?php
/**
 * AntiVirus: Safe Browsing module.
 *
 * @package    AntiVirus
 * @subpackage SafeBrowsing
 */

// Quit.
defined( 'ABSPATH' ) || exit;


/**
 * AntiVirus_SafeBrowsing
 *
 * @since 1.4 Extracted from main class.
 */
class AntiVirus_SafeBrowsing extends AntiVirus {

	/**
	 * Pings the Safe Browsing API to see if the website is infected.
	 */
	public static function check_safe_browsing() {
		// Request the API.
		$response = wp_remote_post(
			sprintf(
				'https://safebrowsing.googleapis.com/v4/threatMatches:find?key=%s',
				'AIzaSyCGHXUd7vQAySRLNiC5y1M_wzR2W0kCVKI'
			),
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => json_encode(
					array(
						'client'     => array(
							'clientId'      => 'wpantivirus',
							'clientVersion' => '1.3.10',
						),
						'threatInfo' => array(
							'threatTypes'      => array(
								'THREAT_TYPE_UNSPECIFIED',
								'MALWARE',
								'SOCIAL_ENGINEERING',
								'UNWANTED_SOFTWARE',
								'POTENTIALLY_HARMFUL_APPLICATION',
							),
							'platformTypes'    => array( 'ANY_PLATFORM' ),
							'threatEntryTypes' => array( 'URL' ),
							'threatEntries'    => array(
								array( 'url' => urlencode( get_bloginfo( 'url' ) ) ),
							),
						),
					)
				),
			)
		);

		// API error?
		if ( is_wp_error( $response ) ) {
			return;
		}

		// Get the JSON response of the API request.
		$response_json = json_decode( wp_remote_retrieve_body( $response ), true );

		// All clear, nothing bad detected.
		if ( wp_remote_retrieve_response_code( $response ) === 200 && empty( $response_json ) ) {
			return;
		}

		// Send notification.
		self::_send_warning_notification(
			esc_html__( 'Safe Browsing Alert', 'antivirus' ),
			sprintf(
				"%s\r\nhttps://transparencyreport.google.com/safe-browsing/search?url=%s&hl=%s",
				esc_html__( 'Google has found a problem on your page and probably listed it on a blacklist. It is likely that your website or your hosting account has been hacked and malware or phishing code was installed. We recommend to check your site. For more details please check the Google Safe Browsing diagnostic page:', 'antivirus' ),
				urlencode( get_bloginfo( 'url' ) ),
				substr( get_locale(), 0, 2 )
			)
		);
	}
}
