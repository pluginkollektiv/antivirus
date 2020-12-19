<?php
/**
 * Test our plugin.
 *
 * @package AntiVirus
 */

/**
 * Class AntiVirus_Test_Plugin.
 *
 * Unit tests for the plugin itself, not including module tests.
 */
class AntiVirus_Test_Plugin extends AntiVirus_TestCase {

	/**
	 * Set up test.
	 *
	 * @inheritdoc
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! defined( 'ANTIVIRUS_FILE' ) ) {
			define( 'ANTIVIRUS_FILE', 'antivirus.php' );
		}
		WP_Mock::passthruFunction( 'plugin_basename' );
	}

	/**
	 * Test plugin construction for normal visitors (no cron, no admin).
	 */
	public function test_construction_normal(): void {
		WP_Mock::userFunction( 'is_admin' )->andReturnFalse();
		WP_Mock::expectActionAdded( 'antivirus_daily_cronjob', array( AntiVirus::class, 'do_daily_cronjob' ) );
		WP_Mock::expectActionNotAdded( 'wp_ajax_get_ajax_response', array( AntiVirus::class, 'get_ajax_response' ) );
		WP_Mock::expectActionNotAdded( 'admin_menu', array( AntiVirus::class, 'add_sidebar_menu' ) );
		WP_Mock::expectActionNotAdded( 'admin_notices', array( AntiVirus::class, 'show_dashboard_notice' ) );
		WP_Mock::expectActionNotAdded( 'plugin_row_meta', array( AntiVirus::class, 'init_row_meta' ) );
		WP_Mock::expectActionNotAdded( 'plugin_action_links_antivirus.php', array( AntiVirus::class, 'init_action_links' ) );
		AntiVirus::init();
		self::assertTrue( true );
	}

	/**
	 * Test plugin construction for admin visitors.
	 */
	public function test_construction_admin(): void {
		WP_Mock::userFunction( 'is_admin' )->andReturnTrue();
		WP_Mock::expectActionAdded( 'antivirus_daily_cronjob', array( AntiVirus::class, 'do_daily_cronjob' ) );
		WP_Mock::expectActionNotAdded( 'wp_ajax_get_ajax_response', array( AntiVirus::class, 'get_ajax_response' ) );
		WP_Mock::expectActionAdded( 'admin_menu', array( AntiVirus::class, 'add_sidebar_menu' ) );
		WP_Mock::expectActionAdded( 'admin_notices', array( AntiVirus::class, 'show_dashboard_notice' ) );
		WP_Mock::expectActionAdded( 'deactivate_antivirus.php', array( AntiVirus::class, 'clear_scheduled_hook' ) );
		WP_Mock::expectActionAdded( 'plugin_row_meta', array( AntiVirus::class, 'init_row_meta' ), 10, 2 );
		WP_Mock::expectActionAdded( 'plugin_action_links_antivirus.php', array( AntiVirus::class, 'init_action_links' ) );
		AntiVirus::init();
		self::assertTrue( true );
	}

	/**
	 * Test plugin construction for AJAX calls.
	 */
	public function test_construction_ajax(): void {
		WP_Mock::userFunction( 'is_admin' )->andReturnTrue();
		define( 'DOING_AJAX', true );
		WP_Mock::expectActionAdded( 'antivirus_daily_cronjob', array( AntiVirus::class, 'do_daily_cronjob' ) );
		WP_Mock::expectActionAdded( 'wp_ajax_get_ajax_response', array( AntiVirus::class, 'get_ajax_response' ) );
		WP_Mock::expectActionNotAdded( 'admin_menu', array( AntiVirus::class, 'add_sidebar_menu' ) );
		WP_Mock::expectActionNotAdded( 'admin_notices', array( AntiVirus::class, 'show_dashboard_notice' ) );
		WP_Mock::expectActionNotAdded( 'deactivate_antivirus.php', array( AntiVirus::class, 'clear_scheduled_hook' ) );
		WP_Mock::expectActionNotAdded( 'plugin_row_meta', array( AntiVirus::class, 'init_row_meta' ) );
		WP_Mock::expectActionNotAdded( 'plugin_action_links_antivirus.php', array( AntiVirus::class, 'init_action_links' ) );
		AntiVirus::init();
		self::assertTrue( true );
	}

	/**
	 * Test daily cron execution.
	 */
	public function test_do_daily_cronjob(): void {
		$mock_ci = Mockery::mock( 'overload:AntiVirus_CheckInternals' );
		$mock_sb = Mockery::mock( 'overload:AntiVirus_SafeBrowsing' );
		$mock_cv = Mockery::mock( 'overload:AntiVirus_ChecksumVerifier' );

		$mock_ci->allows( 'check_blog_internals' );
		$mock_sb->allows( 'check_safe_browsing' );
		$mock_cv->allows( 'verify_files' );

		/*
		 * Case 1: Default configuration, all modules disabled.
		 */
		AntiVirus::do_daily_cronjob();
		$mock_ci->shouldNotHaveReceived( 'check_blog_internals' );
		$mock_sb->shouldNotHaveReceived( 'check_safe_browsing' );
		$mock_cv->shouldNotHaveReceived( 'verify_files' );

		/*
		 * Case 2: Check Internals enabled.
		 */
		$this->update_options(
			array(
				'cronjob_enable'    => 1,
				'safe_browsing'     => 0,
				'checksum_verifier' => 0,
			)
		);
		AntiVirus::do_daily_cronjob();
		$mock_ci->shouldHaveReceived( 'check_blog_internals' )->once();
		$mock_sb->shouldNotHaveReceived( 'check_safe_browsing' );
		$mock_cv->shouldNotHaveReceived( 'verify_files' );

		/*
		 * Case 3: Safe Browsing enabled.
		 */
		$this->update_options(
			array(
				'cronjob_enable'    => 0,
				'safe_browsing'     => 1,
				'checksum_verifier' => 0,
			)
		);
		AntiVirus::do_daily_cronjob();
		$mock_ci->shouldHaveReceived( 'check_blog_internals' )->once();
		$mock_sb->shouldHaveReceived( 'check_safe_browsing' )->once();
		$mock_cv->shouldNotHaveReceived( 'verify_files' );

		/*
		 * Case 4: Checksum Verifier enabled.
		 */
		$this->update_options(
			array(
				'cronjob_enable'    => 0,
				'safe_browsing'     => 0,
				'checksum_verifier' => 1,
			)
		);
		AntiVirus::do_daily_cronjob();
		$mock_ci->shouldHaveReceived( 'check_blog_internals' )->once();
		$mock_sb->shouldHaveReceived( 'check_safe_browsing' )->once();
		$mock_cv->shouldHaveReceived( 'verify_files' )->once();

		/*
		 * Case 5: All modules enabled.
		 */
		$this->update_options(
			array(
				'cronjob_enable'    => 1,
				'safe_browsing'     => 1,
				'checksum_verifier' => 1,
			)
		);
		AntiVirus::do_daily_cronjob();
		$mock_ci->shouldHaveReceived( 'check_blog_internals' )->twice();
		$mock_sb->shouldHaveReceived( 'check_safe_browsing' )->twice();
		$mock_cv->shouldHaveReceived( 'verify_files' )->twice();

		self::assertTrue( true );
	}
}
