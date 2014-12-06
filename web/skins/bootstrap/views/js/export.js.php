<?php
if ( isset($_REQUEST['eids']) )
{
    $eidParms = array();
    foreach ( $_REQUEST['eids'] as $eid )
        $eidParms[] = "eids[]=".validInt($eid);
?>
var eidParm = '<?= join( '&', $eidParms ) ?>';
<?php
}
else
{
?>
var eidParm = 'eid=<?= validInt($_REQUEST['eid']) ?>';
<?php
}
?>

var exportReady = <?= !empty($_REQUEST['generated'])?'true':'false' ?>;
var exportFile = '<?= !empty($_REQUEST['exportFile'])?validJsStr($_REQUEST['exportFile']):'' ?>';

var exportProgressString = '<?= addslashes($SLANG['Exporting']) ?>';
