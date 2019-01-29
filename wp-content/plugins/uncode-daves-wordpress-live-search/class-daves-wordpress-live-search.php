<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cannot access files directly.' );
}

class DavesWordPressLiveSearch {

	///////////////////
	// Initialization
	///////////////////

	/**
	 * Initialize the live search object & enqueuing scripts
	 *
	 * @return void
	 */
	public static function advanced_search_init() {

		load_plugin_textdomain( 'daves-wordpress-live-search', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		if ( self::isSearchablePage() ) {
			wp_enqueue_script( 'underscore' );
			wp_enqueue_script( 'daves-wordpress-live-search', plugin_dir_url( __FILE__ ) . 'js/daves-wordpress-live-search.js', array(
					'jquery',
					'underscore'
				), false, true );
			self::inlineSettings();
		}
	}

	public static function head() {

		$cssOption = get_option( 'daves-wordpress-live-search_css_option' );
		$themeDir  = get_bloginfo( "stylesheet_directory" );

		if ( is_admin() ) {
			$style = plugin_dir_url( __FILE__ ) . 'css/daves-wordpress-live-search_custom.css';
			if ( $style ) {
				wp_register_style( 'daves-wordpress-live-search', $style );
				wp_enqueue_style( 'daves-wordpress-live-search' );
			}
		} elseif ( self::isSearchablePage() ) {
			switch ( $cssOption ) {
				case 'theme':
					$style = $themeDir . '/daves-wordpress-live-search.css';
					break;
				case 'default_red':
				case 'default_blue':
				case 'default_gray':
				case 'custom':
					$style = plugin_dir_url( __FILE__ ) . 'css/daves-wordpress-live-search_' . $cssOption . '.css';
					break;
				case 'notheme':
				default:
					$style = false;
			}

			if ( $style ) {
				wp_register_style( 'daves-wordpress-live-search', $style );
				wp_enqueue_style( 'daves-wordpress-live-search' );
				wp_print_styles();
			}

			if ( $cssOption === 'custom' && ! is_admin() ) {
				$customOptions = get_option( 'daves-wordpress-live-search_custom_options' );

				// Default width if none was provided
				if ( ! isset( $customOptions['width'] ) || empty( $customOptions['width'] ) ) {
					$customOptions['width'] = 250;
				}

				$styleTag = <<<STYLE
            ul.dwls_search_results {
              width: {$customOptions['width']}px;
            }
            ul.dwls_search_results li {
              color: {$customOptions['fg']};
              background-color: {$customOptions['bg']};
            }
            .search_footer {
              background-color: {$customOptions['footbg']};
            }
            .search_footer a,
            .search_footer a:visited {
              color: {$customOptions['footfg']};
            }
            ul.dwls_search_results li a, ul.dwls_search_results li a:visited {
              color: {$customOptions['title']};
            }
            ul.dwls_search_results li:hover
            {
              background-color: {$customOptions['hoverbg']};
            }
            ul.dwls_search_results li {
              border-bottom: 1px solid {$customOptions['divider']};
            }
STYLE;
				if ( ! empty( $customOptions['shadow'] ) ) {
					$styleTag .= <<<STYLE
            ul.dwls_search_results {
              -moz-box-shadow: 5px 5px 3px #222;
              -webkit-box-shadow: 5px 5px 3px #222;
              box-shadow: 5px 5px 3px #222;
            }
STYLE;
				}
				echo '<style type="text/css">' . $styleTag . '</style>';
			}
		}

	}

	/**
	 * Working around the fact either the $_POST parameters are unreliable (may be true or "true")
	 * or I just can't keep them straight. Probably the latter, but best to be safe.
	 *
	 * @param mixed $value value to be tested for truthiness
	 *
	 * @return boolean truthiness
	 */
	private static function isTruthy( $value ) {

		if ( true === $value ) {
			return true;
		} // Check for boolean true
		if ( is_numeric( $value ) && 0 !== $value ) {
			return true;
		} // Check for nonzero
		if ( 'true' === $value ) {
			return true;
		} // Check for the word 'true'

		return false;

	}

	private static function inlineSettings() {

		$resultsDirection    = stripslashes( get_option( 'daves-wordpress-live-search_results_direction' ) );
		$showThumbs          = false; //( true === self::isTruthy( get_option( 'daves-wordpress-live-search_thumbs' ) ) );
		$displayPostCategory = ( true === self::isTruthy( get_option( 'daves-wordpress-live-search_display_post_category' ) ) );
		$showExcerpt         = ( true === self::isTruthy( get_option( 'daves-wordpress-live-search_excerpt' ) ) );
		$showMoreResultsLink = ( true === self::isTruthy( get_option( 'daves-wordpress-live-search_more_results', true ) ) );
		$activateWidget = ( true === self::isTruthy( get_option( 'daves-wordpress-live-search_uncode_activate_widget', true ) ) );//Edit by Uncode
		$minCharsToSearch    = intval( get_option( 'daves-wordpress-live-search_minchars' ) );
		$xOffset             = intval( get_option( 'daves-wordpress-live-search_xoffset' ) );
		$yOffset             = intval( get_option( 'daves-wordpress-live-search_yoffset' ) );
		$resultTemplate      = apply_filters( 'dwls_alter_result_template', file_get_contents( dirname( __FILE__ ) . "/js/dwls-results.tpl" ) );

		// Translations
		$moreResultsText    = __( 'View more results', 'daves-wordpress-live-search' );
		$outdatedJQueryText = __( "Dave's WordPress Live Search requires jQuery 1.2.6 or higher. WordPress ships with current jQuery versions. But if you are seeing this message, it's likely that another plugin is including an earlier version.", 'daves-wordpress-live-search' );

		// Neat trick: use wp_localize_script to generate the config object
		// "This way, you won’t have to use PHP to print out JavaScript code,
		// which is both ugly and non-cacheable."
		// @see http://www.garyc40.com/2010/03/5-tips-for-using-ajax-in-wordpress/#js-global
		wp_localize_script( 'daves-wordpress-live-search', 'DavesWordPressLiveSearchConfig', array(
			'resultsDirection'    => $resultsDirection,
			'showThumbs'          => 'false', //( $showThumbs ) ? 'true' : 'false',
			'showExcerpt'         => ( $showExcerpt == 1 ) ? 'true' : 'false',
			'displayPostCategory' => ( $displayPostCategory == 1 ) ? 'true' : 'false',
			'showMoreResultsLink' => ( $showMoreResultsLink ) ? 'true' : 'false',
			'activateWidgetLink' => ( $activateWidget ) ? 'true' : 'false',
			'minCharsToSearch'    => $minCharsToSearch,
			'xOffset'             => $xOffset,
			'yOffset'             => $yOffset,
			'blogURL'             => get_bloginfo( 'url' ),
			'ajaxURL'             => admin_url( 'admin-ajax.php' ),
			'viewMoreText'        => $moreResultsText,
			'outdatedJQuery'      => $outdatedJQueryText,
			'resultTemplate'      => $resultTemplate,
		) );

	}

	///////////////
	// Admin Pages
	///////////////

	/**
	 * Include the Live Search options page in the admin menu
	 *
	 * @return void
	 */
	public static function admin_menu() {

		add_submenu_page( 'uncode-system-status', 'UNCODE', esc_html__( 'Live Search', 'daves-wordpress-live-search' ), 'manage_options', __FILE__, array(
			'DavesWordPressLiveSearch',
			'plugin_options'
		), 'uncode-system-status', 'uncode_welcome_page' );

	}

	/**
	 * Display & process the Live Search admin options
	 *
	 * @return void
	 */
	public static function plugin_options() {

		$tab = isset( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : '';

		switch ( $tab ) {
			case 'advanced':
				return self::plugin_options_advanced();
			case 'appearance':
				return self::plugin_options_design();
			case 'settings':
			default:
				return self::plugin_options_settings();
		}

	}

	private static function plugin_options_settings() {

		$thisPluginsDirectory = dirname( __FILE__ );

		if ( array_key_exists( 'daves-wordpress-live-search_submit', $_POST ) && current_user_can( 'manage_options' ) ) {
			check_admin_referer( 'daves-wordpress-live-search-config' );

			// Read their posted value
			$maxResults       = max( intval( $_POST['daves-wordpress-live-search_max_results'] ), 0 );
			$resultsDirection = $_POST['daves-wordpress-live-search_results_direction'];
			$minCharsToSearch = intval( $_POST['daves-wordpress-live-search_minchars'] );
			$searchSource     = intval( $_POST['daves-wordpress-live-search_source'] );

			// Save the posted value in the database
			update_option( 'daves-wordpress-live-search_max_results', $maxResults );
			update_option( 'daves-wordpress-live-search_results_direction', $resultsDirection );
			update_option( 'daves-wordpress-live-search_minchars', $minCharsToSearch );
			update_option( 'daves-wordpress-live-search_source', $searchSource );

			$updateMessage = __( 'Options saved.', 'daves-wordpress-live-search' );
			echo "<div class=\"updated fade\"><p><strong>$updateMessage</strong></p></div>";
		} else {
			$maxResults       = intval( get_option( 'daves-wordpress-live-search_max_results' ) );
			$resultsDirection = stripslashes( get_option( 'daves-wordpress-live-search_results_direction' ) );
			$minCharsToSearch = intval( get_option( 'daves-wordpress-live-search_minchars' ) );
			$searchSource     = intval( get_option( 'daves-wordpress-live-search_source' ) );
		}

		$wp_pre_4_3 = version_compare( $GLOBALS['wp_version'], '4.3', 'lt' );

		include "$thisPluginsDirectory/admin/daves-wordpress-live-search-admin.tpl.php";

	}

	private static function plugin_options_design() {

		$thisPluginsDirectory = dirname( __FILE__ );

		if ( array_key_exists( 'daves-wordpress-live-search_submit', $_POST ) && current_user_can( 'manage_options' ) ) {
			check_admin_referer( 'daves-wordpress-live-search-config' );

			// Read their posted value
			$displayPostMeta     = ( isset($_POST['daves-wordpress-live-search_display_post_meta']) && 'true' === $_POST['daves-wordpress-live-search_display_post_meta'] );
			$showThumbs          = ( isset($_POST['daves-wordpress-live-search_thumbs']) && 'true' === $_POST['daves-wordpress-live-search_thumbs'] );
			$showExcerpt         = ( isset($_POST['daves-wordpress-live-search_excerpt']) && 'true' === $_POST['daves-wordpress-live-search_excerpt'] );
			$displayPostCategory = ( isset($_POST['daves-wordpress-live-search_display_post_category']) && 'true' === $_POST['daves-wordpress-live-search_display_post_category'] );
			$excerptLength       = $_POST['daves-wordpress-live-search_excerpt_length'];
			$showMoreResultsLink = ( isset($_POST['daves-wordpress-live-search_more_results']) && 'true' === $_POST['daves-wordpress-live-search_more_results'] );
			$activateWidget = ( isset($_POST['daves-wordpress-live-search_uncode_activate_widget']) && 'true' === $_POST['daves-wordpress-live-search_uncode_activate_widget'] );

			$cssOption     = $_POST['daves-wordpress-live-search_css'];
			$customOptions = $_POST['daves-wordpress-live-search_custom'];

			// Force the width to be something we can work with
			$customOptions['width'] = max( abs( intval( $customOptions['width'] ) ), 1 );

			// Save the posted value in the database
			update_option( 'daves-wordpress-live-search_display_post_meta', (string) $displayPostMeta );
			update_option( 'daves-wordpress-live-search_thumbs', $showThumbs );
			update_option( 'daves-wordpress-live-search_excerpt', $showExcerpt );
			update_option( 'daves-wordpress-live-search_display_post_category', $displayPostCategory );
			update_option( 'daves-wordpress-live-search_excerpt_length', $excerptLength );
			update_option( 'daves-wordpress-live-search_more_results', $showMoreResultsLink );
			update_option( 'daves-wordpress-live-search_uncode_activate_widget', $activateWidget );
			update_option( 'daves-wordpress-live-search_css_option', $cssOption );
			update_option( 'daves-wordpress-live-search_custom_options', $customOptions );

			// Translate the "Options saved" message...just in case.
			// You know...the code I was copying for this does it, thought it might be a good idea to leave it
			$updateMessage = __( 'Options saved.', 'daves-wordpress-live-search' );

			echo "<div class=\"updated fade\"><p><strong>$updateMessage</strong></p></div>";
		} else {
			$displayPostMeta     = (bool) get_option( 'daves-wordpress-live-search_display_post_meta' );
			$displayPostCategory     = (bool) get_option( 'daves-wordpress-live-search_display_post_meta' );
			$showThumbs          = (bool) get_option( 'daves-wordpress-live-search_thumbs' );
			$showExcerpt         = (bool) get_option( 'daves-wordpress-live-search_excerpt' );
			$excerptLength       = intval( get_option( 'daves-wordpress-live-search_excerpt_length' ) );
			$showMoreResultsLink = (bool) get_option( 'daves-wordpress-live-search_more_results' );
			$activateWidget = (bool) get_option( 'daves-wordpress-live-search_uncode_activate_widget' );
			$cssOption           = get_option( 'daves-wordpress-live-search_css_option' );
			$customOptions       = get_option( 'daves-wordpress-live-search_custom_options' );
		}

		$wp_pre_4_3 = version_compare( $GLOBALS['wp_version'], '4.3', 'lt' );

		include "$thisPluginsDirectory/admin/daves-wordpress-live-search-admin-appearance.tpl.php";

	}

	private static function plugin_options_advanced() {

		$thisPluginsDirectory = dirname( __FILE__ );

		if ( array_key_exists( 'daves-wordpress-live-search_submit', $_POST ) && current_user_can( 'manage_options' ) ) {
			check_admin_referer( 'daves-wordpress-live-search-config' );

			// Read their posted value
			$xOffset            = intval( $_POST['daves-wordpress-live-search_xoffset'] );
			$yOffset            = intval( $_POST['daves-wordpress-live-search_yoffset'] );
			$exceptions         = $_POST['daves-wordpress-live-search_exceptions'];
			$applyContentFilter = ( isset( $_POST['daves-wordpress-live-search_apply_content_filter'] ) && "true" == $_POST['daves-wordpress-live-search_apply_content_filter'] );

			update_option( 'daves-wordpress-live-search_exceptions', $exceptions );
			update_option( 'daves-wordpress-live-search_xoffset', intval( $xOffset ) );
			update_option( 'daves-wordpress-live-search_yoffset', intval( $yOffset ) );
			update_option( 'daves-wordpress-live-search_apply_content_filter', $applyContentFilter );

			// Translate the "Options saved" message...just in case.
			// You know...the code I was copying for this does it, thought it might be a good idea to leave it
			$updateMessage = __( 'Options saved.', 'daves-wordpress-live-search' );

			echo "<div class=\"updated fade\"><p><strong>$updateMessage</strong></p></div>";
		} else {

			$exceptions         = get_option( 'daves-wordpress-live-search_exceptions' );
			$xOffset            = intval( get_option( 'daves-wordpress-live-search_xoffset' ) );
			$yOffset            = intval( get_option( 'daves-wordpress-live-search_yoffset' ) );
			$applyContentFilter = (bool) get_option( 'daves-wordpress-live-search_apply_content_filter' );

		}

		$wp_pre_4_3 = version_compare( $GLOBALS['wp_version'], '4.3', 'lt' );

		include "$thisPluginsDirectory/admin/daves-wordpress-live-search-admin-advanced.tpl.php";

	}

	public static function admin_notices() {

		$cssOption = get_option( 'daves-wordpress-live-search_css_option' );

		if ( 'theme' == $cssOption ) {
			$themeDir = get_theme_root() . '/' . get_stylesheet();

			// Make sure there's a daves-wordpress-live-search.css file in the theme
			if ( ! file_exists( $themeDir . "/daves-wordpress-live-search.css" ) ) {
				$alertMessage = sprintf( __( "The %sDave's WordPress Live Search%s plugin is configured to use a theme-specific CSS file, but the current theme does not contain a daves-wordpress-live-search.css file." ), '<em>', '</em>' );
				echo "<div class=\"updated fade\"><p><strong>$alertMessage</strong></p></div>";
			}
		}

	}

	/**
	 * Check if the current page is the login or registration page
	 *
	 * @return bool
	 */
	private static function is_login_page() {
		return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) );
	}

	private static function isSearchablePage() {

		if ( is_admin() || self::is_login_page() ) {
			return false;
		}

		$searchable = true;
		$exceptions = explode( "\n", get_option( 'daves-wordpress-live-search_exceptions' ) );

		foreach ( $exceptions as $exception ) {
			$regexp = trim( $exception );

			// Blank paths were slipping through. Ignore them.
			if ( empty( $regexp ) ) {
				continue;
			}

			if ( '<front>' == $regexp ) {
				$regexp = '';
			}

			$regexp = str_replace( '?', '[?]', $regexp );
			$regexp = str_replace( '|', '[|]', $regexp );

			// These checks can probably be turned into regexps themselves,
			// but it's too early in the morning to be writing regexps
			if ( '*' == substr( $regexp, 0, 1 ) ) {
				$regexp = substr( $regexp, 1 );
			} else {
				$regexp = '^' . $regexp;
			}

			if ( '*' == substr( $regexp, - 1 ) ) {
				$regexp = substr( $regexp, 0, - 1 );
			} else {
				$regexp = $regexp . '$';
			}

			$regexp = '|' . $regexp . '|';

			if ( preg_match( $regexp, substr( $_SERVER['REQUEST_URI'], 1 ) ) > 0 ) {
				return false;
			}
		}

		// Fall-through, search everything by default
		return true;

	}

	/**
	 * Set some decent defaults
	 */
	public static function activate() {

		add_option( 'daves-wordpress-live-search_max_results', 10 );
		add_option( 'daves-wordpress-live-search_results_direction', 'down' );
		add_option( 'daves-wordpress-live-search_display_post_meta', 'true' );
		add_option( 'daves-wordpress-live-search_css_option', 'default_gray' );
		add_option( 'daves-wordpress-live-search_thumbs', 'true' );
		add_option( 'daves-wordpress-live-search_excerpt', 'true' );
		add_option( 'daves-wordpress-live-search_excerpt_length', 100 );
		add_option( 'daves-wordpress-live-search_more_results', 'true' );
		add_option( 'daves-wordpress-live-search_uncode_activate_widget', 'true' );
		add_option( 'daves-wordpress-live-search_minchars', 3 );
		add_option( 'daves-wordpress-live-search_source', DavesWordPressLiveSearchResults::SEARCH_CONTENT );

		add_option( 'daves-wordpress-live-search_custom_options', array(
			'fg'      => '#000',
			'bg'      => '#ddd',
			'hoverbg' => '#fff',
			'title'   => '#000',
			'footbg'  => '#888',
			'footfg'  => '#fff',
			'width'   => '250',
		) );

	}

}
