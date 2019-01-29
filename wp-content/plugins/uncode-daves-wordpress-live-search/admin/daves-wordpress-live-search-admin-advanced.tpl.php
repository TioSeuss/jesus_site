<?php if ( !defined( 'ABSPATH' ) ) die( "Cannot access files directly." ); ?>

<?php include dirname( __FILE__ ) . "/admin_header.tpl.php"; ?>
<tr valign="top">
<th scope="row"><?php _e( "Exceptions", 'daves-wordpress-live-search' ); ?></th>

<td>
<?php $permalinkFormat = get_option( 'permalink_structure' ); ?>

<div><span class="setting-description"><?php printf( __( "Enter the %s of pages which should not have live searching, one per line. The * wildcard can be used at the start or end of a line. For example: %s", 'daves-wordpress-live-search' ), empty( $permalinkFormat ) ? __( 'paths', 'daves-wordpress-live-search' ) : __( 'permalinks' ), '<ul style="list-style-type:disc;margin-left: 3em;">' . empty( $permalinkFormat ) ? '<li>?page_id=123</li><li>page_id=1*</li>' : '<li>about</li><li>employee-*</li>' . '</ul>' ); ?>

<p><strong><?php _e( "NOTE", 'daves-wordpress-live-search' ); ?>:</strong> <?php _e( "These pages will still be returned in search results. This only disables the Live Search feature for the search box on these pages.", 'daves-wordpress-live-search' ); ?></p></span></div>
<textarea name="daves-wordpress-live-search_exceptions" id="daves-wordpress-live-search_exceptions" rows="5" cols="60"><?php echo $exceptions; ?></textarea></td>
</tr>

<!-- X Offset -->
<tr valign="top">
<th scope="row"><?php _e( "Search Results box X offset", 'daves-wordpress-live-search' ); ?></th>

<td>
<div><span class="setting-description"><?php _e( "Use this setting to move the search results box left or right to align exactly with your theme's search field. Value is in pixels. Negative values move the box to the left, positive values move it to the right.", 'daves-wordpress-live-search' ); ?></span></div>

<input type="text" name="daves-wordpress-live-search_xoffset" id="daves-wordpress-live-search_xoffset" value="<?php echo $xOffset; ?>"</td>
</tr>

<!-- Y Offset -->
<tr valign="top">
<th scope="row"><?php _e( "Search Results box Y offset", 'daves-wordpress-live-search' ); ?></th>

<td>
<div><span class="setting-description"><?php _e( "Use this setting to move the search results box up or down to align exactly with your theme's search field. Value is in pixels. Negative values move the box up, positive values move it down.", 'daves-wordpress-live-search' ); ?></span></div>

<input type="text" name="daves-wordpress-live-search_yoffset" id="daves-wordpress-live-search_yoffset" value="<?php echo $yOffset; ?>"</td>
</tr>

<!-- Apply the_content filter -->
<tr valign="top">
<th scope="row"><?php _e( "Enable content filter", 'daves-wordpress-live-search' ); ?></th>

<td><input type="checkbox" name="daves-wordpress-live-search_apply_content_filter" id="daves-wordpress-live-search_apply_content_filter" value="true" <?php checked( $applyContentFilter ); ?> /> <label for="daves-wordpress-live-search_apply_content_filter"><?php _e("Allow other plugins to filter the content before looking for a thumbnail. This will affect Live Search performance, so only enable this if you really need it.", 'daves-wordpress-live-search'); ?></label></td>
</tr>

<!-- Submit buttons -->
<tr valign="top">
<td colspan="2">
<?php submit_button( NULL, 'option-tree-ui-button button button-secondary left', 'daves-wordpress-live-search_submit', false, array( 'id' => 'daves-wordpress-live-search_submit' ) ); ?></td>
</tr>

</tbody></table>

</form>

</div>
