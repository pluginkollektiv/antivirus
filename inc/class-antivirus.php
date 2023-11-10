<?php
/**
 * AntiVirus: Main class.
 *
 * @package    AntiVirus
 */

// Quit.
defined( 'ABSPATH' ) || exit;


/**
 * AntiVirus: Main plugin class.
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
	 *
	 * @deprecated Since 1.4, use init() instead.
	 * @see AntiVirus::init()
	 */
	public static function instance() {
		self::init();
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.4
	 */
	public static function init() {
		// Don't run during autosave or XML-RPC request.
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
			return;
		}

		// Save the plugin basename.
		self::$base = plugin_basename( ANTIVIRUS_FILE );

		// Load translations. Required due to support for WP versions before 4.6.
		load_plugin_textdomain( 'antivirus' );

		// Register the daily cronjob.
		add_action( 'antivirus_daily_cronjob', array( __CLASS__, 'do_daily_cronjob' ) );

		if ( is_admin() ) {
			/* AJAX */
			if ( defined( 'DOING_AJAX' ) ) {
				add_action( 'wp_ajax_get_ajax_response', array( __CLASS__, 'get_ajax_response' ) );
			} else {
				/* Actions */
				add_action( 'admin_menu', array( __CLASS__, 'add_sidebar_menu' ) );
				add_action( 'admin_notices', array( __CLASS__, 'show_dashboard_notice' ) );
				add_action( 'deactivate_' . self::$base, array( __CLASS__, 'clear_scheduled_hook' ) );
				add_action( 'plugin_row_meta', array( __CLASS__, 'init_row_meta' ), 10, 2 );
				add_action( 'plugin_action_links_' . self::$base, array( __CLASS__, 'init_action_links' ) );
			}
		}
	}

	/**
	 * Constructor.
	 *
	 * Should not be called directly,
	 *
	 * @deprecated Since 1.4, use init() instead.
	 * @see AntiVirus::init()
	 */
	public function __construct() {
		// Nothing to construct, just run the initialization for backwards compatibility.
		self::init();
	}

	/**
	 * Adds a link to the plugin settings in the plugin list table.
	 *
	 * @param array $data Plugin action links.
	 *
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
	 *
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
		if ( self::_cron_enabled( self::_get_options() ) ) {
			self::_add_scheduled_hook();
		}

		// Add admin notice and disable the feature, if Safe Browsing is enabled without custom API key.
		$safe_browsing_key = self::_get_option( 'safe_browsing_key' );
		if ( self::_get_option( 'safe_browsing' ) && empty( $safe_browsing_key ) ) {
			self::_update_option( 'safe_browsing', 0 );
			set_transient( 'antivirus-activation-notice', true, 2592000 );
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
	 * Get a plugin options array.
	 *
	 * @return array The options array.
	 * @since 1.4 Extracted from _get_option() for use with _cron_enabled().
	 */
	private static function _get_options() {
		return wp_parse_args(
			get_option( 'antivirus' ),
			array(
				'cronjob_enable'    => 0,
				'cronjob_alert'     => 0,
				'safe_browsing'     => 0,
				'safe_browsing_key' => '',
				'checksum_verifier' => 0,
				'notify_email'      => '',
				'white_list'        => '',
			)
		);
	}

	/**
	 * Get a plugin option value.
	 *
	 * @param string $field Option name.
	 *
	 * @return string The option value.
	 */
	protected static function _get_option( $field ) {
		$options = self::_get_options();

		return empty( $options[ $field ] ) ? '' : $options[ $field ];
	}

	/**
	 * Update an option in the database.
	 *
	 * @param string     $field The option name.
	 * @param string|int $value The option value.
	 */
	protected static function _update_option( $field, $value ) {
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
	 * Is at least one cron check enabled?
	 *
	 * @param array $options Options wo perform the checks on.
	 *
	 * @return bool TRUE, if at least one check is enabled.
	 * @since 1.4
	 */
	private static function _cron_enabled( $options ) {
		return ( isset( $options['cronjob_enable'] ) && $options['cronjob_enable'] )
			|| ( isset( $options['safe_browsing'] ) && $options['safe_browsing'] )
			|| ( isset( $options['checksum_verifier'] ) && $options['checksum_verifier'] );
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
		// Check the theme and permalinks.
		if ( self::_get_option( 'cronjob_enable' ) ) {
			AntiVirus_CheckInternals::check_blog_internals();
		}

		// Check the Safe Browsing API.
		if ( self::_get_option( 'safe_browsing' ) ) {
			AntiVirus_SafeBrowsing::check_safe_browsing();
		}

		// Check the theme and permalinks.
		if ( self::_get_option( 'checksum_verifier' ) ) {
			AntiVirus_ChecksumVerifier::verify_files();
		}
	}

	/**
	 * Send a warning via email that something was detected.
	 *
	 * @param string $subject Subject of the notification email.
	 * @param string $body    Email body.
	 */
	protected static function _send_warning_notification( $subject, $body ) {
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
				esc_html__( 'https://antivirus.pluginkollektiv.org', 'antivirus' )
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
		$data = get_plugin_data( ANTIVIRUS_FILE );

		// Enqueue the JavaScript.
		wp_enqueue_script(
			'av_script',
			plugins_url( 'js/script.min.js', ANTIVIRUS_FILE ),
			array( 'jquery' ),
			$data['Version']
		);

		// Localize script data.
		wp_localize_script(
			'av_script',
			'av_settings',
			array(
				'nonce'  => wp_create_nonce( 'av_ajax_nonce' ),
				'labels' => array(
					'dismiss'  => esc_js( __( 'Dismiss', 'antivirus' ) ),
					'complete' => esc_js( __( 'Scan finished', 'antivirus' ) ),
					'file'     => esc_js( __( 'Theme File', 'antivirus' ) ),
					'status'   => esc_js( __( 'Check Status', 'antivirus' ) ),
				),
				'texts'  => array(
					'dismiss' => esc_js( __( 'Dismiss false positive virus detection', 'antivirus' ) ),
					'ok'      => esc_js( __( '✔ OK', 'antivirus' ) ),
					'pending' => esc_js( __( 'pending', 'antivirus' ) ),
					'warning' => esc_js( __( '! Warning', 'antivirus' ) ),
				),
			)
		);
	}

	/**
	 * Enqueue our stylesheet.
	 */
	public static function add_enqueue_style() {
		// Get plugin data.
		$data = get_plugin_data( ANTIVIRUS_FILE );

		// Enqueue the stylesheet.
		wp_enqueue_style(
			'av_css',
			plugins_url( 'css/style.min.css', ANTIVIRUS_FILE ),
			array(),
			$data['Version']
		);
	}

	/**
	 * Get the currently activated theme.
	 *
	 * @param WP_Theme|false $theme Theme to parse.
	 *
	 * @return array|false An array holding the theme data or false on failure.
	 *
	 * @since 1.4 Added $theme parameter.
	 */
	private static function _get_theme_data( $theme ) {
		// Break recursion if no valid (parent) theme is given.
		if ( ! $theme ) {
			return false;
		}

		// Extract data.
		$name  = $theme->get( 'Name' );
		$slug  = $theme->get_stylesheet();
		$files = array_values( $theme->get_files( 'php', -1 ) );

		// Append parent's data, if we got a child theme.
		$parent = self::_get_theme_data( $theme->parent() );

		// Return false if there are no files in current theme and no parent.
		if ( empty( $files ) && ! $parent ) {
			return false;
		}

		return array(
			'Name'           => $name,
			'Slug'           => $slug,
			'Template Files' => $files,
			'Parent'         => $parent,
		);
	}

	/**
	 * Get all the files belonging to the current theme.
	 *
	 * @return array|false Theme files or false on failure.
	 */
	protected static function _get_theme_files() {
		// Check if the theme exists.
		$theme = self::_get_theme_data( wp_get_theme() );
		if ( ! $theme ) {
			return false;
		}

		$files = $theme['Template Files'];

		// Append parent files, if available.
		$parent = $theme['Parent'];
		while ( false !== $parent ) {
			$files  = array_merge( $files, $parent['Template Files'] );
			$parent = $parent['Parent'];
		}

		// Check its files.
		if ( empty( $files ) ) {
			return false;
		}

		// Returns the files, stripping out the content dir from the paths.
		return array_unique(
			array_map(
				array( 'AntiVirus', '_strip_content_dir' ),
				$files
			)
		);
	}

	/**
	 * Strip out the content dir from a path.
	 *
	 * @param string $string Path to strip from.
	 *
	 * @return string The stripped path.
	 */
	private static function _strip_content_dir( $string ) {
		return str_replace( array( WP_CONTENT_DIR, 'wp-content' ), '', $string );
	}

	/**
	 * Get the whitelist.
	 *
	 * @return array MD5 hashes of whitelisted files.
	 */
	protected static function _get_white_list() {
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
				if ( ! empty( $_POST['_theme_file'] ) ) {
					$theme_file = filter_var( wp_unslash( $_POST['_theme_file'] ), FILTER_UNSAFE_RAW );
					$lines      = AntiVirus_CheckInternals::check_theme_file( $theme_file );
					if ( $lines ) {
						foreach ( $lines as $num => $line ) {
							foreach ( $line as $string ) {
								$values[] = $num;
								$values[] = htmlentities( $string, ENT_QUOTES, 'UTF-8' );
								$values[] = md5( $num . $string );
							}
						}
					}
				}
				break;

			case 'update_white_list':
				if ( ! empty( $_POST['_file_md5'] ) ) {
					$file_md5 = sanitize_text_field( wp_unslash( $_POST['_file_md5'] ) );
					if ( preg_match( '/^[a-f0-9]{32}$/', $file_md5 ) ) {
						self::_update_option(
							'white_list',
							implode(
								':',
								array_unique(
									array_merge(
										self::_get_white_list(),
										array( $file_md5 )
									)
								)
							)
						);

						$values = array( $file_md5 );
					}
				}
				break;

			default:
				break;
		}

		// Send response.
		if ( $values ) {
			if ( isset( $_POST['_ajax_nonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
			} else {
				$nonce = '';
			}

			wp_send_json(
				array(
					'data'  => array_values( $values ),
					'nonce' => $nonce,
				)
			);
		}

		exit();
	}

	/**
	 * Show notice on the dashboard.
	 */
	public static function show_dashboard_notice() {
		// Show admin notice to users who can manage options that Safe Browsing has been disabled because custom API key is missing.
		if ( current_user_can( 'manage_options' ) && get_transient( 'antivirus-activation-notice' ) ) {
			printf(
				'<div class="notice notice-warning is-dismissible"><p><strong>%1$s</strong></p><p>%2$s</p><p>%3$s %4$s</p></div>',
				esc_html__( 'No Safe Browsing API key provided for AntiVirus', 'antivirus' ),
				esc_html__( 'Google Safe Browsing check was disabled, because no API key has been provided.', 'antivirus' ),
				wp_kses(
					sprintf(
						/* translators: First placeholder (%1$s) starting link tag to the plugin settings page, second placeholder (%2$s) closing link tag */
						__( 'If you want to continue using this feature, please provide an API key using the %1$sAntiVirus settings page%2$s.', 'antivirus' ),
						'<a href="' . esc_attr( add_query_arg( array( 'page' => 'antivirus' ), admin_url( '/options-general.php' ) ) ) . '">',
						'</a>'
					),
					array( 'a' => array( 'href' => array() ) )
				),
				wp_kses(
					sprintf(
					/* translators: First placeholder (%1$s) starting link tag to the documentation page, second placeholder (%2$s) closing link tag */
						__( 'See official %1$sdocumentation%2$s from Google.', 'antivirus' ),
						'<a href="https://cloud.google.com/docs/authentication/api-keys" target="_blank" rel="noopener noreferrer">',
						'</a>'
					),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
					)
				)
			);
			delete_transient( 'antivirus-activation-notice' );
		}

		// Only show notice if there's an alert.
		if ( ! self::_get_option( 'cronjob_alert' ) ) {
			return;
		}

		// Display warning.
		printf(
			'<div class="error"><p><strong>%1$s:</strong> %2$s <a href="%3$s">%4$s &rarr;</a></p></div>',
			esc_html__( 'Virus suspected', 'antivirus' ),
			esc_html__( 'The daily antivirus scan of your blog suggests alarm.', 'antivirus' ),
			esc_url(
				add_query_arg(
					array(
						'page'   => 'antivirus',
						'av_tab' => 'scan',
					),
					admin_url( 'options-general.php' )
				)
			),
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
				'cronjob_enable'    => (int) ( ! empty( $_POST['av_cronjob_enable'] ) ),
				'notify_email'      => ( ! empty( $_POST['av_notify_email'] ) ) ? sanitize_email( wp_unslash( $_POST['av_notify_email'] ) ) : '',
				'safe_browsing'     => (int) ( ! empty( $_POST['av_safe_browsing'] ) ),
				'safe_browsing_key' => ( ! empty( $_POST['av_safe_browsing_key'] ) ) ? sanitize_text_field( wp_unslash( $_POST['av_safe_browsing_key'] ) ) : '',
				'checksum_verifier' => (int) ( ! empty( $_POST['av_checksum_verifier'] ) ),
			);

			// No cronjob?
			if ( ! self::_cron_enabled( $options ) ) {
				$options['notify_email'] = '';
			}

			// No Safe Browsing?
			if ( ! $options['safe_browsing'] ) {
				$options['safe_browsing_key'] = '';
			}

			// Stop cron if it was disabled.
			if ( self::_cron_enabled( $options ) && ! self::_cron_enabled( self::_get_options() ) ) {
				self::_add_scheduled_hook();
			} elseif ( ! self::_cron_enabled( $options ) && self::_cron_enabled( self::_get_options() ) ) {
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
			<?php
		}
		?>

		<div class="wrap" id="av_main">
			<h1>
				<?php esc_html_e( 'AntiVirus', 'antivirus' ); ?>
			</h1>

			<h2 class="nav-tab-wrapper">
				<?php
				$current_tab = isset( $_GET['av_tab'] ) ? sanitize_text_field( wp_unslash( $_GET['av_tab'] ) ) : 'settings';
				printf(
					'<a class="nav-tab%s" href="%s">%s</a>',
					esc_attr( 'settings' === $current_tab ? ' nav-tab-active' : '' ),
					esc_url(
						add_query_arg(
							array(
								'page'   => 'antivirus',
								'av_tab' => 'settings',
							),
							admin_url( 'options-general.php' )
						)
					),
					esc_html__( 'Settings', 'antivirus' )
				);
				printf(
					'<a class="nav-tab%s" href="%s">%s</a>',
					esc_attr( 'scan' === $current_tab ? ' nav-tab-active' : '' ),
					esc_url(
						add_query_arg(
							array(
								'page'   => 'antivirus',
								'av_tab' => 'scan',
							),
							admin_url( 'options-general.php' )
						)
					),
					esc_html__( 'Manual Scan', 'antivirus' )
				);
				?>
			</h2>

			<?php if ( 'scan' === $current_tab ) : ?>

			<p>
				<a id="av-scan-trigger" href="#" class="button button-primary">
					<?php esc_html_e( 'Scan the theme templates now', 'antivirus' ); ?>
				</a>
				<span id="av-scan-process"></span>
			</p>

			<div id="av-scan-output" class="av-scan-output"></div>

			<?php else : ?>

			<form id="av_settings" method="post" action="<?php echo esc_url( admin_url( 'options-general.php?page=antivirus' ) ); ?>">
				<?php wp_nonce_field( 'antivirus' ); ?>

				<h2><?php esc_html_e( 'Daily malware scan', 'antivirus' ); ?></h2>
				<table class="form-table">
					<tbody>
					<tr>
						<th scope="row">
							<label for="av_cronjob_enable">
								<?php esc_html_e( 'Theme templates scan', 'antivirus' ); ?>
							</label>
						</th>
						<td>
							<input type="checkbox" name="av_cronjob_enable" id="av_cronjob_enable"
								   value="1" <?php checked( self::_get_option( 'cronjob_enable' ), 1 ); ?> />
							<label for="av_cronjob_enable">
								<?php esc_html_e( 'Enable theme templates scan for malware', 'antivirus' ); ?>
							</label>
							<p class="description">
								<?php
								$timestamp = wp_next_scheduled( 'antivirus_daily_cronjob' );
								if ( $timestamp ) {
									printf(
										'%s: %s',
										esc_html__( 'Next Run', 'antivirus' ),
										esc_html( date_i18n( 'd.m.Y H:i:s', $timestamp + get_option( 'gmt_offset' ) * 3600 ) )
									);
								}
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Google Safe Browsing', 'antivirus' ); ?>
						</th>
						<td>
							<fieldset>
								<input type="checkbox" name="av_safe_browsing" id="av_safe_browsing"
									   value="1" <?php checked( self::_get_option( 'safe_browsing' ), 1 ); ?> />
								<label for="av_safe_browsing">
									<?php esc_html_e( 'Enable malware detection by Google Safe Browsing', 'antivirus' ); ?>
								</label>
								<p class="description">
									<?php
									esc_html_e( 'Diagnosis and notification in suspicion case.', 'antivirus' );
									echo ' ';
									echo wp_kses(
										sprintf(
											/* translators: First placeholder (%1$s) starting link tag to transparency report, second placeholder (%2$s) closing link tag */
											__( 'For more details read %1$sthe transparency report%2$s.', 'antivirus' ),
											sprintf(
												'<a href="https://transparencyreport.google.com/safe-browsing/search?url=%s&hl=%s" target="_blank" rel="noopener noreferrer">',
												urlencode( get_bloginfo( 'url' ) ),
												substr( get_locale(), 0, 2 )
											),
											'</a>'
										),
										array(
											'a' => array(
												'href'   => array(),
												'target' => array(),
												'rel'    => array(),
											),
										)
									);
									?>
								</p>
								<br>
								<label for="av_safe_browsing_key">
									<?php esc_html_e( 'Safe Browsing API key', 'antivirus' ); ?>
								</label>
								<br/>
								<input type="text" name="av_safe_browsing_key" id="av_safe_browsing_key" size="45" required
									   value="<?php echo esc_attr( self::_get_option( 'safe_browsing_key' ) ); ?>" />
								<p class="description">
									<?php
									printf(
										'%1$s %2$s<br>%3$s',
										esc_html__( 'Provide a custom key for the Google Safe Browsing API (v4).', 'antivirus' ),
										wp_kses(
											__( 'A key is <em>required</em> in order to use this check.', 'antivirus' ),
											array( 'em' => array() )
										),
										wp_kses(
											sprintf(
												/* translators: First placeholder (%1$s) starting link tag to the documentation page, second placeholder (%2$s) closing link tag */
												__( 'See official %1$sdocumentation%2$s from Google.', 'antivirus' ),
												'<a href="https://cloud.google.com/docs/authentication/api-keys">',
												'</a>'
											),
											array( 'a' => array( 'href' => array() ) )
										)
									);
									?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="av_checksum_verifier">
								<?php esc_html_e( 'Checksum verification', 'antivirus' ); ?>
							</label>
						</th>
						<td>
							<input type="checkbox" name="av_checksum_verifier" id="av_checksum_verifier"
								   value="1" <?php checked( self::_get_option( 'checksum_verifier' ), 1 ); ?> />
							<label for="av_checksum_verifier">
								<?php esc_html_e( 'Enable checksum verification of WordPress core files', 'antivirus' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Matches checksums of all WordPress core files against the values provided by the official API.', 'antivirus' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="av_notify_email"><?php esc_html_e( 'Email address for notifications', 'antivirus' ); ?></label>
						</th>
						<td>
							<input type="text" name="av_notify_email" id="av_notify_email"
								   value="<?php echo esc_attr( self::_get_option( 'notify_email' ) ); ?>"
								   class="regular-text"
								   placeholder="<?php esc_attr_e( 'Email address for notifications', 'antivirus' ); ?>" />
							<p class="description">
								<?php esc_html_e( 'If the field is empty, the blog admin will be notified.', 'antivirus' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'antivirus' ); ?>"/>
						</th>
						<td>
							<?php
							printf(
								'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
								'https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TD4AMD2D8EMZW',
								esc_html__( 'Donate', 'antivirus' )
							);
							?>
							&bull;
							<?php
							printf(
								'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
								'https://antivirus.pluginkollektiv.org/documentation/',
								esc_html__( 'Documentation', 'antivirus' )
							);
							?>
							&bull;
							<?php
							printf(
								'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
								'https://wordpress.org/support/plugin/antivirus',
								esc_html__( 'Support', 'antivirus' )
							);
							?>
						</td>
					</tr>
					</tbody>
				</table>
			</form>

			<?php endif; ?>
		</div>
		<?php
	}
}
