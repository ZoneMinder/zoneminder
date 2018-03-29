var filterQuery = '<?php echo validJsStr($filterQuery) ?>';

<?php
$jsMonitors = array();

$fields = array('Name', 'LabelFormat', 'SaveJPEGs', 'VideoWriter');
foreach ( $monitors as $monitor ) {
  if ( !empty($monitorIds[$monitor['Id']]) ) {
    $jsMonitor = array();
    foreach ($fields as $field) {
      $jsMonitor[$field] = $monitor[$field];
    }
    $jsMonitors[$monitor['Id']] = $jsMonitor;
  }
}
?>
var monitors = <?php echo json_encode($jsMonitors) ?>;

var archivedString = "<?php echo translate('Archived') ?>";
