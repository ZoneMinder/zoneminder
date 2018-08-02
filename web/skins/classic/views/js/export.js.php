var exportReady = <?php echo !empty($_REQUEST['generated'])?'true':'false' ?>;
var exportFile = '<?php echo !empty($_REQUEST['exportFile'])?validJsStr($_REQUEST['exportFile']):'' ?>';
var exportProgressString = '<?php echo addslashes(translate('Exporting')) ?>';
