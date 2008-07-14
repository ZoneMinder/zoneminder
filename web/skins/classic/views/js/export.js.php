<?php
if ( isset($_REQUEST['eids']) )
{
?>
var eidParm = 'eids[]=<?= join( '&eids[]=', $_REQUEST['eids'] ) ?>';
<?php
}
else
{
?>
var eidParm = 'eid=<?= $_REQUEST['eid'] ?>';
<?php
}
?>

var exportReady = <?= !empty($_REQUEST['generated'])?'true':'false' ?>;
var exportFile = '<?= !empty($_REQUEST['exportFile'])?$_REQUEST['exportFile']:'' ?>';

var exportProgressString = '<?= addslashes($SLANG['Exporting']) ?>';
