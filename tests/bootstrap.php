<?php
/**
 * PHPUnit bootstrapping.
 *
 * @package AntiVirus
 */

// phpcs:ignore Squiz.Commenting.FileComment.Missing
require_once __DIR__ . '/../vendor/autoload.php';

WP_Mock::bootstrap();

require_once __DIR__ . '/antivirustestcase.php';
require_once __DIR__ . '/../inc/class-antivirus.php';
