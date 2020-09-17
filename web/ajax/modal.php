<?php
// HOW TO IMPLEMENT A NEW MODAL
// 1) Create a function in skins/classic/includes/functions that returns the desired modal HTML
// 2) Add a new entry to the switch case below that calls the new HTML function
// 3) Create a $j.getJSON Ajax call in js with the right parameters to retrieve the modal
// 4) Open the modal with $j('#myModal').modal('show')
// 
// Should only report json
error_reporting(0);

if ( empty($_REQUEST['modal']) ) ajaxError('Modal Name Not Provided');

global $OLANG;
$modal = validJsStr($_REQUEST['modal']);
$data = array();

switch ( $modal ) {
  case 'optionhelp' :
    if ( empty($_REQUEST['ohndx']) ) ajaxError('Option Help Index Not Provided');
    $data['html'] = getOptionHelpHTML($_REQUEST['ohndx'], $OLANG);
    break;
  case 'enoperm' :
    $data['html'] = getENoPermHTML();
    break;
  case 'delconfirm' :
    $data['html'] = getDelConfirmHTML();
    break;
  case 'storage' :
    if ( !isset($_REQUEST['id']) ) ajaxError('Storage Id Not Provided');
    $data['html'] = getStorageModalHTML($_REQUEST['id']);
    break;
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
