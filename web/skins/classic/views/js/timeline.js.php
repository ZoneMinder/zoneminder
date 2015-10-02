var filterQuery = '<?php echo validJsStr($filterQuery) ?>';

var monitorNames = {};
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
var monitors = <?php echo json_encode($monitors) ?>;

var archivedString = "<?php echo translate('Archived') ?>";
