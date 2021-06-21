<?php
/**
 * Test our plugin.
 *
 * @package AntiVirus
 */

/**
 * Class AntiVirus_Test_Safebrowsing.
 *
 * Unit tests for the Safe Browsing module.
 */
class AntiVirus_Safebrowsing_Test extends AntiVirus_TestCase {

	/**
	 * Set up test.
	 *
	 * @inheritdoc
	 */
	public function setUp(): void {
		parent::setUp();

		require_once __DIR__ . '/../inc/class-antivirus-safebrowsing.php';
	}

	/**
	 * Test SafeBrowsing check.
	 */
	public function test(): void {
		// Emulate blog URL and non-default locale.
		WP_Mock::userFunction( 'get_bloginfo' )
				->with( 'url' )
				->andReturn( 'https://antivirus.pluginkollektiv.org/test/' );
		WP_Mock::userFunction( 'get_locale' )
				->andReturn( 'de_DE' );

		// We capture the remote call to SafeBrowsing API and mock the response.
		$response     = new stdClass();
		$request_url  = null;
		$request_data = null;
		WP_Mock::userFunction( 'wp_remote_post' )
				->with( Mockery::capture( $request_url ), Mockery::capture( $request_data ) )
				->atLeast()
				->once()
				->andReturn( $response );
		WP_Mock::userFunction( 'is_wp_error' )
				->with( $response )
				->andReturnFalse();

		/*
		 * Case 1: Everything fine, receiving code 200 with empty JSON body on first call and threat response on second.
		 */
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )
				->with( $response )
				->andReturn( 200, 200, 200, 403, 400 );
		WP_Mock::userFunction( 'wp_remote_retrieve_body' )
				->with( $response )
				->andReturn(
					'{}',
					'{"threatType":"MALWARE","platformType":"WINDOWS","threatEntryType": "URL","threat":{"url":"https://antivirus.pluginkollektiv.org/test/"},"threatEntryMetadata":{"entries":[{"key":"malware_threat_type","value":"landing"}]},"cacheDuration":"300.000s"}',
					'{"threatType":"MALWARE","platformType":"WINDOWS","threatEntryType": "URL","threat":{"url":"https://antivirus.pluginkollektiv.org/test/"},"threatEntryMetadata":{"entries":[{"key":"malware_threat_type","value":"landing"}]},"cacheDuration":"300.000s"}',
					'{"error":{"message":"Quota exceeded"}}',
					'{"error":{"message":"Invalid API key"}}'
				);

		// Specify API key.
		$this->update_options( array( 'safe_browsing_key' => 'custom-api-key' ) );

		AntiVirus_SafeBrowsing::check_safe_browsing();

		// Validate the request.
		self::assertEquals(
			'https://safebrowsing.googleapis.com/v4/threatMatches:find?key=custom-api-key',
			$request_url,
			'expected call to Safe Browsing API with custom key'
		);
		self::assertIsArray( $request_data, 'unexpected request' );
		$request_body = json_decode( $request_data['body'] );

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$entries = $request_body->threatInfo->threatEntries;
		self::assertCount( 1, $entries, 'unexpected number of requested threat entries' );
		self::assertEquals(
			urlencode( 'https://antivirus.pluginkollektiv.org/test/' ),
			$entries[0]->url,
			'unexpected blog URL in requested threat entries'
		);

		/*
		 * Case 2: Emulated threat response.
		 */
		$mail_recipient = null;
		$mail_subject   = null;
		$mail_body      = null;
		WP_Mock::userFunction( 'wp_mail' )
				->with( Mockery::capture( $mail_recipient ), Mockery::capture( $mail_subject ), Mockery::capture( $mail_body ) )
				->atLeast()
				->once();

		AntiVirus_SafeBrowsing::check_safe_browsing();

		self::assertEquals( 'admin@example.com', $mail_recipient, 'Mail should have been sent to site admin' );
		self::assertEquals( '[AntiVirus Test Blog] Safe Browsing Alert', $mail_subject, 'Unexpected mail subject' );
		self::assertStringContainsString(
			'https://transparencyreport.google.com/safe-browsing/search?url=https%3A%2F%2Fantivirus.pluginkollektiv.org%2Ftest%2F&hl=de',
			$mail_body,
			'Mail body does not contain expected link to transparency report'
		);

		/*
		 * Case 3: With custom API key and notification address.
		 */
		$this->update_options( array( 'notify_email' => 'notification@example.com' ) );

		AntiVirus_SafeBrowsing::check_safe_browsing();

		self::assertEquals(
			'https://safebrowsing.googleapis.com/v4/threatMatches:find?key=custom-api-key',
			$request_url,
			'expected call to Safe Browsing API with custom key'
		);
		self::assertEquals(
			'notification@example.com',
			$mail_recipient,
			'Mail should have been sent to specified notification address'
		);

		/*
		 * Case 4: Assume code 403 for an expired key.
		 */
		AntiVirus_SafeBrowsing::check_safe_browsing();

		self::assertEquals(
			'[AntiVirus Test Blog] Safe Browsing check failed',
			$mail_subject,
			'expected different subject for Safe Browsing check failing with 403'
		);
		self::assertStringContainsString( "\r\n  Quota exceeded\r\n", $mail_body, 'Message from response not transported to mail' );

		/*
		 * Case 5: Assume code 400 for invalid key.
		 */
		AntiVirus_SafeBrowsing::check_safe_browsing();
		self::assertEquals(
			'[AntiVirus Test Blog] Safe Browsing check failed',
			$mail_subject,
			'expected different subject for Safe Browsing check failing with 400'
		);
		self::assertStringContainsString( "\r\n  Invalid API key\r\n", $mail_body, 'Message from response not transported to mail' );

		/*
		 * Case 6: Without API key.
		 */
		$this->update_options( array( 'safe_browsing_key' => '' ) );
		$request_url = null;

		AntiVirus_SafeBrowsing::check_safe_browsing();

		self::assertNull(
			$request_url,
			'API should not be called without an API key'
		);
	}
}
