<?php
// HOW TO IMPLEMENT A NEW MODAL
// 1) Create a function in skins/classic/includes/functions that returns the desired modal HTML
// 2) Add a new entry to the switch case below that calls the new HTML function
// 3) Create a $j.getJSON Ajax call in js with the right parameters to retrieve the modal
// 4) Open the modal with $j('#myModal').modal('show')
// 

if ( empty($_REQUEST['modal']) ) ajaxError('Modal Name Not Provided');

global $OLANG;
$modal = validJsStr($_REQUEST['modal']);
$data = array();

// Not sure if this is required
if ( ZM_OPT_USE_AUTH && (ZM_AUTH_RELAY == 'hashed') ) {
  $auth_hash = generateAuthHash(ZM_AUTH_HASH_IPS);
  if ( isset($_REQUEST['auth']) and ($_REQUEST['auth'] != $auth_hash) ) {
    $data['auth'] = $auth_hash;
  }
}

switch ( $modal ) {
  case 'optionhelp' :
    if ( empty($_REQUEST['ohndx']) ) ajaxError('Option Help Index Not Provided');
    $data['html'] = getOptionHelpHTML($_REQUEST['ohndx'], $OLANG);
    break;
  case 'enoperm' :
    $data['html'] = getENoPermHTML();
    break;
  default :
    // Maybe don't need both
    ZM\Warning('Unknown modal '.$modal);
    ajaxError("Unknown modal '".$modal."'");
    return;
}

ajaxResponse($data);
return;
?>
