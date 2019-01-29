<?php if ( !defined( 'ABSPATH' ) ) die( "Cannot access files directly." ); ?>

<?php include dirname(__FILE__)."/admin_header.tpl.php"; ?>
<tr valign="top">
<th scope="row"><?php _e("Maximum Results to Display", 'daves-wordpress-live-search'); ?></th>

<td><input type="text" name="daves-wordpress-live-search_max_results" id="daves-wordpress-live-search_max_results" value="<?php echo $maxResults; ?>" class="regular-text code" /><span class="setting-description"><?php _e("Enter &quot;0&quot; to display all matching results", 'daves-wordpress-live-search'); ?></span></td>
</tr>

<tr valign="top">
<th scope="row"><?php _e("Minimum characters before searching", 'daves-wordpress-live-search'); ?></th>

<td>
<select name="daves-wordpress-live-search_minchars">
<option value="1" <?php selected($minCharsToSearch, 1); ?>><?php _e("Search right away", 'daves-wordpress-live-search'); ?></option>
<option value="2" <?php selected($minCharsToSearch, 2); ?>><?php _e("Wait for two characters", 'daves-wordpress-live-search'); ?></option>
<option value="3" <?php selected($minCharsToSearch, 3); ?>><?php _e("Wait for three characters", 'daves-wordpress-live-search'); ?></option>
<option value="4" <?php selected($minCharsToSearch, 4); ?>><?php _e("Wait for four characters", 'daves-wordpress-live-search'); ?></option>
</select>
</td>
</tr>


<tr valign="top">
<th scope="row"><?php _e("Results Direction", 'daves-wordpress-live-search'); ?></th>

<td><input type="radio" name="daves-wordpress-live-search_results_direction" id="daves-wordpress-live-search_results_direction_down" value="down" <?php checked(empty($resultsDirection)); checked($resultsDirection, 'down'); ?> /> <label for="daves-wordpress-live-search_results_direction_down"><?php _e("Down", 'daves-wordpress-live-search'); ?></input></label>

<input type="radio" name="daves-wordpress-live-search_results_direction" id="daves-wordpress-live-search_results_direction_up" value="up" <?php checked($resultsDirection, 'up'); ?> /> <label for="daves-wordpress-live-search_results_direction_up"><?php _e("Up", 'daves-wordpress-live-search'); ?></label><br /><span class="setting-description"><?php _e("When search results are displayed, in which direction should the results box extend from the search box?", 'daves-wordpress-live-search'); ?></span></td>
</tr>

<!-- WP E-Commerce -->
<?php if(defined('WPSC_VERSION')) : ?>
<tr valign="top">
<td colspan="2"><h3><?php _e("WP E-Commerce", 'daves-wordpress-live-search'); ?></h3></td>
</tr>

<tr valign="top">
<th scope="row"> </th>
<td>
    <div><span class="setting-description"><?php printf(__("When used with the %sWP E-Commerce%s plugin, Dave&apos;s WordPress Live Search can search your product catalog instead of posts & pages.", 'daves-wordpress-live-search'), '<a href="http://getshopped.org/">', '</a>'); ?></span></div>
    <table>
        <tr><td><input type="radio" id="daves-wordpress-live-search_source_1" name="daves-wordpress-live-search_source" value="0" <?php checked($searchSource, 0); ?> /> <label for="daves-wordpress-live-search_source_1"><?php _e("Search posts &amp; pages", 'daves-wordpress-live-search'); ?></label></td></tr>
        <tr><td><input type="radio" id="daves-wordpress-live-search_source_2" name="daves-wordpress-live-search_source" value="1" <?php checked($searchSource, 1); ?> /> <label for="daves-wordpress-live-search_source_2"><?php _e("Search products", 'daves-wordpress-live-search'); ?></label></td></tr>
    </table>

</td>
</tr>
<?php else : ?>
<input type="hidden" name="daves-wordpress-live-search_source" value="0" />
<?php endif; ?>

<!-- Submit buttons -->
<tr valign="top">
<td colspan="2">
	<?php submit_button( NULL, 'option-tree-ui-button button button-secondary left', 'daves-wordpress-live-search_submit', false, array('id' => 'daves-wordpress-live-search_submit') ); ?>
</td>
</tr>

</tbody></table>

</form>

</div>