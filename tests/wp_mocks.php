<?php
/**
 * Mock support for interaction with the world.
 *
 * This file provides some helper methods for HTTP POST and mail interception.
 * runkit or runkit7 are required to use these mocks.
 */

$antivirus_mock_wp_remote_requests = array();
$antivirus_mock_wp_remote_response = array();
$antivirus_mock_wp_mail_capture    = array();

/**
 * Mock result of wp_remote_post() call.
 *
 * If matchers are provided, the response will only be returned if the resquest matches both.
 * Otherwise the response will always be returned.
 *
 * @param mixed        $result
 * @param string|null  $url         Regular expression to match the URL (optional).
 * @param Closure|null $arg_matcher Matcher function for request arguments (optional).
 */
function mock_wp_remote_post_response( $result, $url_matcher = null, $arg_matcher = null ) {
	global $antivirus_mock_wp_remote_response;

	$antivirus_mock_wp_remote_response[] = array(
		$url_matcher,
		$arg_matcher,
		$result
	);
}

/**
 * Clear captured requests.
 */
function clear_wp_remote_post_request() {
	global $antivirus_mock_wp_remote_requests;

	$antivirus_mock_wp_remote_requests = array();
}

/**
 * Clear mocked responses.
 */
function clear_wp_remote_post_response() {
	global $antivirus_mock_wp_remote_response;

	$antivirus_mock_wp_remote_response = array();
}

/**
 * Get captured request from wp_remote_post calls.
 *
 * @param integer $offset Offset to return. If 0 (default) the latest is returned, 1 the before-latest, etc.
 *
 * @return array Array of requested URL and arguments.
 */
function get_wp_remote_post_request( $offset = 0 ) {
	global $antivirus_mock_wp_remote_requests;

	if ( count( $antivirus_mock_wp_remote_requests ) <= $offset ) {
		return null;
	} else {
		return $antivirus_mock_wp_remote_requests[ count( $antivirus_mock_wp_remote_requests ) - 1 - $offset ];
	}
}

/**
 * Get captured mails from wp_mail calls.
 *
 * @param integer $offset Offset to return. If 0 (default) the latest is returned, 1 the before-latest, etc.
 *
 * @return array Array of recipient, subject, message and headers.
 */
function get_wp_mail( $offset = 0 ) {
	global $antivirus_mock_wp_mail_capture;

	if ( count( $antivirus_mock_wp_mail_capture ) <= $offset ) {
		return null;
	} else {
		return $antivirus_mock_wp_mail_capture[ count( $antivirus_mock_wp_mail_capture ) - 1 - $offset ];
	}
}


/*
 * Mock the wp_remote_post() functkion to intercept POST requests.
 */
$antivirus_mock_wp_remote_post = 'global $antivirus_mock_wp_remote_requests;
global $antivirus_mock_wp_remote_response;

$antivirus_mock_wp_remote_requests[] = array( $url, $args );

$response = new WP_Error();
foreach ( $antivirus_mock_wp_remote_response as $r ) {
	if ( ( empty( $r[0] ) || preg_match( $r[0], $url ) ) ||
	     ( empty( $r[1] ) || $r[1]( $args ) ) ) {
		$response = $r[2];
	}
}

return $response;';

/*
 * Mock the wp_mail() function to intercept outgoing mails.
 */
$antivirus_mock_wp_mail = 'global $antivirus_mock_wp_mail_capture;

$antivirus_mock_wp_mail_capture[] = array(
	$to,
	$subject,
	$message,
	$headers,
);

return true;
';

if ( function_exists( 'runkit7_function_redefine' ) ) {
	runkit7_function_redefine( 'wp_remote_post', '$url, $args = array()', $antivirus_mock_wp_remote_post );
	runkit7_function_redefine( 'wp_mail', '$to, $subject, $message, $headers = \'\', $attachments = array()', $antivirus_mock_wp_mail );
} elseif ( function_exists( 'runkit_function_redefine' ) ) {
	runkit_function_redefine( 'wp_remote_post', '$url, $args = array()', $antivirus_mock_wp_remote_post );
	runkit_function_redefine( 'wp_mail', '$to, $subject, $message, $headers = \'\', $attachments = array()', $antivirus_mock_wp_mail );
} else {
	throw new Exception( 'runkit extension not installed' );
}
