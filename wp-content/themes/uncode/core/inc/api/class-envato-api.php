<?php
/**
 * Envato API Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Uncode_Envato_API' ) ) :

/**
 * Uncode_Envato_API Class
 */
class Uncode_Envato_API {

	/**
	 * The single class instance.
	 */
	private static $_instance = null;

	/**
	 * The Envato API personal token.
	 */
	private $token;

	/**
	 * Main Envato_Market_API Instance
	 *
	 * Ensures only one instance of this class exists in memory at any one time.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * A dummy constructor to prevent this class from being loaded more than once.
	 *
	 * @see Uncode_Envato_API::instance()
	 */
	private function __construct() {
		/* We do nothing here! */
	}

	/**
	 * You cannot clone this class.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'uncode' ), '1.0.0' );
	}

	/**
	 * You cannot unserialize instances of this class.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'uncode' ), '1.0.0' );
	}

	/**
	 * Sets the token.
	 */
	public function set_token( $token ) {
		$this->token = $token;
	}

	/**
	 * Query the Envato API.
	 */
	public function request( $url, $args = array() ) {
		$defaults = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->token,
				'User-Agent'    => 'WordPress - Uncode',
			),
			'timeout' => 20,
		);
		$args = wp_parse_args( $args, $defaults );

		$token = trim( str_replace( 'Bearer', '', $args[ 'headers' ][ 'Authorization' ] ) );

		if ( empty( $token ) ) {
			return new WP_Error( 'api_token_error', esc_html__( 'An API token is required.', 'uncode' ) );
		}

		// Make an API request.
		$response = wp_remote_get( esc_url_raw( $url ), $args );

		// Check the response code.
		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );

		if ( 200 !== $response_code && ! empty( $response_message ) ) {
			return new WP_Error( $response_code, $response_message );
		} elseif ( 200 !== $response_code ) {
			return new WP_Error( $response_code, esc_html__( 'An unknown API error occurred.', 'uncode' ) );
		} else {
			$return = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( null === $return ) {
				return new WP_Error( 'api_error', esc_html__( 'An unknown API error occurred.', 'uncode' ) );
			}

			return $return;
		}
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
	 * Get the item download.
	 */
	public function download( $id, $args = array() ) {
		if ( empty( $id ) ) {
			return false;
		}

		$url      = 'https://api.envato.com/v3/market/buyer/download?item_id=' . $id . '&shorten_url=true';
		$response = $this->request( $url, $args );

		// @todo Find out which errors could be returned & handle them in the UI.
		if ( is_wp_error( $response ) || empty( $response ) || ! empty( $response['error'] ) ) {
			return false;
		}

		if ( ! empty( $response[ 'wordpress_theme' ] ) ) {
			return $response[ 'wordpress_theme' ];
		}

		if ( ! empty( $response[ 'wordpress_plugin' ] ) ) {
			return $response[ 'wordpress_plugin' ];
		}

		return false;
	}

	/**
	 * Get an item by ID and type.
	 */
	public function item( $id, $args = array() ) {
		$url      = 'https://api.envato.com/v3/market/catalog/item?id=' . $id;
		$response = $this->request( $url, $args );

		if ( is_wp_error( $response ) || empty( $response ) ) {
			return false;
		}

		if ( ! empty( $response[ 'wordpress_theme_metadata' ] ) ) {
			return $this->normalize_theme( $response );
		}

		if ( ! empty( $response[ 'wordpress_plugin_metadata' ] ) ) {
			return $this->normalize_plugin( $response );
		}

		return false;
	}

	/**
	 * Get the list of available themes.
	 */
	public function themes( $args = array(), $page = '' ) {
		$themes = array();

		$url  = 'https://api.envato.com/v3/market/buyer/list-purchases?filter_by=wordpress-themes';
		$url .= ( $page ) ? '&page=' . $page : '';

		$response = $this->request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response ) || empty( $response['results'] ) ) {
			return $themes;
		}

		foreach ( $response[ 'results' ] as $theme ) {
			$themes[] = $this->normalize_theme( $theme );
		}

		return $themes;
	}

	/**
	 * Normalize a theme.
	 */
	public function normalize_theme( $theme ) {
		$item = $theme[ 'item' ];

		return array(
			'id'              => $item[ 'id' ],
			'purchase_code'   => $theme[ 'code' ],
			'name'            => ( ! empty( $item[ 'wordpress_theme_metadata' ][ 'theme_name' ] ) ? $item[ 'wordpress_theme_metadata' ][ 'theme_name' ] : '' ),
			'author'          => ( ! empty( $item[ 'wordpress_theme_metadata' ][ 'author_name' ] ) ? $item[ 'wordpress_theme_metadata' ][ 'author_name' ] : '' ),
			'version'         => ( ! empty( $item[ 'wordpress_theme_metadata' ][ 'version' ] ) ? $item[ 'wordpress_theme_metadata' ][ 'version' ] : '' ),
			'description'     => self::remove_non_unicode( $item[ 'wordpress_theme_metadata' ][ 'description' ] ),
			'url'             => ( ! empty( $item[ 'url' ] ) ? $item[ 'url' ] : '' ),
			'author_url'      => ( ! empty( $item[ 'author_url' ] ) ? $item[ 'author_url' ] : '' ),
			'thumbnail_url'   => ( ! empty( $item[ 'thumbnail_url' ] ) ? $item[ 'thumbnail_url' ] : '' ),
			'rating'          => ( ! empty( $item[ 'rating' ] ) ? $item[ 'rating' ] : '' ),
		);
	}

	/**
	 * Get the list of available plugins.
	 */
	public function plugins( $args = array(), $page = '' ) {
		$plugins = array();

		$url  = 'https://api.envato.com/v3/market/buyer/list-purchases?filter_by=wordpress-plugins';
		$url .= ( $page ) ? '&page=' . $page : '';

		$response = $this->request( $url, $args );

		if ( is_wp_error( $response ) || empty( $response ) || empty( $response['results'] ) ) {
			return $plugins;
		}

		foreach ( $response[ 'results' ] as $plugin ) {
			$plugins[] = $this->normalize_plugin( $plugin[ 'item' ] );
		}

		return $plugins;
	}

	/**
	 * Normalize a plugin.
	 */
	public function normalize_plugin( $plugin ) {
		$requires = null;
		$tested   = null;
		$versions = array();

		// Set the required and tested WordPress version numbers.
		foreach ( $plugin[ 'attributes' ] as $k => $v ) {
			if ( 'compatible-software' === $v[ 'name' ] ) {
				foreach ( $v[ 'value'] as $version ) {
					$versions[] = str_replace( 'WordPress ', '', trim( $version ) );
				}

				if ( ! empty( $versions ) ) {
					$requires = $versions[ count( $versions ) - 1 ];
					$tested   = $versions[ 0 ];
				}

				break;
			}
		}

		return array(
			'id'              => $plugin[ 'id' ],
			'name'            => ( ! empty( $plugin[ 'wordpress_plugin_metadata' ][ 'plugin_name' ] ) ? $plugin[ 'wordpress_plugin_metadata' ][ 'plugin_name' ] : '' ),
			'author'          => ( ! empty( $plugin[ 'wordpress_plugin_metadata' ][ 'author' ] ) ? $plugin[ 'wordpress_plugin_metadata' ][ 'author' ] : '' ),
			'version'         => ( ! empty( $plugin[ 'wordpress_plugin_metadata' ][ 'version' ] ) ? $plugin[ 'wordpress_plugin_metadata' ][ 'version' ] : '' ),
			'description'     => self::remove_non_unicode( $plugin[ 'wordpress_plugin_metadata' ][ 'description' ] ),
			'url'             => ( ! empty( $plugin[ 'url' ] ) ? $plugin[ 'url' ] : '' ),
			'author_url'      => ( ! empty( $plugin[ 'author_url' ] ) ? $plugin[ 'author_url' ] : '' ),
			'thumbnail_url'   => ( ! empty( $plugin[ 'thumbnail_url' ] ) ? $plugin[ 'thumbnail_url' ] : '' ),
			'landscape_url'   => ( ! empty( $plugin[ 'previews' ][ 'landscape_preview' ][ 'landscape_url' ] ) ? $plugin[ 'previews' ][ 'landscape_preview' ][ 'landscape_url' ] : '' ),
			'requires'        => $requires,
			'tested'          => $tested,
			'number_of_sales' => ( ! empty( $plugin[ 'number_of_sales' ] ) ? $plugin[ 'number_of_sales' ] : '' ),
			'updated_at'      => ( ! empty( $plugin[ 'updated_at' ] ) ? $plugin[ 'updated_at' ] : '' ),
			'rating'          => ( ! empty( $plugin[ 'rating' ] ) ? $plugin[ 'rating' ] : '' ),
		);
	}

	/**
	 * Remove all non unicode characters in a string
	 */
	static private function remove_non_unicode( $retval ) {
		return preg_replace( '/[\x00-\x1F\x80-\xFF]/', '', $retval );
	}
}

endif;
