<?php

if ( empty($_REQUEST['modal']) ) {
  ajaxError('Modal Name Not Provided');
  return;
}

$modal = validJsStr($_REQUEST['modal']);
$data = array();

switch ( $modal ) {
  case 'server' :
    if ( !isset($_REQUEST['id']) ) ajaxError('Storage Id Not Provided');
    $data['html'] = getServerModalHTML($_REQUEST['id']);
    break;
  case 'eventdetail' :
    $eid = isset($_REQUEST['eid']) ? $_REQUEST['eid'] : '';
    $eids = isset($_REQUEST['eids']) ? $_REQUEST['eids'] : '';
    $data['html'] = getEventDetailHTML($eid, $eids);
    break;
  default :
    ZM\Logger::Debug("Including modals/$modal.php");
    # Shouldn't be necessary but at the moment we have last .conf file contents
    ob_start();
    @$result = include('modals/'.$modal.'.php');
    $data['html'] = ob_get_contents();
    ob_end_clean();
    if ( !$result ) {
      ajaxError("Unknown modal '".$modal."'");
      return;
    }
} # end switch $modal

ajaxResponse($data);
return;
?>
