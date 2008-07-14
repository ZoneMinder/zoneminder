var filterQuery = '<?= addslashes($filterQuery) ?>';

var monitorNames = new Object();
<?php
foreach ( $monitors as $monitor )
{
    if ( !empty($monitorIds[$monitor['Id']]) )
    {
?>
monitorNames[<?= $monitor['Id'] ?>] = '<?= addslashes($monitor['Name']) ?>';
<?php
    }
}
?>

var archivedString = "<?= $SLANG['Archived'] ?>";
