<?php
// At the moment this can only return the list of available models given the manufacturer id

$message = '';

//
// INITIALIZE AND CHECK SANITY
//

if (!canView('Monitors')) {
  $message = 'Insufficient permissions to view model entries for user '.$user->Username();
} else if (!isset($_REQUEST['ManufacturerId'])) {
  $message = 'This request requires a ManufacturerId to be set';
}

if ($message) {
  ajaxError($message);
  return;
}

require_once('includes/Model.php');
$models = ZM\Model::find(array('ManufacturerId'=>$_REQUEST['ManufacturerId']), array('order'=>'lower(Name)'));
ajaxResponse(array('models'=>$models));
?>
