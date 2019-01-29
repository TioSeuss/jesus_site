<?php
/**
 * Core utilities functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Check if a dependency is active.
 */
function uncode_check_for_dependency( $plugin ) {
	return in_array( $plugin, (array) get_option( 'active_plugins', array() ) ) || uncode_check_for_network_dependency( $plugin );
}

/**
 * Check if a dependency is active on network.
 */
function uncode_check_for_network_dependency( $plugin ) {
	if ( ! is_multisite() ) {
		return false;
	}

	$plugins = get_site_option( 'active_sitewide_plugins');

	if ( isset( $plugins[ $plugin ] ) ) {
		return true;
	}

	return false;
}
