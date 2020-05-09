var filterQuery = '<?php echo validJsStr($filterQuery) ?>';

var monitorNames = new Object();
<?php
foreach ( $monitors as $monitor )
{
    if ( !empty($monitorIds[$monitor['Id']]) )
    {
?>
monitorNames[<?php echo $monitor['Id'] ?>] = '<?php echo validJsStr($monitor['Name']) ?>';
<?php
    }
}
?>

var archivedString = "<?php echo translate('Archived') ?>";
