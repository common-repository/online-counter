<?php
$content = "Author Credits is activated, to deactivate please visit plugin options.";
$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/authorcredit-active.txt","wb");
fwrite($fp,$content);
fclose($fp);
?>
<meta http-equiv="refresh" content="1; url=/wp-admin/admin.php?page=online-counter/admin/luc_admin.php">