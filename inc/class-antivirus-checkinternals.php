<?php
/**
 * AntiVirus: Check Blog Internals module.
 *
 * @package    AntiVirus
 * @subpackage CheckInternals
 */

// Quit.
defined( 'ABSPATH' ) || exit;


/**
 * AntiVirus_CheckInternals
 *
 * @since 1.4 Ported from "Checksum Verifier" plugin.
 */
class AntiVirus_CheckInternals extends AntiVirus {

	/**
	 * Check blog internals like theme files and the permalink structure.
	 */
	public static function check_blog_internals() {
		// Execute checks.
		if ( ! self::_check_theme_files() && ! self::_check_permalink_structure() ) {
			return;
		}

		// Send notification.
		self::_send_warning_notification(
			esc_html__( 'Virus suspected', 'antivirus' ),
			sprintf(
				"%s\r\n%s",
				esc_html__( 'The daily antivirus scan of your blog suggests alarm.', 'antivirus' ),
				get_bloginfo( 'url' )
			)
		);

		// Store alert in database.
		self::_update_option(
			'cronjob_alert',
			1
		);
	}

	/**
	 * Check the files of the currently activated theme.
	 *
	 * @return array|false Results array or false on failure.
	 */
	public static function _check_theme_files() {
		// Check if there are any files.
		$files = self::_get_theme_files();
		if ( ! $files ) {
			return false;
		}

		$results = array();

		// Loop through files.
		foreach ( $files as $file ) {
			$result = self::check_theme_file( $file );
			if ( $result ) {
				$results[ $file ] = $result;
			}
		}

		// Return results if found.
		if ( ! empty( $results ) ) {
			return $results;
		}

		return false;
	}

	/**
	 * Check a single file.
	 *
	 * @param string $file File path.
	 *
	 * @return array|false Results array or false on failure.
	 */
	public static function check_theme_file( $file ) {
		// Simple file path check.
		if ( filter_var( $file, FILTER_SANITIZE_URL ) !== $file ) {
			return false;
		}

		// Sanitize file string.
		if ( validate_file( $file ) !== 0 ) {
			return false;
		}

		// No file?
		if ( ! $file ) {
			return false;
		}

		// Get file content.
		$content = self::_get_file_content( $file );

		if ( ! $content ) {
			return false;
		}

		$results = array();

		// Loop through lines.
		foreach ( $content as $num => $line ) {
			$result = self::_check_file_line( $line, $num );
			if ( $result ) {
				$results[ $num ] = $result;
			}
		}

		// Return results if found.
		if ( ! empty( $results ) ) {
			return $results;
		}

		return false;
	}

	/**
	 * Check the permalink structure.
	 *
	 * @return array|false Results array or false on failure.
	 */
	private static function _check_permalink_structure() {
		$structure = get_option( 'permalink_structure' );

		if ( ! $structure ) {
			return false;
		}

		// Regex check.
		preg_match_all(
			self::_php_match_pattern(),
			$structure,
			$matches
		);

		// Save matches.
		if ( $matches[1] ) {
			return $matches[1];
		}

		return false;
	}

	/**
	 * Get the regular expression for all disallowed words/functions.
	 *
	 * @return string Regular expression.
	 */
	private static function _php_match_pattern() {
		return '/\b(assert|file_get_contents|curl_exec|popen|proc_open|unserialize|eval|base64_encode|base64_decode|create_function|exec|shell_exec|system|passthru|ob_get_contents|file|curl_init|readfile|fopen|fsockopen|pfsockopen|fclose|fread|file_put_contents)\b\s*?\(/';
	}

	/**
	 * Check a specific line number.
	 *
	 * @param string $line The line to check.
	 * @param int    $num  Line number.
	 *
	 * @return array|bool An array of matched lines or false on failure.
	 */
	private static function _check_file_line( $line, $num ) {
		// Trim value.
		$line = trim( (string) $line );

		// Make sure the values aren't empty.
		if ( ! $line || ! isset( $num ) ) {
			return false;
		}

		$results = array();
		$output  = array();

		// Check if the regex matches.
		preg_match_all(
			self::_php_match_pattern(),
			$line,
			$matches
		);

		// Save matches.
		if ( $matches[1] ) {
			$results = $matches[1];
		}

		// Look for frames.
		preg_match_all(
			'/<\s*?(i?frame)/',
			$line,
			$matches
		);

		// Save matches.
		if ( $matches[1] ) {
			$results = array_merge( $results, $matches[1] );
		}

		// Look for the MailPoet vulnerability.
		preg_match_all(
			'/explode\s?\(chr\s?\(\s?\(\d{3}\s?-\s?\d{3}\s?\)\s?\)\s?,/',
			$line,
			$matches
		);

		// Save matches.
		if ( $matches[0] ) {
			$results = array_merge( $results, $matches[0] );
		}

		if ( $results ) {
			// Remove duplicates.
			$results = array_unique( $results );

			// Get whitelist.
			$md5 = self::_get_white_list();

			// Loop through results.
			foreach ( $results as $tag ) {
				$string = str_replace(
					$tag,
					'@span@' . $tag . '@/span@',
					self::_get_dotted_line( $line, $tag )
				);

				// Add line to output if it's not on the whitelist.
				if ( ! in_array( md5( $num . $string ), $md5 ) ) {
					$output[] = $string;
				}
			}

			return $output;
		}

		return false;
	}

	/**
	 * Get file contents
	 *
	 * @param string $file File path.
	 *
	 * @return array An array containing all the lines of the file.
	 */
	private static function _get_file_content( $file ) {
		return file( WP_CONTENT_DIR . $file );
	}

	/**
	 * Shorten a string, append ellipsis.
	 *
	 * @param string $line The line.
	 * @param string $tag  The tag we're looking for.
	 * @param int    $max  Maximum number of chars on each side.
	 *
	 * @return string|false The shortened string or false on failure.
	 */
	private static function _get_dotted_line( $line, $tag, $max = 100 ) {
		// No values?
		if ( ! $line || ! $tag ) {
			return false;
		}

		// Return tag if it's higher than the maximum.
		if ( strlen( $tag ) > $max ) {
			return $tag;
		}

		// Get difference between the tag and the maximum.
		$left = round( ( $max - strlen( $tag ) ) / 2 );

		// Quote regular expression characters.
		$tag = preg_quote( $tag, '/' );

		// Shorten string on the right side.
		$output = preg_replace(
			'/(' . $tag . ')(.{' . $left . '}).{0,}$/',
			'$1$2 ...',
			$line
		);

		// Shorten string on the left side.
		$output = preg_replace(
			'/^.{0,}(.{' . $left . ',})(' . $tag . ')/',
			'... $1$2',
			$output
		);

		return $output;
	}
}
