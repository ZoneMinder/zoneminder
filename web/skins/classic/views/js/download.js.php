<?php
if ( isset($_REQUEST['eids']) ) {
  $eidParms = array();
  foreach ( $_REQUEST['eids'] as $eid )
    $eidParms[] = 'eids[]='.validInt($eid);
?>
var eidParm = '<?php echo join('&', $eidParms) ?>';
<?php
} else if (isset($_REQUEST['eid'])) {
?>
var eidParm = 'eid=<?php echo validInt($_REQUEST['eid']) ?>';
<?php
}
?>

var exportReady = <?php echo !empty($_REQUEST['generated'])?'true':'false' ?>;
var exportFile = '?view=archive&type=<?php echo $exportFormat; ?>&connkey=<?php echo $connkey; ?>';
var connkey = '<?php echo $connkey ?>';

var exportProgressString = '<?php echo addslashes(translate('Exporting')) ?>';
