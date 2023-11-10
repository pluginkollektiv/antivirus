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
		// Check if API key is provided in config.
		$key = parent::_get_option( 'safe_browsing_key' );
		// Opt-out, if no API key was specified.
		if ( empty( $key ) ) {
			return;
		}

		// Request the API.
		$response = wp_remote_post(
			sprintf(
				'https://safebrowsing.googleapis.com/v4/threatMatches:find?key=%s',
				$key
			),
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'client'     => array(
							'clientId'      => 'wpantivirus',
							'clientVersion' => '1.5.0',
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

		// Get the response code and JSON response of the API request.
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_json = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 === $response_code ) {
			// Successful request.
			if ( ! empty( $response_json ) ) {
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
		} elseif ( 400 === $response_code || 403 === $response_code ) {
			// Invalid request (most likely invalid key) or expired/exceeded key.
			$mail_body = sprintf(
				"%s\r\n\r\n%s",
				esc_html__( 'Checking your site against the Google Safe Browsing API has failed.', 'antivirus' ),
				esc_html__( 'This does not mean that your site has been infected, but that the status could not be determined.', 'antivirus' )
			);

			// Add (sanitized) error message, if available.
			if ( isset( $response_json['error']['message'] ) ) {
				$mail_body .= sprintf(
					"\r\n\r\n%s:\r\n  %s\r\n",
					esc_html__( 'Error message from API', 'antivirus' ),
					esc_html( $response_json['error']['message'] )
				);
			}

			// Add advice to solve the problem, depending on the key.
			$mail_body .= sprintf(
				"\r\n%s",
				esc_html__( 'Please check if your API key is correct and its limit not exceeded. If everything is correct and the error persists for the next requests, please contact the plugin support.', 'antivirus' )
			);

			self::_send_warning_notification(
				esc_html__( 'Safe Browsing check failed', 'antivirus' ),
				$mail_body
			);
		}
	}
}
