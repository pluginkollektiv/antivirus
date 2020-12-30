<?php
/**
 * PHPUnit bootstrapping.
 *
 * @package AntiVirus
 */

// phpcs:ignore Squiz.Commenting.FileComment.Missing
require_once __DIR__ . '/../vendor/autoload.php';

// Override WP_CONTENT_DIR to use own resources instead of WP_Mock dummy files.
define( 'WP_CONTENT_DIR', __DIR__ . '/resources' );

WP_Mock::bootstrap();

require_once __DIR__ . '/antivirustestcase.php';
require_once __DIR__ . '/../inc/class-antivirus.php';

/**
 * Class WP_Theme_Mock.
 *
 * Mock-implementation of {@link WP_Theme}.
 */
class WP_Theme_Mock {
	/**
	 * Theme data.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * WP_Theme_Mock constructor.
	 *
	 * @param string             $name       Theme name.
	 * @param string             $stylesheet Theme stylesheet name.
	 * @param array              $files      Theme files.
	 * @param WP_Theme_Mock|null $parent     Parent theme (optional).
	 */
	public function __construct( string $name, string $stylesheet, array $files, WP_Theme_Mock $parent = null ) {
		$this->data = array(
			'Name'       => $name,
			'stylesheet' => $stylesheet,
			'files'      => $files,
			'parent'     => $parent ?? false,
		);
	}

	/**
	 * Get theme attribute.
	 *
	 * @param string $key Attribute key.
	 *
	 * @return mixed Attribute value.
	 */
	public function get( string $key ) {
		return $this->data[ $key ] ?? false;
	}

	/**
	 * Set theme attribute.
	 *
	 * @param string $key Attribute key.
	 * @param mixed  $val Attribute value.
	 */
	public function set( string $key, $val ): void {
		$this->data[ $key ] = $val;
	}

	/**
	 * Get stylesheet name.
	 *
	 * @return string Stylesheet name.
	 */
	public function get_stylesheet(): string {
		return $this->get( 'stylesheet' );
	}

	/**
	 * Get theme files.
	 *
	 * @param string $suffix File suffix (ignored here).
	 * @param int    $deptn  File hierarchy depth (ignored here).
	 *
	 * @return false|mixed
	 */
	public function get_files( string $suffix, int $deptn ) {
		return $this->get( 'files' );
	}

	/**
	 * Get parent theme.
	 *
	 * @return false|WP_Theme_Mock Parent theme or false.
	 */
	public function parent() {
		return $this->get( 'parent' );
	}
}
