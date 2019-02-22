<?php
/**
 * Single Product Share
 *
 * Sharing plugins can hook into here or you can add your own code directly.
 *
 * @package 	WooCommerce/Templates
 * @version     3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $metabox_data;

do_action( 'woocommerce_share' ); // Sharing plugins can hook into here

$page_show_share = (isset($metabox_data['_uncode_specific_share'][0])) ? $metabox_data['_uncode_specific_share'][0] : '';
if ($page_show_share === '') {
	$generic_show_share = ot_get_option('_uncode_product_share');
	$show_share = ($generic_show_share === 'off') ? false : true;
} else {
	$show_share = ($page_show_share === 'off') ? false : true;
}

if (!$show_share) {
	return;
}

?>
<hr />

<div class="detail-container">
	<span class="detail-label"><?php echo esc_html__('Share','uncode'); ?></span>
	<div class="share-button share-buttons share-inline only-icon"></div>
</div>
