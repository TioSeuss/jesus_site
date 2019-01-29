<?php
/**
 * API related functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'uncode_envato_api' ) ) :

	/**
	 * The main function responsible for returning the one true
	 * Uncode_Envato_API Instance to functions everywhere.
	 */
	function uncode_envato_api() {
		return Uncode_Envato_API::instance();
	}

endif;

if ( ! function_exists( 'uncode_get_domain' ) ) :

	/**
	 * Extract domain from hostname
	 */
	function uncode_get_domain( $url ) {
		$pieces = parse_url( $url );
		$domain = isset( $pieces[ 'path' ] ) ? $pieces[ 'path' ] : '';

		if ( preg_match( '/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs ) ) {
			return $regs[ 'domain' ];
		}

		return false;
	}

endif;

if ( ! function_exists( 'uncode_is_local_installation' ) ) :

	/**
	 * Check if we are in a local installation
	 */
	function uncode_is_local_installation( $hostname = false ) {
		$hostname = $hostname ? $hostname : ( empty( $_SERVER[ 'SERVER_NAME' ] ) ? $_SERVER[ 'HTTP_HOST' ]: $_SERVER[ 'SERVER_NAME' ] );

		if ( substr_count( $hostname, '.dev' ) > 0 || substr_count( $hostname, '.local' ) > 0 ) {
			return true;
		}

		return false;
	}

endif;
