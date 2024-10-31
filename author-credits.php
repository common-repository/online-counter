<?php $getitnow = checked('1',get_option('oscimp_dbhost')); ?> </div>
<?php if (strstr($getitnow, "checked='checked'")) {
wp_redirect('../wp-admin/options-general.php');
 } else {
echo '<h1>Final Step!</h1>'; 
echo '<br/>'; 
echo '<p>To continue to the settings of the plugin please choose `Yes` or No to the question below.</p>'; 
echo '<small>Enable author credits and help our team keep this plugin for free, click Yes to enable it and continue to the Web Stats plugin settings or No to exit.</small>'; 
echo '<br/>';
echo '<p><small><a href="../wp-admin/admin.php?page=online-counter/admin/luc_admin.php">No i do not agree</a></small></p>';
echo '<p><a href="../wp-content/plugins/online-counter/agree.php">Yes i agree, continue to plugin settings.</a></p>';
} 
?>