<?php
global $monitors;
global $groups;
global $User;
global $inve;
?>
const monitors = new Array();
<?php
foreach ($monitors as $m) {
?>
monitors[monitors.length] = {
  'id': <?php echo $m->Id() ?>,
  'name': '<?php echo $m->Name() ?>'  
};
<?php
} // end foreach monitor
?>
const groups = new Array();
<?php
foreach ($groups as $g) {
?>
groups[groups.length] = {
  'id': <?php echo $g->Id() ?>,
  'monitor_ids': [<?php echo implode(',', $g->MonitorIds()) ?>]
};
<?php
} // end foreach group
?>
const userId = <?php echo $User->Id() ?>;
const permissionOptions = <?php echo json_encode($inve) ?>;

