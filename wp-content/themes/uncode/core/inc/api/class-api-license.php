<?php
/**
 * API License Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Uncode_API_License' ) ) :

/**
 * Uncode_API_License Class
 */
class Uncode_API_License {

	/**
	 * Endpoint.
	 */
	private $endpoint = 'http://static.undsgn.com/api/';

	/**
	 * Server name.
	 */
	private $server_name;

	/**
	 * Server name.
	 */
	private $user_agent = 'Uncode API';

	/**
	 * Get things going
	 */
	function __construct() {
		$server_name = empty( $_SERVER[ 'SERVER_NAME' ] ) ? $_SERVER[ 'HTTP_HOST' ]: $_SERVER[ 'SERVER_NAME' ];

		// Get real domain (skip IP addresses)
		if ( ! filter_var( $server_name, FILTER_VALIDATE_IP ) ) {
			$server_name = uncode_get_domain( $server_name );
		}

		$this->server_name = $server_name;
	}

	/**
	 * Check if the purchase code is already registered in another domain.
	 *
	 * Returns:
	 *     1 - This purchase code was already used with another domain
	 *     2 - This is a local domain, so we can register the product
	 *     3 - This is the domain already registered with the purchase code, or a subdomain
	 *     4 - This domain was never registered on our DB
	 *     5 - Envato API down (or invalid purchase code)
	 *     6 - Old PHP version
	 */
	public function is_license_already_in_use( $purchase_code ) {
		// First check if we are on a supported PHP version
		if ( ! function_exists( 'password_verify' ) ) {
			return 6;
		}

		// Return early if we are on a local domain
		if ( uncode_is_local_installation( $this->server_name ) ) {
			return 2;
		}

		$license = $this->get_license( $purchase_code );

		// If false, it means that our server is down
		if ( $license === false ) {
			return 5;
		}

		if ( is_array( $license ) && ! empty( $license ) ) {
			// Get first element (we have only one element)
			$license = $license[ 0 ];

			$registered_domain = isset( $license->domain ) ? $license->domain : false;

			if ( $registered_domain ) {
				$same_domain = password_verify( $this->server_name, $registered_domain );

				// Check if we are on the same registered domain
				if ( $same_domain ) {
					// Same registered domain (or a subdomain of it)
					return 3;
	            } else {
	            	// Already registered on another domain
	            	return 1;
	            }
			}
		}

		return 4;
	}

	/**
	 * Get license (if exists).
	 */
	public function get_license( $purchase_code ) {
		$response = wp_remote_post( $this->endpoint . 'get_license.php', array(
			'user-agent' => $this->user_agent,
			'body'       => array(
				'purchase_code' => urlencode( $purchase_code ),
			),
			'sslverify'  => false,
			'timeout'    => 45
		) );

		if ( is_wp_error( $response ) ) {
			return false;
	  	}

	  	$response_code = wp_remote_retrieve_response_code( $response );

	  	if ( 200 !== $response_code ) {
	  		return false;
	  	}

	  	$response_data = json_decode( wp_remote_retrieve_body( $response ) );

	  	return $response_data;
	}

	/**
	 * Add new license.
	 */
	public function add_license( $args ) {
		// Skip local installations
		if ( uncode_is_local_installation( $this->server_name ) ) {
			return;
		}

		// Old PHP versions
		if ( ! function_exists( 'password_hash' ) ) {
			return;
		}

		$args[ 'domain' ] = password_hash( $this->server_name, PASSWORD_DEFAULT );

		$response = wp_remote_post( $this->endpoint . 'add_license.php', array(
			'user-agent' => $this->user_agent,
			'body'       => $args,
			'sslverify'  => false,
			'timeout'    => 45
		) );

		if ( is_wp_error( $response ) ) {
			return false;
	  	}

	  	$response_code = wp_remote_retrieve_response_code( $response );

	  	if ( 200 !== $response_code ) {
	  		return false;
	  	}

	  	$response_data = json_decode( wp_remote_retrieve_body( $response ) );

	  	return $response_data;
	}

	/**
	 * Update existing license.
	 */
	public function update_license( $args ) {
		// Skip local installations
		if ( uncode_is_local_installation( $this->server_name ) ) {
			return;
		}

		// Old PHP versions
		if ( ! function_exists( 'password_hash' ) ) {
			return;
		}

		$args[ 'domain' ] = password_hash( $this->server_name, PASSWORD_DEFAULT );

		$response = wp_remote_post( $this->endpoint . 'update_license.php', array(
			'user-agent' => $this->user_agent,
			'body'       => $args,
			'sslverify'  => false,
			'timeout'    => 45
		) );

		if ( is_wp_error( $response ) ) {
			return false;
	  	}

	  	$response_code = wp_remote_retrieve_response_code( $response );

	  	if ( 200 !== $response_code ) {
	  		return false;
	  	}

	  	$response_data = json_decode( wp_remote_retrieve_body( $response ) );

	  	return $response_data;
	}

	/**
	 * Delete license.
	 */
	public function delete_license( $purchase_code ) {
		$response = wp_remote_post( $this->endpoint . 'delete_license.php', array(
			'user-agent' => $this->user_agent,
			'body'       => array(
				'purchase_code' => urlencode( $purchase_code ),
			),
			'sslverify'  => false,
			'timeout'    => 45
		) );

		if ( is_wp_error( $response ) ) {
			return false;
	  	}

	  	$response_code = wp_remote_retrieve_response_code( $response );

	  	if ( 200 !== $response_code ) {
	  		return false;
	  	}

	  	$response_data = json_decode( wp_remote_retrieve_body( $response ) );

	  	return $response_data;
	}

	/**
	 * Check if there are no records for this purchase code.
	 */
	public function check_if_purchase_code_has_record( $purchase_code ) {
		$license_data = $this->get_license( $purchase_code );

		// Record found
		if ( is_array( $license_data ) && count( $license_data ) > 0 ) {
			return true;
		}

		return false;
	}
}

endif;
