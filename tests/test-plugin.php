<?php
/**
 * Test our plugin.
 *
 * @package AntiVirus
 */

/**
 * Class AntiVirus_Test_Plugin.
 */
class AntiVirus_Test_Plugin extends WP_UnitTestCase {
	/**
	 * The plugin should be installed and activated.
	 */
	function test_plugin_activated() {
		$this->assertTrue( class_exists( 'AntiVirus' ) );
	}


	/**
	 * Test the Safe Browsing check.
	 */
	function test_safe_browsing() {
		/*
		 * Case 1: cron job disabled, Safe Browsing enabled.
		 */
		update_option(
			'antivirus',
			array(
				'cronjob_enable'    => 1,
				'notify_email'      => '',
				'safe_browsing'     => 1,
				'safe_browsing_key' => '',
			)
		);

		AntiVirus::do_daily_cronjob();

		$this->assertNotNull( get_wp_remote_post_request(), 'Remote POST should have been called (Safe Browsing check executed)' );
		$this->assertNull( get_wp_mail(), 'No mail should have been sent' );

		/*
		 * Case 2: cron job ensabled, Safe Browsing disabled.
		 */
		update_option(
			'antivirus',
			array(
				'cronjob_enable'    => 1,
				'notify_email'      => '',
				'safe_browsing'     => 0,
				'safe_browsing_key' => '',
			)
		);

		AntiVirus::do_daily_cronjob();

		$this->assertNotNull( get_wp_remote_post_request(), 'Remote POST should have been called (Safe Browsing check executed)' );
		$this->assertNull( get_wp_mail(), 'No mail should have been sent' );

		/*
		 * Case 3: cron job enabled, Safe Browsing enabled, default key.
		 */
		update_option(
			'antivirus',
			array(
				'cronjob_enable'    => 1,
				'notify_email'      => '',
				'safe_browsing'     => 1,
				'safe_browsing_key' => '',
			)
		);

		AntiVirus::do_daily_cronjob();

		$request = get_wp_remote_post_request();
		$this->assertNotNull( $request, 'Remote POST should have been called (Safe Browsing check executed)' );
		$this->assertNull( get_wp_mail(), 'No mail should have been sent' );
		$this->assertStringEndsWith( '?key=AIzaSyCGHXUd7vQAySRLNiC5y1M_wzR2W0kCVKI', $request[0], 'Default API key should have been used' );

		// Check if JSON request is correct.
		$this->assertEquals( 'application/json', $request[1]['headers']['Content-Type'], 'Content-Type header not correct' );
		$json_request = json_decode( $request[1]['body'] );
		$this->assertNotNull( $json_request, 'Invalid JSON in body' );
		$this->assertEquals( 1, count( $json_request->threatInfo->threatEntries ), 'Unexpected number of site URLs' );
		$this->assertEquals( urlencode( 'http://example.org' ), $json_request->threatInfo->threatEntries[0]->url, 'Invalid site URL in request' );

		/*
		 * Case 4: with custom API key.
		 */
		update_option(
			'antivirus',
			array(
				'cronjob_enable'    => 1,
				'notify_email'      => '',
				'safe_browsing'     => 1,
				'safe_browsing_key' => 'my-custom-key',
			)
		);
		AntiVirus::do_daily_cronjob();
		$request = get_wp_remote_post_request();
		$this->assertNotNull( $request, 'Remote POST should have been called (Safe Browsing check executed)' );
		$this->assertNull( get_wp_mail(), 'No mail should have been sent' );
		$this->assertStringEndsWith( '?key=my-custom-key', $request[0], 'Custom API key should have been used' );
		$this->assertEquals( $json_request, json_decode( $request[1]['body'] ), 'Request body should not differ from previous request with default key' );

		/*
		 * Case 5: Until now all checks have failed with WP_Error. Assume a correct 200 empty-body response.
		 */
		mock_wp_remote_post_response(
			array(
				'headers'  => array(),
				'body'     => '',
				'response' => array(
					'code'    => 200,
					'message' => 'OK'
				)
			)
		);

		AntiVirus::do_daily_cronjob();
		$request = get_wp_remote_post_request();
		$this->assertNotNull( $request, 'Remote POST should have been called (Safe Browsing check executed)' );
		$this->assertNull( get_wp_mail(), 'No mail should have been sent' );
		$this->assertStringEndsWith( '?key=my-custom-key', $request[0], 'Custom API key should have been used' );
		$this->assertEquals( $json_request, json_decode( $request[1]['body'] ), 'Request body should not differ from previous request with default key' );

		/*
		 * Case 6: Successful request with threat content.
		 */
		clear_wp_remote_post_request();
		clear_wp_remote_post_response();
		mock_wp_remote_post_response(
			array(
				'headers'  => array(),
				'body'     => '{"matches":[{"threatType":"MALWARE","platformType":"WINDOWS","threatEntryType":"URL","threat":{"url":"http://www.urltocheck1.org/"},"threatEntryMetadata":{"entries":[{"key":"malware_threat_type","value":"landing"}]},"cacheDuration":"300.000s"},{"threatType":"MALWARE","platformType":"WINDOWS","threatEntryType":"URL","threat":{"url":"http://www.urltocheck2.org/"},"threatEntryMetadata":{"entries":[{"key":"malware_threat_type","value":"landing"}]},"cacheDuration":"300.000s"}]}',
				'response' => array(
					'code'    => 200,
					'message' => 'OK'
				)
			)
		);

		AntiVirus::do_daily_cronjob();
		$request = get_wp_remote_post_request();
		$this->assertNotNull( $request, 'Remote POST should have been called (Safe Browsing check executed)' );
		$mail = get_wp_mail();
		$this->assertNotNull( $mail, 'Mail should have been sent' );
		$this->assertEquals('admin@example.org', $mail[0], 'Recipient should be default site admin');
		$this->assertEquals('[Test Blog] Safe Browsing Alert', $mail[1], 'Not an alert message');

		/*
		 * Case 7: Same for configured noticifation address.
		 */
		update_option(
			'antivirus',
			array(
				'cronjob_enable'    => 1,
				'notify_email'      => 'testme@example.org',
				'safe_browsing'     => 1,
				'safe_browsing_key' => 'my-custom-key',
			)
		);

		clear_wp_remote_post_request();
		AntiVirus::do_daily_cronjob();
		$request = get_wp_remote_post_request();
		$this->assertNotNull( $request, 'Remote POST should have been called (Safe Browsing check executed)' );
		$mail = get_wp_mail();
		$this->assertNotNull( $mail, 'Mail should have been sent' );
		$this->assertEquals('testme@example.org', $mail[0], 'Recipient should be configured address');
		$this->assertEquals('[Test Blog] Safe Browsing Alert', $mail[1], 'Not an alert message');

		/*
		 * Case 8: Assume code 403 for an expired key.
		 */
		mock_wp_remote_post_response(
			array(
				'headers'  => array(),
				'body'     => '{"error":{"message":"Quota exceeded"}}',
				'response' => array(
					'code'    => 403,
					'message' => 'Forbidden'
				)
			)
		);

		clear_wp_remote_post_request();
		AntiVirus::do_daily_cronjob();
		$request = get_wp_remote_post_request();
		$this->assertNotNull( $request, 'Remote POST should have been called (Safe Browsing check executed)' );
		$mail = get_wp_mail();
		$this->assertNotNull( $mail, 'Mail should have been sent' );
		$this->assertEquals('testme@example.org', $mail[0], 'Recipient should be configured address');
		$this->assertEquals('[Test Blog] Safe Browsing check failed', $mail[1], 'Not a "check-failed" message');
		$this->assertContains("\r\n  Quota exceeded\r\n", $mail[2], 'Message from response not transported to mail');

		/*
		 * Case 9: Assume code 400 for invalid key.
		 */
		mock_wp_remote_post_response(
			array(
				'headers'  => array(),
				'body'     => '{"error":{"message":"Invalid API key"}}',
				'response' => array(
					'code'    => 400,
					'message' => 'Forbidden'
				)
			)
		);

		clear_wp_remote_post_request();
		AntiVirus::do_daily_cronjob();
		$request = get_wp_remote_post_request();
		$this->assertNotNull( $request, 'Remote POST should have been called (Safe Browsing check executed)' );
		$mail = get_wp_mail();
		$this->assertNotNull( $mail, 'Mail should have been sent' );
		$this->assertEquals('testme@example.org', $mail[0], 'Recipient should be configured address');
		$this->assertEquals('[Test Blog] Safe Browsing check failed', $mail[1], 'Not a "check-failed" message');
		$this->assertContains("\r\n  Invalid API key\r\n", $mail[2], 'Message from response not transported to mail');
	}
}
