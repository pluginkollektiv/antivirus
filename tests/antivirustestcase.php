<?php
/**
 * Abstract test class for the Antivirus plugin.
 *
 * @package AntiVirus
 */

/**
 * Class AntiVirus_TestCase.
 */
abstract class AntiVirus_TestCase extends WP_Mock\Tools\TestCase {
	/**
	 * @var array Plugin options.
	 */
	private $options = array(
		'cronjob_enable'    => 0,
		'cronjob_alert'     => 0,
		'safe_browsing'     => 0,
		'safe_browsing_key' => '',
		'checksum_verifier' => 0,
		'notify_email'      => '',
		'white_list'        => '',
	);

	/**
	 * Set up tests.
	 *
	 * Initialize WP_Mock and add some common mocks like options override and blog info.
	 */
	public function setUp(): void {
		WP_Mock::setUp();

		WP_Mock::passthruFunction( 'wp_parse_args' );
		WP_Mock::userFunction( 'get_option' )->with( 'antivirus' )->andReturnUsing(
			function () {
				return $this->options;
			}
		);
		WP_Mock::userFunction( 'is_email' )->withAnyArgs()->andReturnUsing(
			function ( $e ) {
				return boolval( filter_var( $e, FILTER_VALIDATE_EMAIL ) );
			}
		);
		WP_Mock::userFunction( 'get_bloginfo' )->with( 'name' )->andReturn( 'AntiVirus Test Blog' );
		WP_Mock::userFunction( 'get_bloginfo' )->with( 'admin_email' )->andReturn( 'admin@example.com' );
	}

	/**
	 * Tear down tests.
	 *
	 * Tear down (verify) WP_Mock.
	 */
	public function tearDown(): void {
		WP_Mock::tearDown();
	}

	/**
	 * Update mocked options.
	 *
	 * @param array $overrides Associative array of overridden options.
	 */
	protected function update_options( $overrides ) {
		foreach ( $this->options as $k => &$v ) {
			if ( isset( $overrides[ $k ] ) ) {
				$v = $overrides[ $k ];
			}
		}
	}
}
