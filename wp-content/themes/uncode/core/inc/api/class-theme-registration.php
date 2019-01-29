<?php
/**
 * Theme Registration Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Uncode_Theme_Registration' ) ) :

/**
 * Uncode_Theme_Registration Class
 */
class Uncode_Theme_Registration {

	/**
	 * Whether the token is valid or not.
	 */
	private $valid_token = false;

	/**
	 * Valid token option (DB).
	 */
	private $valid_token_option_name = 'uncode_registration_valid_token';

	/**
	 * Token option (DB).
	 */
	private $token_option_name = 'uncode_registration_token';

	/**
	 * An instance of the Uncode_Envato_API class.
	 */
	private $envato_api = null;

	/**
	 * Error message option (DB).
	 */
	private $error_option_name = 'uncode_registration_error';

	/**
	 * Purchase code option (DB).
	 */
	private $purchase_code_option_name = 'uncode_registration_purchase_code';

	/**
	 * Valid purchase code option (DB).
	 */
	private $valid_purchase_code_option_name = 'uncode_registration_valid_purchase_code';

	/**
	 * Buyer's purchased licenses for Uncode.
	 */
	private $purchased_licenses = array();

	/**
	 * Flag for already used purchse codes (DB).
	 */
	private $purchase_code_not_already_in_use_option_name = 'uncode_registration_purchase_code_not_already_in_use';

	/**
	 * Flag for accepted terms (DB).
	 */
	private $terms_option_name = 'uncode_registration_accepted_terms';

	/**
	 * Whether the form was just submitted or not.
	 */
	private $updated = false;

	/**
	 * Received a valid response from the Envato API.
	 */
	private $valid_response = false;

	/**
	 * Get things going
	 */
	function __construct() {
		// Save token
		add_action( 'wp_loaded', array( $this, 'save_token' ) );
		add_action( 'wp_loaded', array( $this, 'check_license' ), 20 );
	}

	/**
	 * Save the token.
	 */
	public function save_token() {

		if ( isset( $_POST[ 'uncode_registration_form' ] ) && isset( $_POST[ '_wpnonce' ] ) && wp_verify_nonce( $_POST[ '_wpnonce' ], 'uncode-registration-form' ) ) {

			// Force deregistration
			$force_deregistration = isset( $_POST[ 'uncode_registration_deregister_product' ] ) && $_POST[ 'uncode_registration_deregister_product' ] ? true : false;

			// Return early if terms were not accepted
			$accept_terms = isset( $_POST[ 'uncode_registration_accept_terms' ] ) ? true : false;

			if ( ! $accept_terms && ! $force_deregistration ) {
				$this->add_error( esc_html__( 'You must accept the terms before to register the theme.', 'uncode' ) );
				delete_option( $this->terms_option_name );
				return;
			} else {
				update_option( $this->terms_option_name, true );
			}

			// Token
			$token = isset( $_POST[ 'envato_token' ] ) ? sanitize_text_field( $_POST[ 'envato_token' ] ) : '';
			// Purchase code
			$purchase_code = isset( $_POST[ 'envato_purchase_code' ] ) ? sanitize_text_field( $_POST[ 'envato_purchase_code' ] ) : '';

			// Return early if the token is empty
			// or if we are deregistering a license
			// and delete all the options from DB
			if ( ! $token || $force_deregistration ) {
				$this->delete_options();
				return;
			}

			// Delete previously saved purchase code
			$old_purchase_code       = get_option( $this->purchase_code_option_name );
			$old_purchase_code_valid = get_option( $this->valid_purchase_code_option_name );

			if ( $old_purchase_code && $old_purchase_code_valid && ( $old_purchase_code != $purchase_code ) ) {
				$this->delete_license( $old_purchase_code );
			}

			// Save token
			update_option( $this->token_option_name, $token );

			// Save purchase code
			update_option( $this->purchase_code_option_name, $purchase_code );

			// Check the validity of the token
			$is_valid_token = $this->is_valid_token( $token );

			// Update valid/invalid token option
			update_option( $this->valid_token_option_name, $is_valid_token );

			if ( $is_valid_token ) {
				// Empty registration error if we have a valid token
				$this->empty_error();

				// Check the validity of the purchase code
				$is_valid_purchase_code = $this->is_valid_purchase_code( $purchase_code );

				// Update valid/invalid purchase code option
				update_option( $this->valid_purchase_code_option_name, $is_valid_purchase_code );

				// Check if we need to force the activation on this domain
				$force_activation = isset( $_POST[ 'uncode_registration_force_to_this_domain' ] ) && $_POST[ 'uncode_registration_force_to_this_domain' ] ? true : false;

				if ( $is_valid_purchase_code ) {
					// Save the purchase code on this domain
					$this->save_license( $purchase_code, $force_activation );
				}
			} else {
				delete_option( $this->valid_purchase_code_option_name );

				// Delete previously saved purchase code
				if ( $old_purchase_code && $old_purchase_code_valid ) {
					$this->delete_license( $old_purchase_code );
				}
			}

			$this->updated = true;
		}
	}

	/**
	 * Check the token.
	 */
	private function is_valid_token( $token ) {
		// Return early if the token is empty
		if ( ! $token  ) {
			return false;
		}

		// Check string length
		if ( 32 === strlen( $token ) ) {
			// Make an API call to see if the token is valid
			if ( $this->check_token_via_api( $token ) ) {
				return true;
			} else if ( $this->valid_response ) {
				// Valid token (received a correct response from Envato)
				// but the user doesn't have a valid 'Uncode' license
				$this->add_error( esc_html__( 'Invalid token. Your Envato account does not have Uncode among purchased items.', 'uncode' ) );
			}
		} else {
			$this->add_error( esc_html__( 'Invalid token. The token must contain 32 characters.', 'uncode' ) );
		}

		return false;
	}

	/**
	 * Check the token via API.
	 * The API returns a list of purchased themes.
	 * So we check if "Uncode" is one of them.
	 */
	private function check_token_via_api( $token, $page = '' ) {
		// Set token for the API call
		if ( '' !== $token ) {
			$this->envato_api()->set_token( $token );
		}

		$themes = $this->envato_api()->themes( array(), $page );

		// Check response errors
		if ( is_wp_error( $themes ) ) {
			$this->handle_errors( $themes );
			return false;
		}

		// Flags
		$this->valid_response = true;
		$ret                  = false;

		// Check if the theme is on the list of purchased themes
		foreach ( $themes as $theme ) {
			if ( isset( $theme[ 'name' ] ) ) {
				if ( 'Uncode' === $theme[ 'name' ] ) {
					$license_details = array(
						'purchase_code' => $theme[ 'purchase_code' ],
					);

					$this->purchased_licenses[] = $license_details;
					$ret = true;
				}
			}
		}

		if ( $ret ) {
			return true;
		}

		// Handle users with more than 100 items.
		// Envato returns max 100 items per call
		// so we need to pass the $page parameter.
		if ( 100 === count( $themes ) ) {
			$page = ( ! $page ) ? 2 : $page + 1;
			return $this->check_token_via_api( '', $page );
		}

		return false;
	}

	/**
	 * Check the purchase code.
	 */
	private function is_valid_purchase_code( $purchase_code ) {
		// Return early if the purchase code is empty
		if ( ! $purchase_code  ) {
			$this->add_error( esc_html__( 'Invalid purchase code.', 'uncode' ) );
			return false;
		}

		foreach ( $this->purchased_licenses as $purchased_license ) {
			if ( isset( $purchased_license[ 'purchase_code' ] ) && $purchase_code == $purchased_license[ 'purchase_code' ] ) {
				return true;
			}
		}

		$this->add_error( esc_html__( 'Invalid purchase code.', 'uncode' ) );

		return false;
	}

	/**
	 * Check if the purchase code is already in use on another domain.
	 */
	private function check_if_already_in_use( $purchase_code ) {
		$uncode_api_license = new Uncode_API_License();
		$check_license      = $uncode_api_license->is_license_already_in_use( $purchase_code );

		if ( $check_license === 5 ) {
			// Server down but the token is correct
			delete_option( $this->purchase_code_not_already_in_use_option_name );

			$this->add_error( esc_html__( 'Please check your firewall server settings or try again later.', 'uncode' ) );

			return false;

		} else if ( $check_license === 1 ) {
			// This purchase code was already used with another domain
			delete_option( $this->purchase_code_not_already_in_use_option_name );

			return true;

		} else {
			// Empty registration error
			$this->empty_error();

			update_option( $this->purchase_code_not_already_in_use_option_name, true );

			return false;
		}
	}

	/**
	 * Envato API class.
	 */
	private function envato_api() {
		if ( null === $this->envato_api ) {
			$this->envato_api = uncode_envato_api();
		}

		return $this->envato_api;
	}

	/**
	 * Handle response erros.
	 */
	private function handle_errors( $error ) {
		// If we have a WP Error there was a problem with
		// the token or the Envato API maybe down.
		//     401 - Unauthorized (token not valid)
		//     403 - Forbidden (token not valid)
		//     429 - Rate limit
		if ( $error->get_error_code() ) {
			switch ( $error->get_error_code() ) {
				case 401:
					$this->add_error( esc_html__( 'Invalid token. Access unauthorized.', 'uncode' ) );
					break;

				case 403:
					$this->add_error( esc_html__( 'Invalid token. Access forbidden.', 'uncode' ) );
					break;

				case 429:
					$this->add_error( esc_html__( 'Rate limit exceeded.', 'uncode' ) );
					break;

				default:
					$this->add_error( esc_html__( 'The Envato API is currently down. Please try again later.', 'uncode' ) );
					break;
			}
		} else {
			$this->add_error( esc_html__( 'Invalid response. Please try again later.', 'uncode' ) );
		}

		return;
	}

	/**
	 * Save error message in DB.
	 */
	private function add_error( $message ) {
		update_option( $this->error_option_name, $message );
	}

	/**
	 * Empty error message in DB.
	 */
	private function empty_error() {
		delete_option( $this->error_option_name );
	}

	/**
	 * Delete registration options from DB.
	 */
	private function delete_options() {
		// Delete previously saved purchase code
		$old_purchase_code = get_option( $this->purchase_code_option_name );
		$old_purchase_code_valid = get_option( $this->valid_purchase_code_option_name );

		if ( $old_purchase_code && $old_purchase_code_valid ) {
			$this->delete_license( $old_purchase_code );
		}

		delete_option( $this->valid_token_option_name );
		delete_option( $this->token_option_name );
		delete_option( $this->error_option_name );
		delete_option( $this->purchase_code_option_name );
		delete_option( $this->valid_purchase_code_option_name );
		delete_option( $this->purchase_code_not_already_in_use_option_name );
		delete_option( $this->terms_option_name );
	}

	/**
	 * Check the license (ie. if the purchase code was already registered to another domain).
	 */
	public function check_license() {
		// Check only in System Status
		if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'uncode-system-status' ) {
			// Don't check the license when we save the token and the purchase code
			if ( ! $this->updated ) {
				$token                  = get_option( $this->token_option_name );
				$is_valid_token         = get_option( $this->valid_token_option_name, false );
				$purchase_code          = get_option( $this->purchase_code_option_name );
				$is_valid_purchase_code = get_option( $this->valid_purchase_code_option_name, false );

				// Make this check only if we have a valid token/purchase code
				if ( $token && $is_valid_token && $purchase_code && $is_valid_purchase_code ) {
					$is_already_in_use = $this->check_if_already_in_use( $purchase_code );

					if ( ! $is_already_in_use ) {
						$this->check_if_connected( $purchase_code, $token );
					}
				}
			}
		}
	}

	/**
	 * Save the license to this domain
	 */
	private function save_license( $purchase_code, $force_activation = false ) {
		// Skip local installations
		if ( uncode_is_local_installation() ) {
			return;
		}

		// First, check if the purchase code is already in use
		if ( $this->check_if_already_in_use( $purchase_code ) && ! $force_activation ) {
			return;
		}

		// Save license
		$license_data = $this->get_license_data_from_purchase_code( $purchase_code );

		if ( $license_data && is_array( $license_data ) ) {
			$args_license = array(
				'purchase_code'   => $purchase_code,
			);

			$uncode_api_license = new Uncode_API_License();

			if ( $force_activation ) {
				// Update license and use this domain
				$update_license = $uncode_api_license->update_license( $args_license );
				update_option( $this->purchase_code_not_already_in_use_option_name, true );
			} else {
				// Save new license and use this domain
				$add_license = $uncode_api_license->add_license( $args_license );
			}
		}
	}

	/**
	 * Check the license and the domain when the system status load.
	 *
	 * This handles an infrequent but possible scenario. Let's say that the
	 * license was activated on site A. Then we try to activate the license in site B.
	 * In site B we receive an error that says that the license is already active
	 * on another domain (site A). Whitout doing nothin in site B, the user goes to
	 * site A and deregister the theme from Site A. So there's not any record now
	 * for this purchase code. And we still need to attach the purchase code to site B.
	 * This function does that.
	 */
	private function check_if_connected( $purchase_code, $token ) {
		// Skip local installations
		if ( uncode_is_local_installation() ) {
			return;
		}

		// First, check if the purchase code is connected to any domain
		$uncode_api_license = new Uncode_API_License();
		$is_connected = $uncode_api_license->check_if_purchase_code_has_record( $purchase_code );

		if ( ! $is_connected ) {
			// Check the validity of the token again (just to be sure)
			$is_valid_token = $this->is_valid_token( $token );

			// Update valid/invalid token option
			update_option( $this->valid_token_option_name, $is_valid_token );

			if ( $is_valid_token ) {
				// Empty registration error if we have a valid token
				$this->empty_error();

				// Check the validity of the purchase code
				$is_valid_purchase_code = $this->is_valid_purchase_code( $purchase_code );

				// Update valid/invalid purchase code option
				update_option( $this->valid_purchase_code_option_name, $is_valid_purchase_code );

				if ( $is_valid_purchase_code ) {
					// Save the purchase code on this domain
					$this->save_license( $purchase_code );
				}
			}
		}
	}

	/**
	 * Delete the license
	 */
	private function delete_license( $purchase_code ) {
		$uncode_api_license = new Uncode_API_License();
		$uncode_api_license->delete_license( $purchase_code );
	}

	/**
	 * Get license data corresponding to a specific purchase code
	 */
	private function get_license_data_from_purchase_code( $purchase_code ) {
		foreach ( $this->purchased_licenses as $purchased_license ) {
			if ( isset( $purchased_license[ 'purchase_code' ] ) && $purchase_code == $purchased_license[ 'purchase_code' ] ) {
				return $purchased_license;
			}
		}

		return false;
	}
}

endif;

return new Uncode_Theme_Registration();
