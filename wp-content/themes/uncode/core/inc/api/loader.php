<?php
/**
 * Load API classes and functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	return;
}

if ( is_admin() ) {
	require_once 'class-api-license.php';
	require_once 'class-envato-api.php';
	require_once 'class-theme-registration.php';
	require_once 'class-theme-updater.php';
	require_once 'api-functions.php';
}
