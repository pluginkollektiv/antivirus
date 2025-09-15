<?php
/**
 * Plugin Name:       AntiVirus
 * Plugin URI:        https://antivirus.pluginkollektiv.org
 * Description:       Security plugin to protect your blog or website against exploits and spam injections.
 * Author:            pluginkollektiv
 * Author URI:        https://pluginkollektiv.org
 * Version:           1.6.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       antivirus
 *
 * @package AntiVirus
 */

/*
Copyright (C)  2009-2015 Sergej Müller
Copyright (C)  2016-2025 pluginkollektiv

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

// Make sure we don't expose any info if called directly.
if ( ! class_exists( 'WP' ) ) {
	die();
}

define( 'ANTIVIRUS_FILE', __FILE__ );

/**
 * Plugin autoloader.
 *
 * @param string $class_name The classname.
 */
function antivirus_autoload( string $class_name ): void {
	if ( in_array( $class_name, array( 'AntiVirus', 'AntiVirus_CheckInternals', 'AntiVirus_SafeBrowsing', 'AntiVirus_ChecksumVerifier' ), true ) ) {
		require_once sprintf(
			'%s%s%s%sclass-%s.php',
			__DIR__,
			DIRECTORY_SEPARATOR,
			'inc',
			DIRECTORY_SEPARATOR,
			strtolower( str_replace( '_', '-', $class_name ) )
		);
	}
}

// Register autoloader.
spl_autoload_register( 'antivirus_autoload' );


// Initialize the plugin.
add_action( 'plugins_loaded', array( 'AntiVirus', 'init' ), 99 );

/* Hooks */
register_activation_hook( __FILE__, array( 'AntiVirus', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'AntiVirus', 'deactivation' ) );
register_uninstall_hook( __FILE__, array( 'AntiVirus', 'uninstall' ) );
