<?php
/**
 * Test our plugin.
 *
 * @package AntiVirus
 */

/**
 * Class AntiVirus_ChecksumVerifier_Test.
 *
 * Unit tests for the Checksum Verifier module.
 */
class AntiVirus_ChecksumVerifier_Test extends AntiVirus_TestCase {

	/**
	 * Set up test.
	 *
	 * @inheritdoc
	 */
	public function setUp(): void {
		parent::setUp();

		require_once __DIR__ . '/../inc/class-antivirus-checksumverifier.php';
	}

	/**
	 * Test SafeBrowsing check.
	 */
	public function test(): void {
		$testfile1 = __DIR__ . '/testfile1';
		$testfile2 = __DIR__ . '/testfile2';
		$testfile3 = __DIR__ . '/testfile3';

		define( 'DAY_IN_SECONDS', 86400 );
		// Emulate blog version and non-default locale (actual value does not really matter here).
		WP_Mock::userFunction( 'get_bloginfo' )
				->with( 'version' )
				->andReturn( '5.5.1' );
		WP_Mock::userFunction( 'get_locale' )
				->andReturn( 'de_DE' );

		// We capture the remote call to SafeBrowsing API and mock the response.
		$response    = new stdClass();
		$request_url = null;
		WP_Mock::userFunction( 'wp_remote_get' )
				->with( Mockery::capture( $request_url ) )
				->atLeast()
				->once()
				->andReturn( $response );
		WP_Mock::userFunction( 'get_site_transient' )
				->with( 'checksums_NS41LjFkZV9ERQ==' )
				->andReturn(
					null,
					null,
					null,
					null,
					null,
					json_decode( '{"' . $testfile1 . '":"a8b7bcbb15f9804a905c33127aca0ade","' . $testfile2 . '":"0123456789abcdef0123456789abcdef"}' )
				)
				->atLeast()
				->once();
		$transient            = null;
		$transient_expiration = null;
		WP_Mock::userFunction( 'set_site_transient' )
			->with(
				'checksums_NS41LjFkZV9ERQ==',
				Mockery::capture( $transient ),
				Mockery::capture( $transient_expiration )
			);

		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )
			->with( $response )
			->andReturn(
				500,
				200,
				200,
				200,
				200,
				404     // Should never be hit, because of the cache.
			);
		WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->with( $response )
			->andReturn(
				'{nonsense:',
				'{"checksums":{"' . $testfile1 . '":"a8b7bcbb15f9804a905c33127aca0ade","' . $testfile2 . '":"0123456789abcdef0123456789abcdef"}}',
				'{"checksums":{"' . $testfile1 . '":"a8b7bcbb15f9804a905c33127aca0ade","' . $testfile3 . '":"0123456789abcdef0123456789abcdef"}}',
				'{"checksums":{"' . $testfile1 . '":"a8b7bcbb15f9804a905c33127aca0ade","' . $testfile2 . '":"0123456789abcdef0123456789abcdef"}}'
			);
		WP_Mock::userFunction( 'validate_file' )
			->withAnyArgs()
			->andReturn(
				0,  // Only first file is valid.
				3,
				0,  // Both files are valid.
				0
			);

		$mail_recipient = null;
		$mail_subject   = null;
		$mail_body      = null;
		WP_Mock::userFunction( 'wp_mail' )
			->with( Mockery::capture( $mail_recipient ), Mockery::capture( $mail_subject ), Mockery::capture( $mail_body ) )
			->atLeast()
			->once();

		/*
		 * Case 1: Empty cache, error response.
		 */
		AntiVirus_ChecksumVerifier::verify_files();

		// Validate the request.
		self::assertEquals(
			'https://api.wordpress.org/core/checksums/1.0/?version=5.5.1&locale=de_DE',
			$request_url,
			'expected call to checksum API with version and locale'
		);
		self::assertNull( $transient, 'checksums unexpectedly cached with error response' );
		self::assertNull( $mail_recipient, 'no mail should be sent for error response' );

		/*
		 * Case 2: Empty cache, invalid JSON response.
		 */
		AntiVirus_ChecksumVerifier::verify_files();

		self::assertEquals(
			'https://api.wordpress.org/core/checksums/1.0/?version=5.5.1&locale=de_DE',
			$request_url,
			'expected call to checksum API with version and locale'
		);
		self::assertNull( $transient, 'checksums unexpectedly cached with invalid response' );
		self::assertNull( $mail_recipient, 'no mail should be sent for invalid response' );

		/*
		 * Case 3: Empty cache, retrieve valid API response. One match, one invalid file.
		 */
		AntiVirus_ChecksumVerifier::verify_files();

		self::assertIsObject( $transient, 'checksums not cached' );
		self::assertEquals( 86400, $transient_expiration, 'transient should be valid for 1 day' );
		self::assertNull( $mail_recipient, 'no mail should be sent for invalid file path' );

		/*
		 * Case 4: Empty cache, retrieve valid API response. One match, one non-existing file.
		 */
		AntiVirus_ChecksumVerifier::verify_files();

		self::assertNull( $mail_recipient, 'no mail should be sent for non-existing file' );

		/*
		 * Case 5: Empty cache, retrieve valid API response. One match, one mismatch.
		 */
		AntiVirus_ChecksumVerifier::verify_files();

		self::assertEquals( 'admin@example.com', $mail_recipient, 'Mail should have been sent to site admin' );
		self::assertEquals( '[AntiVirus Test Blog] Checksum Verifier Alert', $mail_subject, 'Unexpected mail subject' );
		self::assertStringContainsString( 'testfile2', $mail_body, 'Mail body does not contain expected filename' );

		/*
		 * Case 6: Cached checksums, so invalid response will not be used.
		 */
		$request_url    = null;
		$mail_recipient = null;

		AntiVirus_ChecksumVerifier::verify_files();

		self::assertNull( $request_url, 'no request expected with transient present' );
		self::assertNotNull( $mail_recipient, 'no mail sent for ignored file' );

		/*
		 * Case 7: Filter application ignores invalid file.
		 */
		WP_Mock::onFilter( 'antivirus_checksum_verifier_ignore_files' )
			->with(
				array(
					'wp-config-sample.php',
					'wp-includes/version.php',
					'readme.html',
					'readme-ja.html',
					'liesmich.html',
					'olvasdel.html',
					'procitajme.html',
				)
			)->reply( array( $testfile2 ) );
		$mail_recipient = null;

		AntiVirus_ChecksumVerifier::verify_files();

		self::assertNull( $mail_recipient, 'no mail should be sent for ignored file' );
	}
}
