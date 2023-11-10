<?php
/**
 * Test our plugin.
 *
 * @package AntiVirus
 */

/**
 * Class AntiVirus_Checkinternals_Test.
 *
 * Unit tests for the the {@link AntiVirus_CheckInternals} module.
 */
class AntiVirus_Checkinternals_Test extends AntiVirus_TestCase {

	/**
	 * Set up test.
	 *
	 * @inheritdoc
	 */
	public function setUp(): void {
		parent::setUp();

		require_once __DIR__ . '/../inc/class-antivirus-checkinternals.php';
	}

	/**
	 * Test theme file checks.
	 */
	public function test_theme_files(): void {
		$theme = new WP_Theme_Mock(
			'Theme 1',
			'theme1',
			array(
				'themefile1' => '/themes/theme1/themefile1',
				'themefile2' => '/themes/theme1/themefile2',
			)
		);

		$parent_theme = new WP_Theme_Mock(
			'Theme 2',
			'theme2',
			array(
				'themefile1' => '/themes/theme2/themefile1',
				'themefile3' => '/themes/theme2/themefile3',
			)
		);

		$checked_files = array();
		WP_Mock::userFunction( 'wp_get_theme' )
				->andReturn( $theme );
		WP_Mock::userFunction( 'validate_file' )
				->andReturnUsing(
					function ( $file ) use ( &$checked_files ) {
						$checked_files[] = $file;

						return 0;
					}
				);

		// Check standalone theme.
		self::assertFalse( AntiVirus_CheckInternals::_check_theme_files(), 'failed checking empty files' );
		self::assertEquals( 2, count( $checked_files ), 'unexpected number of checked files for standlone theme' );

		// Again with child theme.
		$theme->set( 'parent', $parent_theme );
		$checked_files = array();
		self::assertFalse( AntiVirus_CheckInternals::_check_theme_files(), 'failed checking empty files' );
		self::assertEquals( 4, count( $checked_files ), 'unexpected number of checked files for child theme' );

		// TODO: add some real content checks.
	}
}
