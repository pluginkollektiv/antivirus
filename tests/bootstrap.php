<?php
/**
 * PHPUnit bootstrapping.
 *
 * @package AntiVirus
 */

require_once __DIR__ . '/../vendor/autoload.php';

WP_Mock::bootstrap();

require_once __DIR__ . '/antivirustestcase.php';
require_once __DIR__ . '/../inc/class-antivirus.php';
