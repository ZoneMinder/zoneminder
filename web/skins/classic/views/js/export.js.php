const exportReady = <?php echo !empty($_REQUEST['generated'])?'true':'false' ?>;
const exportFile = '<?php echo !empty($_REQUEST['exportFile'])?validJsStr($_REQUEST['exportFile']):'' ?>';
