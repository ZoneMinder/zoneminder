<?php
// Monitor control actions, require a monitor id and control permissions for that monitor
if (empty($_REQUEST['mid'])) {
  ZM\Warning('Settings requires a monitor id');
  return;
}
if (!canView('Control', $_REQUEST['mid'])) {
  ZM\Warning('Settings requires the Control permission');
  return;
}

require_once('includes/Monitor.php');
$mid = validInt($_REQUEST['mid']);
$args = ' -m ' . escapeshellarg($mid);
$data = ['controls'=>[]];
if (isset($_REQUEST['newBrightness'])) {
  $zmuCommand = getZmuCommand($args . ' -B' . escapeshellarg($_REQUEST['newBrightness']));
  $zmuOutput = exec($zmuCommand);
  $data['controls'][]=['control'=>'Brightness', 'value'=>$zmuOutput];
}
if (isset($_REQUEST['newContrast'])) {
  $zmuCommand = getZmuCommand($args . ' -C' . escapeshellarg($_REQUEST['newContrast']));
  $zmuOutput = exec($zmuCommand);
  $data['controls'][]=['control'=>'Contrast', 'value'=>$zmuOutput];
}
if (isset($_REQUEST['newHue'])) {
  $zmuCommand = getZmuCommand($args . ' -H' . escapeshellarg($_REQUEST['newHue']));
  $zmuOutput = exec($zmuCommand);
  $data['controls'][]=['control'=>'Hue', 'value'=>$zmuOutput];
  }
if (isset($_REQUEST['newColour'])) {
  $zmuCommand = getZmuCommand($args . ' -O' . escapeshellarg($_REQUEST['newColour']));
  $zmuOutput = exec($zmuCommand);
  $data['controls'][]=['control'=>'Colour', 'value'=>$zmuOutput];
}
ajaxResponse($data);
?>
