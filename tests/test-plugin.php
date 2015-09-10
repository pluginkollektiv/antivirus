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
}
