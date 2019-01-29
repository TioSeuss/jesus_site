<?php
if (!defined('ABSPATH'))
    exit;
image_hover_ultimate_user_capabilities();
wp_enqueue_script('iheu-vendor-bootstrap-jss', plugins_url('css-js/bootstrap.min.js', __FILE__));
wp_enqueue_style('iheu-vendor-bootstrap', plugins_url('css-js/bootstrap.min.css', __FILE__));
wp_enqueue_style('iheu-vendor-style', plugins_url('css-js/style.css', __FILE__));
$faversion = get_option('oxi_addons_font_awesome_version');
$faversion = explode('||', $faversion);
wp_enqueue_style('font-awesome-' . $faversion[0], $faversion[1]);
global $wpdb;
if (!empty($_REQUEST['_wpnonce'])) {
    $nonce = $_REQUEST['_wpnonce'];
}
if (!empty($_POST['delete']) && is_numeric($_POST['id'])) {
    if (!wp_verify_nonce($nonce, 'image_hover_ultimate_home_delete')) {
        die('You do not have sufficient permissions to access this page.');
    } else {
        global $wpdb;
        $id = (int) $_POST['id'];
        $table_name = $wpdb->prefix . 'image_hover_ultimate_style';
        $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id = %d", $id));
    }
}

if (!empty($_POST['submit']) && $_POST['submit'] == 'Clone' && is_numeric($_POST['image_hover_ultimate_id'])) {
    if (!wp_verify_nonce($nonce, 'image_hover_ultimate_home_clone')) {
        die('You do not have sufficient permissions to access this page.');
    } else {
        global $wpdb;
        $id = sanitize_text_field($_POST['image_hover_ultimate_id']);
        $table_name = $wpdb->prefix . 'image_hover_ultimate_style';
        $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d ", $id), ARRAY_A);
        $name = sanitize_text_field($_POST['style-name']);
        $style_name = $data['style_name'];
        $css = $data['css'];
        $wpdb->query($wpdb->prepare("INSERT INTO {$table_name} (name, style_name, css) VALUES ( %s, %s, %s )", array($name, $style_name, $css)));
        $redirect_id = $wpdb->insert_id;
        if ($redirect_id == 0) {
            $url = admin_url("admin.php?page=image-hover-ultimate-new");
        }
        if ($redirect_id != 0) {
            $url = admin_url("admin.php?page=image-hover-ultimate-new&styleid=$redirect_id");
        }
        echo '<script type="text/javascript"> document.location.href = "' . $url . '"; </script>';
        exit;
    }
}
$data = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'image_hover_ultimate_style ORDER BY id DESC', ARRAY_A);
?>
<div class="wrap">

    <?php echo iheu_promote_free(); ?>
    <div class="iheu-admin-row">            
        <h1> Image Hover Effects <a href="<?php echo admin_url("admin.php?page=image-hover-ultimate-new"); ?>" class="btn btn-primary"> Add New</a></h1>
        <div class="iheu-admin-wrapper table-responsive" style="margin-top: 20px; margin-bottom: 20px;">
            <?php
            if (count($data) == 0) {
                ?>
                <div class="iheb-style-settings-preview">
                    <div class="iheb-add-new-item-heading">
                        <a href="<?php echo admin_url("admin.php?page=image-hover-ultimate-new"); ?>">
                            <div class="iheb-add-new-item">
                                <span>
                                    <i class="fa fa-plus-circle"></i>
                                    Create Your First Hover Effects
                                </span>
                            </div>
                        </a>
                    </div>
                </div>

                <?php
            } else {
                ?>
                <table class="table table-hover widefat " style="background-color: #fff; border: 1px solid #ccc">
                    <thead>
                        <tr>
                            <th style="width: 11%">ID</th>
                            <th style="width: 10%">Name</th>
                            <th style="width: 13%">Template</th>
                            <th style="width: 42%">Shortcode</th>
                            <th style="width: 25%">Edit Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($data as $value) {
                            $id = $value['id'];
                            echo ' <tr>';
                            echo ' <td>' . $id . '</td>';
                            echo '  <td >' . $value['name'] . '</td>';
                            echo ' <td >' . str_replace("-", " ", $value['style_name']) . '</td>';
                            echo '<td ><span>Shortcode <input type="text" onclick="this.setSelectionRange(0, this.value.length)" value="[iheu_ultimate_oxi id=&quot;' . $id . '&quot;]"></span>'
                            . '<span>Php Code <input type="text" onclick="this.setSelectionRange(0, this.value.length)" value="&lt;?php echo do_shortcode(&#039;[iheu_ultimate_oxi  id=&quot;' . $id . '&quot;]&#039;); ?&gt;"></span></td>';
                            echo '<td >
                                   <button type="button" class="btn btn-success orphita-style-clone" title="Clone"  style="float:left" dataid="' . $id . '">Copy</button>
                                    <a href="' . admin_url("admin.php?page=image-hover-ultimate-new&styleid=$id") . '"  title="Edit"  class="btn btn-info" style="float:left; margin-right: 5px; margin-left: 5px;">Edit</a>
                                    <form method="post" class="orphita-style-delete">
                                            ' . wp_nonce_field("image_hover_ultimate_home_delete") . '
                                            <input type="hidden" name="id" value="' . $id . '">
                                            <button class="btn btn-danger" style="float:left"  title="Delete"  type="submit" value="delete" name="delete">Delete</button>  
                                    </form>
                                   
                             </td>';
                            echo ' </tr>';
                        }
                    }
                    ?>

                </tbody>
            </table>
        </div>
    </div>
</div>
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery(".orphita-style-clone").on("click", function () {
            var dataid = jQuery(this).attr("dataid");
            jQuery("#image_hover_ultimate_id").val(dataid);
            jQuery("#orphita-style-model").modal("show");
        });
        jQuery('.orphita-style-delete').submit(function () {
            var status = confirm("Do you Want to Delete?");
            if (status == false) {
                return false;
            } else {
                return true;
            }
        });
    });

</script> 
<div class="modal fade" id="orphita-style-model" >
    <form method="post">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Image Settings</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group row form-group-sm">
                        <label for="style" class="col-sm-6 col-form-label"  data-toggle="tooltip" class="tooltipLink" data-original-title="Give Your Template Name">Name</label>
                        <div class="col-sm-6 nopadding">
                            <input class="form-control" type="text" value="" id='style-name'  name="style-name">
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <input type="hidden" name="image_hover_ultimate_id" id="image_hover_ultimate_id" value="">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    <input type="submit" class="btn btn-primary" name="submit" value="Clone">
                    <?php wp_nonce_field("image_hover_ultimate_home_clone") ?>
                </div>
            </div>
        </div>
    </form>
</div>