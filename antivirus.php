<?php
/**
 * Plugin Name: AntiVirus
 * Description: Security plugin to protect your blog or website against exploits and spam injections.
 * Author:      pluginkollektiv
 * Author URI:  https://pluginkollektiv.org
 * Plugin URI:  https://wordpress.org/plugins/antivirus/
 * Text Domain: antivirus
 * Domain Path: /lang
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Version:     1.3.9
 *
 * @package AntiVirus
 */

/*
Copyright (C)  2009-2015 Sergej Müller

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

// Make sure we don't expose any info if called directly.
if ( ! class_exists( 'WP' ) ) {
	die();
}

/**
 * Main plugin class.
 */
class AntiVirus {
	/**
	 * The basename of a plugin.
	 *
	 * @var string
	 */
	private static $base;

	/**
	 * Pseudo constructor.
	 */
	public static function instance() {
		new self();
	}

	/**
	 * Constructor.
	 *
	 * Should not be called directly,
	 *
	 * @see AntiVirus::instance()
	 */
	public function __construct() {
		// Don't run during autosave or XML-RPC request.
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
			return;
		}

		// Save the plugin basename.
		self::$base = plugin_basename( __FILE__ );

		// Run the daily cronjob.
		if ( defined( 'DOING_CRON' ) ) {
			add_action( 'antivirus_daily_cronjob', array( __CLASS__, 'do_daily_cronjob' ) );
		}

		if ( is_admin() ) {
			/* AJAX */
			if ( defined( 'DOING_AJAX' ) ) {
				add_action( 'wp_ajax_get_ajax_response', array( __CLASS__, 'get_ajax_response' ) );
			} else {
				/* Actions */
				add_action( 'init', array( __CLASS__, 'load_plugin_lang' ) );
				add_action( 'admin_menu', array( __CLASS__, 'add_sidebar_menu' ) );
				add_action( 'admin_notices', array( __CLASS__, 'show_dashboard_notice' ) );
				add_action( 'deactivate_' . self::$base, array( __CLASS__, 'clear_scheduled_hook' ) );
				add_action( 'plugin_row_meta', array( __CLASS__, 'init_row_meta' ), 10, 2 );
				add_action( 'plugin_action_links_' . self::$base, array( __CLASS__, 'init_action_links' ) );
			}
		}
	}

	/**
	 * Load plugin translations.
	 */
	public static function load_plugin_lang() {
		load_plugin_textdomain( 'antivirus', false, basename( dirname( plugin_dir_path( __FILE__ ) ) ) . '/lang' );
	}

	/**
	 * Adds a link to the plugin settings in the plugin list table.
	 *
	 * @param array $data Plugin action links.
	 * @return array The modified action links array.
	 */
	public static function init_action_links( $data ) {
		// Only add link if user has permissions to view them.
		if ( ! current_user_can( 'manage_options' ) ) {
			return $data;
		}

		return array_merge(
			$data,
			array(
				sprintf(
					'<a href="%s">%s</a>',
					add_query_arg(
						array(
							'page' => 'antivirus',
						),
						admin_url( 'options-general.php' )
					),
					__( 'Settings', 'antivirus' )
				),
			)
		);
	}

	/**
	 * Adds a donation link to the second row in the plugin list table.
	 *
	 * @param array  $data Plugin links array.
	 * @param string $page The current row identifier.
	 * @return array The modified links array.
	 */
	public static function init_row_meta( $data, $page ) {
		if ( $page !== self::$base ) {
			return $data;
		}

		return array_merge(
			$data,
			array(
				'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TD4AMD2D8EMZW" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Donate', 'antivirus' ) . '</a>',
				'<a href="https://wordpress.org/support/plugin/antivirus" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support', 'antivirus' ) . '</a>',
			)
		);
	}

	/**
	 * Plugin activation hook.
	 */
	public static function activation() {
		// Add default option.
		add_option(
			'antivirus',
			array(),
			'',
			'no'
		);

		// Add cron schedule.
		if ( self::_get_option( 'cronjob_enable' ) ) {
			self::_add_scheduled_hook();
		}
	}

	/**
	 * Plugin deactivation hook.
	 */
	public static function deactivation() {
		self::clear_scheduled_hook();
	}

	/**
	 * Plugin uninstall hook.
	 */
	public static function uninstall() {
		delete_option( 'antivirus' );
	}

	/**
	 * Get a plugin option value.
	 *
	 * @param string $field Option name.
	 * @return string The option value.
	 */
	private static function _get_option( $field ) {
		$options = wp_parse_args(
			get_option( 'antivirus' ),
			array(
				'cronjob_enable' => 0,
				'cronjob_alert'  => 0,
				'safe_browsing'  => 0,
				'notify_email'   => '',
				'white_list'     => '',
			)
		);

		return ( empty( $options[ $field ] ) ? '' : $options[ $field ] );
	}

	/**
	 * Update an option in the database.
	 *
	 * @param string     $field The option name.
	 * @param string|int $value The option value.
	 */
	private static function _update_option( $field, $value ) {
		self::_update_options(
			array(
				$field => $value,
			)
		);
	}

	/**
	 * Update multiple options in the database.
	 *
	 * @param array $data An associative array of option fields and values.
	 */
	private static function _update_options( $data ) {
		update_option(
			'antivirus',
			array_merge(
				(array) get_option( 'antivirus' ),
				$data
			)
		);
	}

	/**
	 * Initialize the cronjob.
	 *
	 * Schedules the AntiVirus cronjob to run daily.
	 */
	private static function _add_scheduled_hook() {
		if ( ! wp_next_scheduled( 'antivirus_daily_cronjob' ) ) {
			wp_schedule_event(
				time(),
				'daily',
				'antivirus_daily_cronjob'
			);
		}
	}

	/**
	 * Cancel the daily cronjob.
	 */
	public static function clear_scheduled_hook() {
		if ( wp_next_scheduled( 'antivirus_daily_cronjob' ) ) {
			wp_clear_scheduled_hook( 'antivirus_daily_cronjob' );
		}
	}

	/**
	 * Cronjob callback.
	 */
	public static function do_daily_cronjob() {
		// Check if cronjob is enabled in the plugin.
		if ( ! self::_get_option( 'cronjob_enable' ) ) {
			return;
		}

		// Load plugin textdomain.
		self::load_plugin_lang();

		// Check the Safe Browsing API.
		self::_check_safe_browsing();

		// Check the theme and permalinks.
		self::_check_blog_internals();
	}

	/**
	 * Pings the Safe Browsing API to see if the website is infected.
	 */
	private static function _check_safe_browsing() {
		// Check if option is enabled in the plugin.
		if ( ! self::_get_option( 'safe_browsing' ) ) {
			return;
		}

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
							'clientVersion' => '1.3.10'
						),
						'threatInfo' => array(
							'threatTypes'      => array(
								'THREAT_TYPE_UNSPECIFIED',
								'MALWARE',
								'SOCIAL_ENGINEERING',
								'UNWANTED_SOFTWARE',
								'POTENTIALLY_HARMFUL_APPLICATION'
							),
							'platformTypes'    => array( 'ANY_PLATFORM' ),
							'threatEntryTypes' => array( 'URL' ),
							'threatEntries'    => array(
								array( 'url' => urlencode( get_bloginfo( 'url' ) ) ),
							)
						)
					)
				)
			)
		);

		// API error?
		if ( is_wp_error( $response ) ) {
			return;
		}

		// Get the JSON response of the API request.
		$response_json = json_decode(wp_remote_retrieve_body( $response ), true);

		// All clear, nothing bad detected.
		if ( wp_remote_retrieve_response_code( $response ) === 200 && empty( $response_json ) ) {
			return;
		}

		// Send notification.
		self::_send_warning_notification(
			esc_html__( 'Safe Browsing Alert', 'antivirus' ),
			sprintf(
				"%s\r\nhttps://transparencyreport.google.com/safe-browsing/search?url=%s&hl=%s",
				esc_html__( 'Please check the Google Safe Browsing diagnostic page:', 'antivirus' ),
				urlencode( get_bloginfo( 'url' ) ),
				substr( get_locale(), 0, 2 )
			)
		);
	}

	/**
	 * Check blog internals like theme files and the permalink structure.
	 */
	private static function _check_blog_internals() {
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
	 * Send a warning via email that something was detected.
	 *
	 * @param string $subject Subject of the notification email.
	 * @param string $body Email body.
	 */
	private static function _send_warning_notification( $subject, $body ) {
		// Get recipient email address.
		$email = self::_get_option( 'notify_email' );

		// Get admin email address if nothing is stored.
		if ( ! is_email( $email ) ) {
			$email = get_bloginfo( 'admin_email' );
		}

		// Send email.
		wp_mail(
			$email,
			sprintf(
				'[%s] %s',
				get_bloginfo( 'name' ),
				$subject
			),
			sprintf(
				"%s\r\n\r\n\r\n%s\r\n%s\r\n",
				$body,
				esc_html__( 'Notify message by AntiVirus for WordPress', 'antivirus' ),
				esc_html__( 'http://wpantivirus.com', 'antivirus' )
			)
		);
	}

	/**
	 * Add sub menu page to the options main menu.
	 */
	public static function add_sidebar_menu() {
		$page = add_options_page(
			__( 'AntiVirus', 'antivirus' ),
			__( 'AntiVirus', 'antivirus' ),
			'manage_options',
			'antivirus',
			array(
				__CLASS__,
				'show_admin_menu',
			)
		);

		add_action( 'admin_print_styles-' . $page, array( __CLASS__, 'add_enqueue_style' ) );
		add_action( 'admin_print_scripts-' . $page, array( __CLASS__, 'add_enqueue_script' ) );
	}

	/**
	 * Enqueue our JavaScript.
	 */
	public static function add_enqueue_script() {
		// Get plugin data.
		$data = get_plugin_data( __FILE__ );

		// Enqueue the JavaScript.
		wp_enqueue_script(
			'av_script',
			plugins_url( 'js/script.min.js', __FILE__ ),
			array( 'jquery' ),
			$data['Version']
		);

		// Localize script data.
		wp_localize_script(
			'av_script',
			'av_settings',
			array(
				'nonce' => wp_create_nonce( 'av_ajax_nonce' ),
				'theme' => esc_js( urlencode( self::_get_theme_name() ) ),
				'msg_1' => esc_js( __( 'There is no virus', 'antivirus' ) ),
				'msg_2' => esc_js( __( 'View line', 'antivirus' ) ),
				'msg_3' => esc_js( __( 'Scan finished', 'antivirus' ) ),
			)
		);
	}

	/**
	 * Enqueue our stylesheet.
	 */
	public static function add_enqueue_style() {
		// Get plugin data.
		$data = get_plugin_data( __FILE__ );

		// Enqueue the stylesheet.
		wp_enqueue_style(
			'av_css',
			plugins_url( 'css/style.min.css', __FILE__ ),
			array(),
			$data['Version']
		);
	}

	/**
	 * Get the currently activated theme.
	 *
	 * @return array|false An array holding the theme data or false on failure.
	 */
	private static function _get_current_theme() {
		$theme = wp_get_theme();
		$name  = $theme->get( 'Name' );
		$slug  = $theme->get_stylesheet();
		$files = $theme->get_files( 'php', 1 );

		// Check if empty.
		if ( empty( $name ) || empty( $files ) ) {
			return false;
		}

		return array(
			'Name'           => $name,
			'Slug'           => $slug,
			'Template Files' => $files,
		);
	}

	/**
	 * Get all the files belonging to the current theme.
	 *
	 * @return array|false Theme files or false on failure.
	 */
	private static function _get_theme_files() {
		// Check if the theme exists.
		if ( ! $theme = self::_get_current_theme() ) {
			return false;
		}

		// Check its files.
		if ( empty( $theme['Template Files'] ) ) {
			return false;
		}

		// Returns the files, stripping out the content dir from the paths.
		return array_unique(
			array_map(
				array( 'AntiVirus', '_strip_content_dir' ),
				$theme['Template Files']
			)
		);
	}

	/**
	 * Strip out the content dir from a path.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	private static function _strip_content_dir( $string ) {
		return str_replace( array( WP_CONTENT_DIR, "wp-content" ), "", $string );
	}

	/**
	 * Get the name of the currently activated theme.
	 *
	 * @return string|false The theme name or false on failure.
	 */
	private static function _get_theme_name() {
		if ( $theme = self::_get_current_theme() ) {
			if ( ! empty( $theme['Slug'] ) ) {
				return $theme['Slug'];
			}
			if ( ! empty( $theme['Name'] ) ) {
				return $theme['Name'];
			}
		}

		return false;
	}

	/**
	 * Get the whitelist.
	 *
	 * @return array MD5 hashes of whitelisted files.
	 */
	private static function _get_white_list() {
		return explode(
			':',
			self::_get_option( 'white_list' )
		);
	}

	/**
	 * Ajax response handler.
	 */
	public static function get_ajax_response() {
		// Check referer.
		check_ajax_referer( 'av_ajax_nonce' );

		// Check if there really is some data.
		if ( empty( $_POST['_action_request'] ) ) {
			exit();
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$values = array();

		// Get value based on request.
		switch ( $_POST['_action_request'] ) {
			case 'get_theme_files':
				self::_update_option(
					'cronjob_alert',
					0
				);

				$values = self::_get_theme_files();
				break;

			case 'check_theme_file':
				if ( ! empty( $_POST['_theme_file'] ) && $lines = self::_check_theme_file( $_POST['_theme_file'] ) ) {
					foreach ( $lines as $num => $line ) {
						foreach ( $line as $string ) {
							$values[] = $num;
							$values[] = htmlentities( $string, ENT_QUOTES );
							$values[] = md5( $num . $string );
						}
					}
				}
				break;

			case 'update_white_list':
				if ( ! empty( $_POST['_file_md5'] ) && preg_match( '/^[a-f0-9]{32}$/', $_POST['_file_md5'] ) ) {
					self::_update_option(
						'white_list',
						implode(
							':',
							array_unique(
								array_merge(
									self::_get_white_list(),
									array( $_POST['_file_md5'] )
								)
							)
						)
					);

					$values = array( $_POST['_file_md5'] );
				}
				break;

			default:
				break;
		}

		// Send response.
		if ( $values ) {
			wp_send_json(
				array(
					'data'  => array_values( $values ),
					'nonce' => $_POST['_ajax_nonce'],
				)
			);
		}

		exit();
	}

	/**
	 * Get file contents
	 *
	 * @param string $file File path.
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
		$tag = preg_quote( $tag );

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
	 * @return array|bool An array of matched lines or false on failure.
	 */
	private static function _check_file_line( $line = '', $num ) {
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

		// Search for base64 encoded strings.
		preg_match_all(
			'/[\'\"\$\\ \/]*?([a-zA-Z0-9]{' . strlen( base64_encode( 'sergej + swetlana = love.' ) ) . ',})/', /* get length of my life ;) */
			$line,
			$matches
		);

		// Save matches.
		if ( $matches[1] ) {
			$results = array_merge( $results, $matches[1] );
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

		// Look for `get_option` calls.
		preg_match(
			'/get_option\s*\(\s*[\'"](.*?)[\'"]\s*\)/',
			$line,
			$matches
		);

		// Check option.
		if ( $matches && $matches[1] && self::_check_file_line( get_option( $matches[1] ), $num ) ) {
			array_push( $results, 'get_option' );
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
	 * Check the files of the currently activated theme.
	 *
	 * @return array|false Results array or false on failure.
	 */
	private static function _check_theme_files() {
		// Check if there are any files.
		if ( ! $files = self::_get_theme_files() ) {
			return false;
		}

		$results = array();

		// Loop through files.
		foreach ( $files as $file ) {
			if ( $result = self::_check_theme_file( $file ) ) {
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
	 * @return array|false Results array or false on failure.
	 */
	private static function _check_theme_file( $file ) {
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
			if ( $result = self::_check_file_line( $line, $num ) ) {
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
	 * Show notice on the dashboard.
	 */
	public static function show_dashboard_notice() {
		// Only show notice if there's an alert.
		if ( ! self::_get_option( 'cronjob_alert' ) ) {
			return;
		}

		// Display warning.
		echo sprintf(
			'<div class="error"><p><strong>%1$s:</strong> %2$s <a href="%3$s">%4$s &rarr;</a></p></div>',
			esc_html__( 'Virus suspected', 'antivirus' ),
			esc_html__( 'The daily antivirus scan of your blog suggests alarm.', 'antivirus' ),
			esc_url( add_query_arg(
				array(
					'page' => 'antivirus',
				),
				admin_url( 'options-general.php' )
			) ),
			esc_html__( 'Manual malware scan', 'antivirus' )
		);
	}

	/**
	 * Print the settings page.
	 */
	public static function show_admin_menu() {
		// Save updates.
		if ( ! empty( $_POST ) ) {
			// Check the referer.
			check_admin_referer( 'antivirus' );

			// Save values.
			$options = array(
				'cronjob_enable' => (int) ( ! empty( $_POST['av_cronjob_enable'] ) ),
				'notify_email'   => sanitize_email( @$_POST['av_notify_email'] ),
				'safe_browsing'  => (int) ( ! empty( $_POST['av_safe_browsing'] ) ),
			);

			// No cronjob?
			if ( empty( $options['cronjob_enable'] ) ) {
				$options['notify_email']  = '';
				$options['safe_browsing'] = 0;
			}

			// Stop cron if it was disabled.
			if ( $options['cronjob_enable'] && ! self::_get_option( 'cronjob_enable' ) ) {
				self::_add_scheduled_hook();
			} else if ( ! $options['cronjob_enable'] && self::_get_option( 'cronjob_enable' ) ) {
				self::clear_scheduled_hook();
			}

			// Save options.
			self::_update_options( $options ); ?>

			<div id="message" class="notice notice-success">
				<p>
					<strong>
						<?php esc_html_e( 'Settings saved.', 'antivirus' ); ?>
					</strong>
				</p>
			</div>
		<?php } ?>

		<div class="wrap" id="av_main">
			<h1>
				<?php esc_html_e( 'AntiVirus', 'antivirus' ); ?>
			</h1>

			<table class="form-table">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Manual malware scan', 'antivirus' ); ?>
					</th>
					<td>
						<div class="inside" id="av_manual_scan">
							<p>
								<a href="#" class="button button-primary">
									<?php esc_html_e( 'Scan the theme templates now', 'antivirus' ); ?>
								</a>
								<span class="alert"></span>
							</p>

							<div class="output"></div>
						</div>
					</td>
				</tr>
			</table>


			<form method="post" action="<?php echo esc_url( admin_url( 'options-general.php?page=antivirus' ) ); ?>">
				<?php wp_nonce_field( 'antivirus' ) ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Daily malware scan', 'antivirus' ); ?>
						</th>
						<td>
							<fieldset>
								<label for="av_cronjob_enable">
									<input type="checkbox" name="av_cronjob_enable" id="av_cronjob_enable"
										   value="1" <?php checked( self::_get_option( 'cronjob_enable' ), 1 ) ?> />
									<?php esc_html_e( 'Check the theme templates for malware', 'antivirus' ); ?>
								</label>

								<p class="description">
									<?php
									if ( $timestamp = wp_next_scheduled( 'antivirus_daily_cronjob' ) ) {
										echo sprintf(
											'%s: %s',
											esc_html__( 'Next Run', 'antivirus' ),
											date_i18n( 'd.m.Y H:i:s', $timestamp + get_option( 'gmt_offset' ) * 3600 )
										);
									}
									?>
								</p>

								<br/>

								<label for="av_safe_browsing">
									<input type="checkbox" name="av_safe_browsing" id="av_safe_browsing"
										   value="1" <?php checked( self::_get_option( 'safe_browsing' ), 1 ) ?> />
									<?php esc_html_e( 'Malware detection by Google Safe Browsing', 'antivirus' ); ?>
								</label>

								<p class="description">
									<?php esc_html_e( 'Diagnosis and notification in suspicion case', 'antivirus' ); ?>
								</p>

								<br/>

								<label for="av_notify_email"><?php esc_html_e( 'Email address for notifications', 'antivirus' ); ?></label>
								<input type="text" name="av_notify_email" id="av_notify_email"
									   value="<?php esc_attr_e( self::_get_option( 'notify_email' ) ); ?>"
									   class="regular-text"
									   placeholder="<?php esc_attr_e( 'Email address for notifications', 'antivirus' ); ?>" />


								<p class="description">
									<?php esc_html_e( 'If the field is empty, the blog admin will be notified', 'antivirus' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'antivirus' ) ?>"/>
						</th>
						<td>
							<?php
							printf(
								'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
								'https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TD4AMD2D8EMZW',
								esc_html__( 'Donate', 'antivirus' )
							);

							printf(
								'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
								esc_attr__( 'https://wordpress.org/plugins/antivirus/faq/', 'antivirus' ),
								esc_html__( 'FAQ', 'antivirus' )
							);

							printf(
								'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
								'https://github.com/pluginkollektiv/antivirus/wiki',
								esc_html__( 'Manual', 'antivirus' )
							);

							printf(
								'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
								'https://wordpress.org/support/plugin/antivirus',
								esc_html__( 'Support', 'antivirus' )

							);
							?>
						</td>
					</tr>
				</table>
			</form>
		</div>
	<?php }
}

// Initialize the plugin.
add_action( 'plugins_loaded', array( 'AntiVirus', 'instance' ), 99 );

/* Hooks */
register_activation_hook( __FILE__, array( 'AntiVirus', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'AntiVirus', 'deactivation' ) );
register_uninstall_hook( __FILE__, array( 'AntiVirus', 'uninstall' ) );
