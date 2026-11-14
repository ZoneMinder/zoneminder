<?php
global $monitors;
global $groups;
?>
const monitors = new Array();
<?php
foreach ($monitors as $m) {
?>
monitors[monitors.length] = {
  'id': <?php echo $m->Id() ?>,
  'name': '<?php echo validJsStr($m->Name()) ?>'  
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
<?php
global $User;
$role = $User->Role();
$role_data = null;
if ($role) {
  $role_data = array('monitors'=>$role->Monitors(), 'monitor_permissions'=>new stdClass(), 'group_permissions'=>new stdClass());
  foreach ($role->Monitor_Permissions() as $p) $role_data['monitor_permissions']->{$p->MonitorId()} = $p->Permission();
  foreach ($role->Group_Permissions() as $p) $role_data['group_permissions']->{$p->GroupId()} = $p->Permission();
}
?>
const role = <?php echo json_encode($role_data) ?>;
