<?php
/**
 * AntiVirus: Checksum Verifier module.
 *
 * @package    AntiVirus
 * @subpackage ChecksumVerifier
 */

// Quit.
defined( 'ABSPATH' ) || exit;


/**
 * AntiVirus_ChecksumVerifier
 *
 * @since 1.4 Ported from "Checksum Verifier" plugin.
 */
class AntiVirus_ChecksumVerifier extends AntiVirus {

	/**
	 * Perform the check
	 */
	public static function verify_files() {
		// Get checksums via API.
		$checksums = self::get_checksums();
		if ( ! $checksums ) {
			return;
		}

		// Loop files and match checksums.
		$matches = self::match_checksums( $checksums );

		if ( ! empty( $matches ) ) {
			// Notification mail.
			self::_send_warning_notification(
				esc_html__( 'Checksum Verifier Alert', 'antivirus' ),
				sprintf(
					"%s:\r\n\r\n- %s",
					esc_html__( 'Checksums do not match for the following files', 'antivirus' ),
					implode( "\r\n- ", $matches )
				)
			);

			// Write to log.
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log(
					sprintf(
						'%s: %s',
						esc_html__( 'Checksums do not match for the following files', 'antivirus' ),
						implode( ', ', $matches )
					)
				);
			}
		}
	}


	/**
	 * Get file checksums.
	 *
	 * @return  array|boolean  Checksums getting from API or FALSE on errors.
	 */
	private static function get_checksums() {
		// Blog information.
		$version  = get_bloginfo( 'version' );
		$language = get_locale();

		// Transient name.
		$transient = sprintf(
			'checksums_%s',
			base64_encode( $version . $language )
		);

		// Read from cache.
		$checksums = get_site_transient( $transient );
		if ( $checksums ) {
			return $checksums;
		}

		// Start API request.
		$response = wp_remote_get(
			add_query_arg(
				array(
					'version' => $version,
					'locale'  => $language,
				),
				'https://api.wordpress.org/core/checksums/1.0/'
			)
		);

		// Check response code.
		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		// JSON magic.
		$json = json_decode(
			wp_remote_retrieve_body( $response )
		);

		// Exit on JSON error.
		if ( null === $json ) {
			return false;
		}

		// Checksums exists?
		if ( empty( $json->checksums ) ) {
			return false;
		}

		// Eat it.
		$checksums = $json->checksums;

		// Save into the cache.
		set_site_transient(
			$transient,
			$checksums,
			DAY_IN_SECONDS
		);

		return $checksums;
	}


	/**
	 * Matching of MD5 hashes
	 *
	 * @param array $checksums File checksums.
	 *
	 * @return  array            File paths
	 *
	 * @hook    array  antivirus_checksum_verifier_ignore_files
	 */
	private static function match_checksums( $checksums ) {
		// Ignore files filter.
		$ignore_files = (array) apply_filters(
			'antivirus_checksum_verifier_ignore_files',
			array(
				'wp-config-sample.php',
				'wp-includes/version.php',
				'readme.html',      // Default readme file.
				'readme-ja.html',   // Japanese readme, shipped up to 3.9 (ja).
				'liesmich.html',    // German readme (de_DE).
				'olvasdel.html',    // Hungarian readme (hu_HU).
				'procitajme.html',  // Croatian readme (hr).
			)
		);

		// Init matches.
		$matches = array();

		// Loop files.
		foreach ( $checksums as $file => $checksum ) {
			// Skip ignored files and wp-content directory.
			if ( 0 === strpos( $file, 'wp-content/' ) || in_array( $file, $ignore_files, true ) ) {
				continue;
			}

			// File path.
			$file_path = ABSPATH . $file;

			// File check.
			if ( 0 !== validate_file( $file_path ) || ! file_exists( $file_path ) ) {
				continue;
			}

			// Compare MD5 hashes.
			if ( md5_file( $file_path ) !== $checksum ) {
				$matches[] = $file;
			}
		}

		return $matches;
	}
}
