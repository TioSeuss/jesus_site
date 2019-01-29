<?php
/**
 * Theme Updater Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Uncode_Theme_Updater' ) ) :

/**
 * Uncode_Theme_Updater Class
 */
class Uncode_Theme_Updater {

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
	 * An instance of the Uncode_Envato_API class.
	 */
	private $envato_api = null;

	/**
	 * API themes array from Envato.
	 */
	private $themes = array();

	/**
	 * Received a valid response from the Envato API.
	 */
	private $valid_response = false;

	/**
	 * Get things going
	 */
	function __construct() {
		// Check for theme & plugin updates.
		add_filter( 'http_request_args', array( $this, 'disable_wporg_request' ), 5, 2 );

		// Inject theme updates into the response array.
		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'update_themes' ) );
		add_filter( 'pre_set_transient_update_themes', array( $this, 'update_themes' ) );

		// Deferred Download.
		add_action( 'upgrader_package_options', array( $this, 'maybe_deferred_download' ), 99 );
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
	 * Disables requests to the wp.org repository for Uncode.
	 */
	public function disable_wporg_request( $request, $url ) {
		// If it's not a theme update request, bail.
		if ( false !== strpos( $url, '//api.wordpress.org/themes/update-check/1.1/' ) ) {

			// Decode JSON so we can manipulate the array.
			$data = json_decode( $request[ 'body' ][ 'themes' ] );

			// Remove the active parent and child themes from the check
	 		$parent = get_option( 'template' );
	 		$child = get_option( 'stylesheet' );
	 		unset( $data->themes->$parent );
	 		unset( $data->themes->$child );

			// Encode back into JSON and update the response.
			$request[ 'body' ][ 'themes' ] = wp_json_encode( $data );
		}

		return $request;
	}

	/**
	 * Inject update data for Uncode.
	 */
	public function update_themes( $transient ) {
		// Process Uncode updates.
		if ( isset( $transient->checked ) ) {

			// Get the installed version of Uncode.
			$current_uncode_version = UNCODE_PARENT_VERSION;

			if ( ! ( $this->themes && is_array( $this->themes ) && count( $this->themes ) > 0 ) ) {

				// Get saved token
				$token = get_option( $this->token_option_name, false );

				// Check the validity of the token
				$is_valid_token = $this->is_valid_token( $token );

				// Return early if the token is not valid
				if ( ! $is_valid_token ) {
					delete_option( $this->valid_purchase_code_option_name );
					return false;
				}

				// Empty registration error if we have a valid token
				$this->empty_error();

				// Get saved purchase code
				$purchase_code = get_option( $this->purchase_code_option_name, false );

				// Check the validity of the purchase code
				$is_valid_purchase_code = $this->is_valid_purchase_code( $purchase_code );

				// Update valid/invalid purchase code option
				update_option( $this->valid_purchase_code_option_name, $is_valid_purchase_code );

				// Return early if the purchase code is not valid
				if ( ! $is_valid_purchase_code ) {
					return false;
				}

				// Check if the token is already in use
				if ( $token && $is_valid_token && $purchase_code && $is_valid_purchase_code ) {
					$is_already_in_use = $this->check_if_already_in_use( $purchase_code );

					if ( ! $is_already_in_use ) {
						return false;
					}
				}
			}

			// Check if the purchase code is already registered in another domain
			$is_not_already_in_use = get_option( $this->purchase_code_not_already_in_use_option_name, false );

			if ( ! $is_not_already_in_use ) {
				return false;
			}

			// Get the themes from the Envato API.
			$themes = $this->themes;

			// Get latest Uncode version.
			$latest_uncode = array(
				'id'      => '',
				'name'    => '',
				'url'     => '',
				'version' => '',
			);

			foreach ( $themes as $theme ) {
				if ( isset( $theme[ 'name' ] ) && 'uncode' === strtolower( $theme[ 'name' ] ) ) {
					$latest_uncode = $theme;
					break;
				}
			}

			if ( version_compare( $current_uncode_version, $latest_uncode[ 'version' ], '<' ) ) {
				$transient->response[ strtolower( $latest_uncode[ 'name' ] ) ] = array(
					'theme'       => strtolower( $latest_uncode[ 'name' ] ),
					'new_version' => $latest_uncode[ 'version' ],
					'url'         => 'https://themeforest.net/item/uncode-creative-multiuse-wordpress-theme/13373220',
					'package'     => $this->deferred_download( $latest_uncode[ 'id' ] ),
					//beta testing
					//'package'     => '',
				);
			}
		}

		return $transient;
	}

	/**
	 * Filter the package options before running an update.
	 */
	public function maybe_deferred_download( $options ) {
		$package = $options[ 'package' ];
		if ( false !== strrpos( $package, 'deferred_download' ) && false !== strrpos( $package, 'item_id' ) ) {
			parse_str( wp_parse_url( $package, PHP_URL_QUERY ), $vars );

			if ( $vars[ 'item_id' ] ) {
				$args                 = $this->set_bearer_args();
				$options[ 'package' ] = $this->download( $vars[ 'item_id' ], $args );
			}
		}

		return $options;
	}

	/**
	 * Returns the bearer arguments for a request with a single use API Token.
	 */
	public function set_bearer_args() {
		$args  = array();
		$token = get_option( $this->token_option_name, false );

		if ( ! empty( $token ) ) {
			$args = array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'User-Agent'    => 'WordPress - Uncode',
				),
				'timeout' => 20,
			);
		}

		return $args;
	}

	/**
	 * Deferred item download URL.
	 */
	public function deferred_download( $id ) {
		if ( empty( $id ) ) {
			return '';
		}

		$args = array(
			'deferred_download' => true,
			'item_id'           => $id,
		);

		return add_query_arg( $args, esc_url( admin_url( 'admin.php?page=uncode-system-status' ) ) );
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

		$this->themes = $themes;

		// Flags
		$this->valid_response = true;
		$ret                  = false;

		// Check if the theme is on the list of purchased themes
		foreach ( $themes as $theme ) {
			if ( isset( $theme[ 'name' ] ) ) {
				if ( 'Uncode' === $theme[ 'name' ] ) {
					$license_details = array(
						'purchase_code'   => $theme[ 'purchase_code' ],
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
				case 403:
					$this->add_error( sprintf( esc_html__( 'Invalid token. Error code %d.', 'uncode' ), absint( $error->get_error_code() ) ) );
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
	 * Empty error message in DB.
	 */
	private function empty_error() {
		delete_option( $this->error_option_name );
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
	 * Save error message in DB.
	 */
	private function add_error( $message ) {
		update_option( $this->error_option_name, $message );
	}

	/**
	 * Get the item download.
	 */
	public function download( $id, $args = array() ) {
		if ( empty( $id ) ) {
			return false;
		}

		$url      = 'https://api.envato.com/v3/market/buyer/download?item_id=' . $id . '&shorten_url=true';
		$response = $this->envato_api()->request( $url, $args );

		if ( is_wp_error( $response ) || empty( $response ) || ! empty( $response[ 'error' ] ) ) {
			return false;
		}

		if ( ! empty( $response[ 'wordpress_theme' ] ) ) {
			return $response[ 'wordpress_theme' ];
		}

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
}

endif;

return new Uncode_Theme_Updater();
