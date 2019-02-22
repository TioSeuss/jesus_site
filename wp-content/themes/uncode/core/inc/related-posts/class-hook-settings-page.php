<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Uncode_RP4WP_Hook_Settings_Page extends RP4WP_Hook {
	protected $tag = 'admin_menu';

	/**
	 * Settings screen output
	 *
	 * @since  1.1.0
	 * @access public
	 */
	public static function screen() {
		global $wp_settings_sections, $wp_settings_fields;
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Related Posts for WordPress', 'uncode' ); ?></h2>

			<div class="rp4wp-content">
				<form method="post" action="options.php" id="rp4wp-settings-form">
					<?php
					//pass slug name of page, also referred  to in Settings API as option group name
					settings_fields( 'rp4wp' );

					if ( isset( $wp_settings_sections['rp4wp'] ) ) {

						echo '<h2 class="nav-tab-wrapper">';
						foreach ( (array) $wp_settings_sections['rp4wp'] as $section ) {
							//nav-tab-active
							echo '<a href="#rp4wp-settings-' . $section['id'] . '" class="nav-tab">' . $section['title'] . '</a>';
						}
						echo '</h2>' . PHP_EOL;
						?>
						<?php


						foreach ( (array) $wp_settings_sections['rp4wp'] as $section ) {

							echo '<div id="rp4wp-settings-' . $section['id'] . '" class="rp4wp-settings-section">';

							if ( $section['title'] ) {
								echo "<h3>{$section['title']}</h3>\n";
							}

							if ( $section['callback'] ) {
								call_user_func( $section['callback'], $section );
							}

							if ( isset( $wp_settings_fields ) && isset( $wp_settings_fields['rp4wp'] ) && isset( $wp_settings_fields['rp4wp'][ $section['id'] ] ) ) {
								echo '<table class="form-table">';
								do_settings_fields( 'rp4wp', $section['id'] );
								echo '</table>';

							}

							echo '</div>';
						}


					}

					// submit button
					submit_button();
					?>
				</form>
			</div>
		</div>
		<?php
	}
}
