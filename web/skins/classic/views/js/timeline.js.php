var filterQuery = '<?= validJsStr($filterQuery) ?>';

var monitorNames = new Hash();
<?php
foreach ( $monitors as $monitor )
{
    if ( !empty($monitorIds[$monitor['Id']]) )
    {
?>
monitorNames[<?= $monitor['Id'] ?>] = '<?= validJsStr($monitor['Name']) ?>';
<?php
    }
}
?>

var archivedString = "<?= $SLANG['Archived'] ?>";
