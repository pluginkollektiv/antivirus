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

		// Test of malicious patterns are detected.
		$theme->set( 'parent', false );
		$theme->set( 'files', array( '/themes/theme1/maliciousfile' ) );

		$results = AntiVirus_CheckInternals::_check_theme_files();
		self::assertIsArray( $results, 'malicous file passed check' );
		self::assertArrayHasKey( '/themes/theme1/maliciousfile', $results, 'malicious file not in result array' );
		$results = $results['/themes/theme1/maliciousfile'];
		self::assertEquals( 3, count( $results ), 'unexpected number of matches in malicious file' );
		self::assertEquals(
			array( '$base64 = "@span@IQ==@/span@";	// "!"' ),
			$results[5],
			'unexpected match for line 6'
		);
		self::assertEquals(
			array( '$base64 = \'@span@cGx1Z2lua28vL2VrdGl2Cg==@/span@\';	// "pluginko//ektiv"' ),
			$results[6],
			'unexpected match for line 7'
		);
		self::assertEquals(
			array( '$base64 = \'MSA@span@c9+vXHm1qkYNhtk/PJA=@/span@\';	// random stuff' ),
			$results[7],
			'unexpected match for line 8'
		);
	}
}
